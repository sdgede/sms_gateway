<?php

namespace App\Commands;

use App\Models\SmsGatewayModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class WebSocketServerCommand extends BaseCommand
{
    protected $group       = 'SMS Gateway';
    protected $name        = 'ws:serve';
    protected $description = 'Starts the pure PHP RFC 6455 WebSocket Server for real-time Android Gateway dispatch.';
    protected $usage       = 'ws:serve [options]';
    protected $options     = [
        '--port'    => 'WebSocket listening port (default: 8085)',
        '--ipcPort' => 'Internal IPC listening port for CI4 broadcaster (default: 8086)',
        '--host'    => 'Host IP to bind (default: 0.0.0.0)',
    ];

    private $wsSocket;
    private $ipcSocket;
    private array $clients = []; // [socketId => ['socket' => resource, 'handshake' => bool, 'gateway' => ?array, 'ip' => string]]
    private SmsGatewayModel $gatewayModel;

    public function run(array $params)
    {
        $this->gatewayModel = new SmsGatewayModel();

        $port = (int)($params['port'] ?? CLI::getOption('port') ?? env('websocket.port', 8085));
        $ipcPort = (int)($params['ipcPort'] ?? CLI::getOption('ipcPort') ?? env('websocket.ipcPort', 8086));
        $host = (string)($params['host'] ?? CLI::getOption('host') ?? env('websocket.host', '0.0.0.0'));

        CLI::write("==================================================", 'cyan');
        CLI::write("  SMS Gateway RFC 6455 WebSocket Server", 'yellow');
        CLI::write("==================================================", 'cyan');
        CLI::write("  Listening on: ws://{$host}:{$port}", 'green');
        CLI::write("  Internal IPC: tcp://127.0.0.1:{$ipcPort}", 'green');
        CLI::write("  Press Ctrl+C to stop.", 'dark_gray');
        CLI::write("--------------------------------------------------", 'cyan');

        // 1. Create WebSocket Server Socket
        $this->wsSocket = stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);
        if (!$this->wsSocket) {
            CLI::error("Failed to start WebSocket server on {$host}:{$port} - {$errstr} ({$errno})");
            return;
        }
        stream_set_blocking($this->wsSocket, false);

        // 2. Create Internal IPC Socket
        $this->ipcSocket = stream_socket_server("tcp://127.0.0.1:{$ipcPort}", $errno, $errstr);
        if (!$this->ipcSocket) {
            CLI::error("Failed to start IPC server on 127.0.0.1:{$ipcPort} - {$errstr} ({$errno})");
            return;
        }
        stream_set_blocking($this->ipcSocket, false);

        $lastPing = time();

        // 3. Event Loop
        while (true) {
            $read = [$this->wsSocket, $this->ipcSocket];
            $write = null;
            $except = null;

            foreach ($this->clients as $c) {
                if (is_resource($c['socket'])) {
                    $read[] = $c['socket'];
                }
            }

            $numChanged = @stream_select($read, $write, $except, 0, 200000); // 200ms timeout
            if ($numChanged === false) {
                break;
            }

            // New WebSocket connection
            if (in_array($this->wsSocket, $read, true)) {
                $clientSock = @stream_socket_accept($this->wsSocket, 0);
                if ($clientSock) {
                    stream_set_blocking($clientSock, false);
                    $id = (int)$clientSock;
                    $peer = stream_socket_get_name($clientSock, true) ?: 'unknown';
                    $this->clients[$id] = [
                        'socket'    => $clientSock,
                        'handshake' => false,
                        'gateway'   => null,
                        'ip'        => $peer,
                    ];
                    CLI::write("[WS] New client connection from {$peer} (ID: {$id})", 'light_gray');
                }
                $key = array_search($this->wsSocket, $read, true);
                unset($read[$key]);
            }

            // New IPC trigger from CI4 Backend (e.g. new SMS queued)
            if (in_array($this->ipcSocket, $read, true)) {
                $ipcClient = @stream_socket_accept($this->ipcSocket, 0);
                if ($ipcClient) {
                    $ipcData = @fread($ipcClient, 65536);
                    @fclose($ipcClient);
                    if (!empty($ipcData)) {
                        $this->handleIpcMessage(trim($ipcData));
                    }
                }
                $key = array_search($this->ipcSocket, $read, true);
                unset($read[$key]);
            }

            // Process data from connected WebSocket clients
            foreach ($read as $sock) {
                $id = (int)$sock;
                if (!isset($this->clients[$id])) {
                    continue;
                }

                $data = @fread($sock, 65536);
                if ($data === false || strlen($data) === 0) {
                    $this->disconnectClient($id, 'Connection closed by peer');
                    continue;
                }

                if (!$this->clients[$id]['handshake']) {
                    $this->performHandshake($id, $data);
                } else {
                    $this->handleClientFrame($id, $data);
                }
            }

            // Periodic Ping every 30s to keep connection alive
            if (time() - $lastPing >= 30) {
                $lastPing = time();
                $pingFrame = $this->encodeFrame('', 'ping');
                foreach ($this->clients as $id => $client) {
                    if ($client['handshake']) {
                        @fwrite($client['socket'], $pingFrame);
                    }
                }
            }
        }
    }

    /**
     * Perform RFC 6455 WebSocket Handshake with Token Authentication
     */
    private function performHandshake(int $id, string $headers): void
    {
        $client = &$this->clients[$id];

        if (!preg_match('/Sec-WebSocket-Key:\s*(.+)\r\n/i', $headers, $match)) {
            $this->disconnectClient($id, 'Handshake rejected: Missing Sec-WebSocket-Key');
            return;
        }

        $secKey = trim($match[1]);
        $acceptKey = base64_encode(sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        // Extract token from Query string (/ws?token=gw_tok_...) or Authorization header
        $token = null;
        if (preg_match('/GET\s+([^\s]+)/i', $headers, $reqMatch)) {
            $path = $reqMatch[1];
            $query = parse_url($path, PHP_URL_QUERY);
            if ($query) {
                parse_str($query, $queryParams);
                $token = $queryParams['token'] ?? $queryParams['device_token'] ?? null;
            }
        }

        if (empty($token) && preg_match('/Authorization:\s*Bearer\s*(.+)\r\n/i', $headers, $authMatch)) {
            $token = trim($authMatch[1]);
        }

        // Authenticate Gateway device if token is provided
        $gateway = null;
        if (!empty($token)) {
            $gateway = $this->gatewayModel->findByToken($token);
        }

        $client['gateway'] = $gateway;
        $client['handshake'] = true;

        $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                    "Upgrade: websocket\r\n" .
                    "Connection: Upgrade\r\n" .
                    "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";

        @fwrite($client['socket'], $response);

        $gwName = $gateway ? "'{$gateway['device_name']}' ({$gateway['device_id']})" : "Anonymous / Dashboard Observer";
        CLI::write("[WS] Handshake SUCCESS for Client ID {$id} | Device: {$gwName}", 'green');

        if ($gateway) {
            $this->gatewayModel->recordHeartbeat($gateway['device_id'], []);
        }

        // Send Welcome Packet
        $welcome = json_encode([
            'event'       => 'connected',
            'status'      => 'ONLINE',
            'message'     => 'Connected to SMS Gateway RFC 6455 WebSocket',
            'device_id'   => $gateway ? $gateway['device_id'] : null,
            'device_name' => $gateway ? $gateway['device_name'] : null,
            'server_time' => date('Y-m-d H:i:s'),
        ]);
        @fwrite($client['socket'], $this->encodeFrame($welcome));

        // Immediately push any pending jobs in queue
        if ($gateway) {
            $jobModel = new \App\Models\SmsJobModel();
            $pendingJobs = $jobModel->getNextAvailableJobs(5);
            foreach ($pendingJobs as $pj) {
                CLI::write("[WS] Pushing existing pending job {$pj['job_id']} to newly connected device {$gwName}", 'cyan');
                $jobFrame = $this->encodeFrame(json_encode([
                    'event'     => 'new_sms_job',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'data'      => $pj,
                ]));
                @fwrite($client['socket'], $jobFrame);
            }
        }
    }

    /**
     * Handle incoming WebSocket frames from Android client
     */
    private function handleClientFrame(int $id, string $data): void
    {
        $decoded = $this->decodeFrame($data);
        if ($decoded === null) {
            return;
        }

        $opcode = $decoded['opcode'];
        $payload = $decoded['payload'];

        if ($opcode === 8) { // Close frame
            $this->disconnectClient($id, 'Received close frame');
            return;
        }

        if ($opcode === 9) { // Ping frame -> Respond with Pong
            @fwrite($this->clients[$id]['socket'], $this->encodeFrame($payload, 'pong'));
            return;
        }

        if ($opcode === 10) { // Pong frame
            return;
        }

        // Text Frame JSON
        if ($opcode === 1 && !empty($payload)) {
            $json = json_decode($payload, true);
            if (is_array($json)) {
                $action = $json['action'] ?? $json['event'] ?? 'unknown';
                $gateway = $this->clients[$id]['gateway'] ?? null;
                CLI::write("[WS] Received message/action '{$action}' from Client {$id}", 'light_cyan');

                $jobModel = new \App\Models\SmsJobModel();
                $reportModel = new \App\Models\SmsDeliveryReportModel();

                // 1. Ping / Heartbeat
                if ($action === 'ping' || $action === 'heartbeat') {
                    if ($gateway) {
                        $this->gatewayModel->recordHeartbeat($gateway['device_id'], $json);
                    }
                    $ack = json_encode([
                        'event'       => 'heartbeat_ack',
                        'status'      => 'ONLINE',
                        'server_time' => date('Y-m-d H:i:s'),
                    ]);
                    @fwrite($this->clients[$id]['socket'], $this->encodeFrame($ack));
                }

                // 2. Claim Job over WebSocket
                elseif ($action === 'claim' && !empty($json['job_id']) && $gateway) {
                    $jobId = $json['job_id'];
                    $lockSecs = (int)($json['lock_seconds'] ?? 60);
                    $claimed = $jobModel->claimJob($jobId, $gateway['device_id'], $lockSecs);
                    $response = json_encode([
                        'event'   => 'claim_result',
                        'job_id'  => $jobId,
                        'success' => $claimed,
                        'message' => $claimed ? 'Job claimed successfully' : 'Claim failed / already claimed',
                    ]);
                    @fwrite($this->clients[$id]['socket'], $this->encodeFrame($response));
                }

                // 3. Mark Sending over WebSocket
                elseif ($action === 'start' && !empty($json['job_id']) && $gateway) {
                    $jobId = $json['job_id'];
                    $jobModel->markAsSending($jobId, $gateway['device_id']);
                }

                // 4. Report Delivery over WebSocket
                elseif ($action === 'report' && !empty($json['job_id']) && $gateway) {
                    $jobId = $json['job_id'];
                    $status = strtoupper(trim((string)($json['status'] ?? 'SENT')));
                    $statusCode = $json['operator_status_code'] ?? null;
                    $statusMsg = $json['operator_status_message'] ?? null;

                    $reportModel->recordReport([
                        'job_id'                  => $jobId,
                        'device_id'               => $gateway['device_id'],
                        'status'                  => $status,
                        'operator_status_code'    => $statusCode,
                        'operator_status_message' => $statusMsg,
                        'reported_at'             => date('Y-m-d H:i:s'),
                    ]);

                    if ($status === 'SENT') {
                        $jobModel->markAsSent($jobId, $gateway['device_id']);
                    } elseif ($status === 'DELIVERED') {
                        $jobModel->markAsDelivered($jobId, $gateway['device_id']);
                    } elseif ($status === 'FAILED') {
                        $isRecoverable = isset($json['is_recoverable']) ? (bool)$json['is_recoverable'] : true;
                        $jobModel->markAsFailed($jobId, $gateway['device_id'], $statusMsg ?? 'Failed', $isRecoverable);
                    }

                    $ack = json_encode([
                        'event'   => 'report_ack',
                        'job_id'  => $jobId,
                        'status'  => $status,
                        'success' => true,
                    ]);
                    @fwrite($this->clients[$id]['socket'], $this->encodeFrame($ack));
                }
            }
        }
    }

    /**
     * Handle IPC Message received from CI4 Backend (e.g. new SMS queued)
     */
    private function handleIpcMessage(string $rawJson): void
    {
        $msg = json_decode($rawJson, true);
        if (!$msg || !isset($msg['event'])) {
            return;
        }

        $event = $msg['event'];
        $payload = json_encode($msg);
        $frame = $this->encodeFrame($payload);

        $sentCount = 0;
        foreach ($this->clients as $id => $client) {
            if ($client['handshake']) {
                @fwrite($client['socket'], $frame);
                $sentCount++;
            }
        }

        CLI::write("[WS::IPC] Broadcasted event '{$event}' to {$sentCount} active client(s)", 'green');
    }

    /**
     * Encode payload into RFC 6455 WebSocket Frame
     */
    private function encodeFrame(string $payload, string $type = 'text'): string
    {
        $opcodes = ['text' => 0x1, 'close' => 0x8, 'ping' => 0x9, 'pong' => 0xA];
        $b1 = 0x80 | ($opcodes[$type] ?? 0x1);
        $length = strlen($payload);

        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length <= 65535) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            $header = pack('CCNN', $b1, 127, 0, $length);
        }

        return $header . $payload;
    }

    /**
     * Decode RFC 6455 WebSocket Frame
     */
    private function decodeFrame(string $data): ?array
    {
        if (strlen($data) < 2) {
            return null;
        }

        $b1 = ord($data[0]);
        $b2 = ord($data[1]);

        $opcode = $b1 & 0x0F;
        $isMasked = (bool)($b2 & 0x80);
        $len = $b2 & 0x7F;

        $offset = 2;
        if ($len === 126) {
            if (strlen($data) < 4) return null;
            $len = unpack('n', substr($data, 2, 2))[1];
            $offset = 4;
        } elseif ($len === 127) {
            if (strlen($data) < 10) return null;
            $len = unpack('J', substr($data, 2, 8))[1];
            $offset = 10;
        }

        $maskKey = '';
        if ($isMasked) {
            if (strlen($data) < $offset + 4) return null;
            $maskKey = substr($data, $offset, 4);
            $offset += 4;
        }

        $payload = substr($data, $offset, $len);
        if ($isMasked && !empty($maskKey)) {
            $unmasked = '';
            for ($i = 0; $i < strlen($payload); $i++) {
                $unmasked .= $payload[$i] ^ $maskKey[$i % 4];
            }
            $payload = $unmasked;
        }

        return [
            'opcode'  => $opcode,
            'payload' => $payload,
        ];
    }

    /**
     * Disconnect client cleanly
     */
    private function disconnectClient(int $id, string $reason = ''): void
    {
        if (isset($this->clients[$id])) {
            $sock = $this->clients[$id]['socket'];
            if (is_resource($sock)) {
                @fclose($sock);
            }
            unset($this->clients[$id]);
            CLI::write("[WS] Client {$id} disconnected ({$reason})", 'yellow');
        }
    }
}

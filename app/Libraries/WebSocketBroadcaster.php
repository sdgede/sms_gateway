<?php

namespace App\Libraries;

class WebSocketBroadcaster
{
    /**
     * Broadcast a new SMS job event to the WebSocket server daemon
     */
    public static function broadcastNewJob(array $job): bool
    {
        return self::sendSocketMessage([
            'event'     => 'new_sms_job',
            'timestamp' => date('Y-m-d H:i:s'),
            'data'      => $job,
        ]);
    }

    /**
     * Broadcast general gateway event (e.g. status change, heartbeat ack)
     */
    public static function broadcastEvent(string $event, array $data = []): bool
    {
        return self::sendSocketMessage([
            'event'     => $event,
            'timestamp' => date('Y-m-d H:i:s'),
            'data'      => $data,
        ]);
    }

    /**
     * Send internal IPC message to local WebSocket daemon
     */
    private static function sendSocketMessage(array $payload): bool
    {
        $port = (int)(env('websocket.ipcPort', 8086));
        $host = env('websocket.host', '127.0.0.1');

        $socket = @fsockopen($host, $port, $errno, $errstr, 0.5);
        if (!$socket) {
            // WebSocket server might not be running; non-blocking fail
            return false;
        }

        $json = json_encode($payload) . "\n";
        @fwrite($socket, $json);
        @fclose($socket);

        return true;
    }
}

<?php

namespace App\Controllers;

use App\Libraries\FcmService;
use App\Libraries\SmsDispatcher;
use App\Models\SmsAuditLogModel;
use App\Models\SmsDeliveryReportModel;
use App\Models\SmsGatewayModel;
use App\Models\SmsJobModel;
use App\Models\SmsPairingCodeModel;
use App\Models\SmsPhoneLineModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Web Dashboard Controller
 *
 * Provides real-time UI, statistics, pairing code management,
 * device actions, and direct test SMS dispatching.
 */
class Home extends BaseController
{
    protected SmsGatewayModel $gatewayModel;
    protected SmsPairingCodeModel $pairingModel;
    protected SmsJobModel $jobModel;
    protected SmsDeliveryReportModel $reportModel;
    protected SmsAuditLogModel $auditLogModel;
    protected SmsPhoneLineModel $phoneLineModel;

    public function __construct()
    {
        $this->gatewayModel = new SmsGatewayModel();
        $this->pairingModel = new SmsPairingCodeModel();
        $this->jobModel = new SmsJobModel();
        $this->reportModel = new SmsDeliveryReportModel();
        $this->auditLogModel = new SmsAuditLogModel();
        $this->phoneLineModel = new SmsPhoneLineModel();
    }

    /**
     * Render the main dashboard web interface
     * GET /
     */
    public function index(): ResponseInterface
    {
        return $this->response->setBody(view('dashboard'));
    }

    /**
     * Fetch Live Data for Dashboard Polling (every 3 seconds)
     * GET /web/data
     */
    public function getLiveData(): ResponseInterface
    {
        // 1. Android Gateway Devices with computed online/offline status
        $gateways = $this->gatewayModel->orderBy('id', 'DESC')->findAll();
        $now = time();
        foreach ($gateways as &$gw) {
            unset($gw['token_hash']); // Security: Never expose token hashes to UI
            if ($gw['status'] !== 'DISABLED') {
                if (!empty($gw['last_seen_at'])) {
                    $lastSeenSecs = $now - strtotime($gw['last_seen_at']);
                    $gw['computed_status'] = ($lastSeenSecs <= 120) ? 'ONLINE' : 'OFFLINE';
                    $gw['last_seen_human'] = $lastSeenSecs < 60 ? "{$lastSeenSecs}s ago" : round($lastSeenSecs / 60) . "m ago";
                } else {
                    $gw['computed_status'] = 'OFFLINE';
                    $gw['last_seen_human'] = 'Never';
                }
            } else {
                $gw['computed_status'] = 'DISABLED';
                $gw['last_seen_human'] = !empty($gw['last_seen_at']) ? date('d M H:i', strtotime($gw['last_seen_at'])) : '-';
            }
        }

        // 2. Active & Recent Pairing Codes
        $pairingCodes = $this->pairingModel->orderBy('id', 'DESC')->findAll(10);
        foreach ($pairingCodes as &$code) {
            $code['is_expired'] = false;
            $code['status_label'] = $code['is_used'] ? 'USED' : 'ACTIVE';
        }

        // 3. SMS Jobs Queue (Latest 50 entries)
        $jobs = $this->jobModel->orderBy('id', 'DESC')->findAll(50);

        // 4. Phone Lines (Registered FCM SIM Lines)
        $phoneLines = $this->phoneLineModel->orderBy('updated_at', 'DESC')->findAll(20);
        foreach ($phoneLines as &$pl) {
            $pl['has_fcm'] = !empty($pl['fcm_token']);
            $pl['fcm_preview'] = !empty($pl['fcm_token']) ? substr($pl['fcm_token'], 0, 14) . '...' . substr($pl['fcm_token'], -6) : 'None';
            $pl['updated_human'] = !empty($pl['updated_at']) ? date('d M H:i', strtotime($pl['updated_at'])) : '-';
        }

        // 5. Active Dispatcher Configuration & Methods
        $fcmStatus = FcmService::getConfigStatus();
        $dispatcherMethods = SmsDispatcher::getActiveMethods();
        $dispatcherLabels = SmsDispatcher::getActiveMethodLabels();

        // 6. Aggregate Queue Statistics
        $stats = $this->jobModel->getStatistics();
        $stats['total_jobs'] = $stats['total'] ?? 0;
        $stats['gateways_count'] = count($gateways);
        $stats['gateways_online'] = count(array_filter($gateways, fn($g) => ($g['computed_status'] ?? '') === 'ONLINE'));
        $stats['phone_lines_count'] = count($phoneLines);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'stats'              => $stats,
                'gateways'           => $gateways,
                'pairing_codes'      => $pairingCodes,
                'phone_lines'        => $phoneLines,
                'jobs'               => $jobs,
                'fcm_status'         => $fcmStatus,
                'dispatcher_methods' => $dispatcherMethods,
                'dispatcher_labels'  => $dispatcherLabels,
                'server_time'        => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Web UI: Generate a new One-Time Pairing Code
     * POST /web/pairing/generate
     */
    public function generatePairing(): ResponseInterface
    {
        $deviceName = trim($this->request->getVar('device_name') ?? '');
        if (empty($deviceName)) {
            $json = $this->request->getJSON(true);
            $deviceName = $json['device_name'] ?? 'Android Gateway Device';
        }

        $pairing = $this->pairingModel->generateCode($deviceName);

        $this->auditLogModel->log(
            actorType: 'ADMIN',
            actorId: 'WEB_UI',
            action: 'PAIRING_CODE_GENERATED',
            target: $pairing['code'],
            metadata: ['device_name' => $deviceName]
        );

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Pairing code generated successfully!',
            'data'    => $pairing,
        ]);
    }

    /**
     * Web UI: Delete Unused Pairing Code
     * POST /web/pairing/delete
     */
    public function deletePairing(): ResponseInterface
    {
        $id = (int)($this->request->getVar('id') ?? 0);
        if ($id === 0) {
            $json = $this->request->getJSON(true);
            $id = (int)($json['id'] ?? 0);
        }
        $pairing = $this->pairingModel->find($id);

        if (!$pairing) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Kode pairing tidak ditemukan.',
            ]);
        }

        $this->pairingModel->delete($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Kode pairing {$pairing['code']} berhasil dihapus.",
        ]);
    }

    /**
     * Web UI: Enqueue & Dispatch Test SMS
     * POST /web/sms/send
     */
    public function sendTestSms(): ResponseInterface
    {
        $recipient = trim($this->request->getVar('recipient') ?? '');
        $message = trim($this->request->getVar('message') ?? '');
        $priority = (int)($this->request->getVar('priority') ?? 2);
        $clientMessageId = trim($this->request->getVar('client_message_id') ?? '');

        if (empty($recipient) || empty($message)) {
            $json = $this->request->getJSON(true) ?? [];
            $recipient = trim($json['recipient'] ?? '');
            $message = trim($json['message'] ?? '');
            $priority = (int)($json['priority'] ?? $priority);
            $clientMessageId = trim($json['client_message_id'] ?? $clientMessageId);
        }

        if (empty($recipient) || empty($message)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Nomor tujuan dan isi pesan tidak boleh kosong.',
            ]);
        }

        if (empty($clientMessageId)) {
            $clientMessageId = 'TEST-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        $result = $this->jobModel->createJob([
            'recipient'         => $recipient,
            'message'           => $message,
            'priority'          => $priority,
            'client_message_id' => $clientMessageId,
        ]);

        $this->auditLogModel->log(
            actorType: 'ADMIN',
            actorId: 'WEB_UI',
            action: 'TEST_SMS_QUEUED',
            target: $result['job']['job_id'],
            metadata: ['recipient' => $recipient, 'client_message_id' => $clientMessageId]
        );

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => $result['is_replay'] ? 'Pesan SMS dengan ID ini sudah ada (Idempotent)' : 'Pesan SMS berhasil dimasukkan ke antrean!',
            'data'    => $result['job'],
        ]);
    }

    /**
     * Web UI: Device Management Actions (enable, disable, revoke, delete)
     * POST /web/gateway/action
     */
    public function gatewayAction(): ResponseInterface
    {
        $deviceId = trim($this->request->getVar('device_id') ?? '');
        $action = trim($this->request->getVar('action') ?? '');

        if (empty($deviceId) || empty($action)) {
            $json = $this->request->getJSON(true) ?? [];
            $deviceId = trim($json['device_id'] ?? $deviceId);
            $action = trim($json['action'] ?? $action);
        }

        $gateway = $this->gatewayModel->findByDeviceId($deviceId);
        if (!$gateway) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Perangkat gateway tidak ditemukan.',
            ]);
        }

        if ($action === 'revoke') {
            $this->gatewayModel->revokeToken($deviceId);
            if (!empty($gateway['phone_number'])) {
                $this->phoneLineModel->where('phone_number', $gateway['phone_number'])->delete();
            }
            $this->phoneLineModel->where('id', $deviceId)->delete();
            $msg = "Token dan FCM untuk perangkat {$gateway['device_name']} berhasil di-revoke.";
        } elseif ($action === 'disable') {
            $this->gatewayModel->update($gateway['id'], ['status' => 'DISABLED']);
            $msg = "Perangkat {$gateway['device_name']} dinonaktifkan.";
        } elseif ($action === 'enable') {
            $this->gatewayModel->update($gateway['id'], ['status' => 'OFFLINE']);
            $msg = "Perangkat {$gateway['device_name']} diaktifkan (menunggu heartbeat).";
        } elseif ($action === 'delete') {
            $this->gatewayModel->delete($gateway['id']);
            // Cascade delete associated FCM phone lines
            if (!empty($gateway['phone_number'])) {
                $this->phoneLineModel->where('phone_number', $gateway['phone_number'])->delete();
            }
            $this->phoneLineModel->where('id', $deviceId)->delete();
            $this->phoneLineModel->where('phone_number', $deviceId)->delete();
            $msg = "Perangkat {$gateway['device_name']} dan token FCM terkait berhasil dihapus.";
        } else {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Aksi gateway tidak valid.',
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => $msg,
        ]);
    }

    /**
     * Web UI: Requeue / Retry SMS Job immediately (Reset to PENDING & trigger push)
     * POST /web/sms/requeue
     */
    public function requeueSms(): ResponseInterface
    {
        $jobId = trim($this->request->getVar('job_id') ?? '');
        if (empty($jobId)) {
            $json = $this->request->getJSON(true) ?? [];
            $jobId = trim($json['job_id'] ?? '');
        }
        $job = $this->jobModel->findByJobId($jobId);

        if (!$job) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => "SMS job '{$jobId}' tidak ditemukan.",
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $this->jobModel->update($job['id'], [
            'status'             => SmsJobModel::STATUS_PENDING,
            'available_at'       => $now,
            'assigned_device_id' => null,
            'claimed_at'         => null,
            'claim_expires_at'   => null,
            'attempt'            => 0,
            'failed_reason'      => null,
            'updated_at'         => $now,
        ]);

        log_message('info', "[Home::requeueSms] Job {$jobId} reset to PENDING. Triggering dispatch...");

        // Dispatch job using active methods configured in .env (Firebase / SSE / WebSocket)
        SmsDispatcher::dispatchJob($job);

        $this->auditLogModel->log(
            actorType: 'ADMIN',
            actorId: 'WEB_UI',
            action: 'SMS_JOB_REQUEUED',
            target: $jobId
        );

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Job {$jobId} berhasil di-reset ke antrean PENDING dan sinyal pengiriman telah ditembakkan.",
        ]);
    }

    /**
     * Web UI: Delete SMS Job
     * POST /web/sms/delete
     */
    public function deleteSms(): ResponseInterface
    {
        $jobId = trim($this->request->getVar('job_id') ?? '');
        if (empty($jobId)) {
            $json = $this->request->getJSON(true) ?? [];
            $jobId = trim($json['job_id'] ?? '');
        }
        $job = $this->jobModel->findByJobId($jobId);

        if (!$job) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => "SMS job '{$jobId}' tidak ditemukan.",
            ]);
        }

        $this->jobModel->delete($job['id']);
        $this->reportModel->where('job_id', $jobId)->delete();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Job {$jobId} berhasil dihapus dari sistem.",
        ]);
    }

    /**
     * Web UI: Delete Registered Phone Line / FCM Token
     * POST /web/phone-line/delete
     */
    public function deletePhoneLine(): ResponseInterface
    {
        $id = trim($this->request->getVar('id') ?? '');
        if (empty($id)) {
            $json = $this->request->getJSON(true) ?? [];
            $id = trim($json['id'] ?? '');
        }
        $line = $this->phoneLineModel->find($id);

        if (!$line) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'SIM Line tidak ditemukan.',
            ]);
        }

        $this->phoneLineModel->delete($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "SIM Line {$line['phone_number']} berhasil dihapus.",
        ]);
    }

    /**
     * Web UI: Manually Run Queue Maintenance Worker
     * POST /web/worker/run
     */
    public function runWorker(): ResponseInterface
    {
        $recovered = $this->jobModel->recoverStaleClaims();
        $retried = $this->jobModel->processRetries();

        $msg = "Worker maintenance selesai: {$recovered} job stale di-recover, {$retried} job retry dirilis ke PENDING.";

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => $msg,
            'data'    => [
                'recovered_claims' => $recovered,
                'released_retries' => $retried,
            ],
        ]);
    }
}

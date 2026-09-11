<?php

namespace App\Controllers;

use App\Models\SmsAuditLogModel;
use App\Models\SmsDeliveryReportModel;
use App\Models\SmsGatewayModel;
use App\Models\SmsJobModel;
use App\Models\SmsPairingCodeModel;
use CodeIgniter\HTTP\ResponseInterface;

class Home extends BaseController
{
    protected SmsGatewayModel $gatewayModel;
    protected SmsPairingCodeModel $pairingModel;
    protected SmsJobModel $jobModel;
    protected SmsDeliveryReportModel $reportModel;
    protected SmsAuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->gatewayModel = new SmsGatewayModel();
        $this->pairingModel = new SmsPairingCodeModel();
        $this->jobModel = new SmsJobModel();
        $this->reportModel = new SmsDeliveryReportModel();
        $this->auditLogModel = new SmsAuditLogModel();
    }

    /**
     * Dashboard & Test UI
     */
    public function index(): ResponseInterface
    {
        return $this->response->setBody(view('dashboard'));
    }

    /**
     * Fetch Live Data for UI Polling
     * GET /web/data
     */
    public function getLiveData(): ResponseInterface
    {
        // 1. Gateways with computed online/offline status
        $gateways = $this->gatewayModel->orderBy('id', 'DESC')->findAll();
        $now = time();
        foreach ($gateways as &$gw) {
            unset($gw['token_hash']);
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

        // 2. Pairing Codes
        $pairingCodes = $this->pairingModel->orderBy('id', 'DESC')->findAll(10);
        foreach ($pairingCodes as &$code) {
            $isExpired = strtotime($code['expires_at']) < $now;
            $code['is_expired'] = $isExpired;
            $code['status_label'] = $code['is_used'] ? 'USED' : ($isExpired ? 'EXPIRED' : 'ACTIVE');
        }

        // 3. SMS Jobs (latest 50)
        $jobs = $this->jobModel->orderBy('id', 'DESC')->findAll(50);

        // 4. Aggregate Stats
        $stats = $this->jobModel->getStatistics();
        $stats['gateways_count'] = count($gateways);
        $stats['gateways_online'] = count(array_filter($gateways, fn($g) => ($g['computed_status'] ?? '') === 'ONLINE'));

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'stats'         => $stats,
                'gateways'      => $gateways,
                'pairing_codes' => $pairingCodes,
                'jobs'          => $jobs,
                'server_time'   => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Web UI: Generate Pairing Code
     * POST /web/pairing/generate
     */
    public function generatePairing(): ResponseInterface
    {
        $deviceName = trim($this->request->getPost('device_name') ?? 'Android Gateway Device');
        $expiryMinutes = (int)($this->request->getPost('expiry_minutes') ?? 15);

        if ($expiryMinutes < 1 || $expiryMinutes > 1440) {
            $expiryMinutes = 15;
        }

        $pairing = $this->pairingModel->generateCode($deviceName, $expiryMinutes);

        $this->auditLogModel->log(
            actorType: 'ADMIN',
            actorId: 'WEB_UI',
            action: 'PAIRING_CODE_GENERATED',
            target: $pairing['code'],
            metadata: ['device_name' => $deviceName, 'expiry_minutes' => $expiryMinutes]
        );

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Pairing code generated successfully!',
            'data'    => $pairing,
        ]);
    }

    /**
     * Web UI: Send Test SMS
     * POST /web/sms/send
     */
    public function sendTestSms(): ResponseInterface
    {
        $recipient = trim($this->request->getPost('recipient') ?? '');
        $message = trim($this->request->getPost('message') ?? '');
        $priority = (int)($this->request->getPost('priority') ?? 2);
        $clientMessageId = trim($this->request->getPost('client_message_id') ?? '');

        if (empty($recipient) || empty($message)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Recipient and message cannot be empty.',
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
            'message' => $result['is_replay'] ? 'Existing SMS job retrieved (Idempotent)' : 'SMS queued successfully!',
            'data'    => $result['job'],
        ]);
    }

    /**
     * Web UI: Gateway Actions (Toggle Status / Revoke)
     * POST /web/gateway/action
     */
    public function gatewayAction(): ResponseInterface
    {
        $deviceId = trim($this->request->getPost('device_id') ?? '');
        $action = trim($this->request->getPost('action') ?? '');

        $gateway = $this->gatewayModel->findByDeviceId($deviceId);
        if (!$gateway) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Gateway not found',
            ]);
        }

        if ($action === 'revoke') {
            $this->gatewayModel->revokeToken($deviceId);
            $msg = "Token for device {$gateway['device_name']} revoked.";
        } elseif ($action === 'disable') {
            $this->gatewayModel->update($gateway['id'], ['status' => 'DISABLED']);
            $msg = "Device {$gateway['device_name']} disabled.";
        } elseif ($action === 'enable') {
            $this->gatewayModel->update($gateway['id'], ['status' => 'OFFLINE']);
            $msg = "Device {$gateway['device_name']} enabled (waiting for heartbeat).";
        } elseif ($action === 'delete') {
            $this->gatewayModel->delete($gateway['id']);
            $msg = "Device {$gateway['device_name']} deleted.";
        } else {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Invalid action',
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => $msg,
        ]);
    }

    /**
     * Web UI: Requeue / Retry SMS Job immediately (Reset to PENDING)
     * POST /web/sms/requeue
     */
    public function requeueSms(): ResponseInterface
    {
        $jobId = trim($this->request->getPost('job_id') ?? '');
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

        $this->auditLogModel->log(
            actorType: 'ADMIN',
            actorId: 'WEB_UI',
            action: 'SMS_JOB_REQUEUED',
            target: $jobId
        );

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Job {$jobId} berhasil di-reset ke antrean PENDING untuk dikirim ulang oleh Android.",
        ]);
    }

    /**
     * Web UI: Delete SMS Job
     * POST /web/sms/delete
     */
    public function deleteSms(): ResponseInterface
    {
        $jobId = trim($this->request->getPost('job_id') ?? '');
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
     * Web UI: Run Background Worker manually
     * POST /web/worker/run
     */
    public function runWorker(): ResponseInterface
    {
        $recovered = $this->jobModel->recoverStaleClaims();
        $retried = $this->jobModel->processRetries();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Worker executed: {$recovered} stale claims recovered, {$retried} retries released to PENDING.",
        ]);
    }
}

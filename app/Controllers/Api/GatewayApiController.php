<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\SmsAuditLogModel;
use App\Models\SmsDeliveryReportModel;
use App\Models\SmsGatewayModel;
use App\Models\SmsJobModel;
use App\Models\SmsPairingCodeModel;
use CodeIgniter\HTTP\ResponseInterface;

class GatewayApiController extends BaseController
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
     * Get authenticated gateway from Request
     */
    protected function getAuthenticatedGateway(): ?array
    {
        return $this->request->authenticatedGateway ?? null;
    }

    /**
     * Helper to extract request data flexibly from JSON, Raw Body, or POST
     */
    private function extractRequestData(): array
    {
        $data = $this->request->getJSON(true);
        if (empty($data) || !is_array($data)) {
            $raw = (string)$this->request->getBody();
            if (!empty($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }
        if (empty($data) || !is_array($data)) {
            $data = $this->request->getPost() ?? [];
        }
        return $data;
    }

    /**
     * One-Time Device Pairing
     * POST /gateway/pair
     */
    public function pair(): ResponseInterface
    {
        $raw = $this->extractRequestData();
        $rawBodyString = (string)$this->request->getBody();
        $contentType = $this->request->getHeaderLine('Content-Type');

        log_message('info', "[Gateway::pair] Incoming request from IP: " . $this->request->getIPAddress() . " | Content-Type: {$contentType} | Raw Body: {$rawBodyString}");

        $pairingCode = trim((string)($raw['pairing_code'] ?? $raw['code'] ?? $raw['pairingCode'] ?? ''));

        if (empty($pairingCode)) {
            log_message('error', "[Gateway::pair] 422 Error: Missing pairing_code in payload: {$rawBodyString}");

            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'code'    => 'MISSING_PAIRING_CODE',
                'message' => "Parameter 'pairing_code' wajib diisi.",
                'debug'   => [
                    'received_raw_body' => $rawBodyString,
                    'parsed_fields'     => $raw,
                ],
            ]);
        }

        // 1. Validate Pairing Code FIRST
        $validPairing = $this->pairingModel->validateCode($pairingCode);
        if (!$validPairing) {
            log_message('error', "[Gateway::pair] Invalid/expired pairing code: '{$pairingCode}'");

            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'code'    => 'INVALID_OR_EXPIRED_CODE',
                'message' => "Kode pairing '{$pairingCode}' tidak valid, sudah pernah digunakan, atau sudah kadaluarsa. Silakan buat kode baru di dashboard.",
            ]);
        }

        // 2. Extract or auto-generate device_id
        $deviceId = trim((string)(
            $raw['device_id'] ?? 
            $raw['deviceId'] ?? 
            $raw['android_id'] ?? 
            $raw['id'] ?? 
            ''
        ));

        if (empty($deviceId)) {
            $modelStr = !empty($raw['device_model']) ? preg_replace('/[^A-Za-z0-9_-]/', '_', $raw['device_model']) : 'Android';
            $deviceId = strtolower($modelStr) . '-' . substr(md5($pairingCode . ($raw['os_version'] ?? '') . time()), 0, 6);
        }

        // 3. Device Name: ALWAYS prioritized from what Admin set when generating pairing code
        $deviceName = !empty($validPairing['device_name']) 
            ? $validPairing['device_name'] 
            : (!empty($raw['device_name']) ? $raw['device_name'] : (!empty($raw['device_model']) ? $raw['device_model'] : 'Android Gateway'));

        // 4. Extract SIM info (support nested sim_info object or direct keys)
        $simOperator = $raw['sim_operator'] 
            ?? $raw['simOperator'] 
            ?? ($raw['sim_info']['operator_name'] ?? ($raw['sim_info']['operator'] ?? null));

        $simSlot = 1;
        if (isset($raw['sim_info']['sim_slot'])) {
            $simSlot = (int)$raw['sim_info']['sim_slot'];
        } elseif (isset($raw['sim_slot'])) {
            $simSlot = (int)$raw['sim_slot'];
        }

        $phoneNumber = $raw['phone_number'] 
            ?? $raw['phoneNumber'] 
            ?? ($raw['sim_info']['phone_number'] ?? null);

        $appVersion = $raw['app_version'] 
            ?? $raw['appVersion'] 
            ?? null;

        // 5. Generate secure device token
        $rawToken = 'gw_tok_' . bin2hex(random_bytes(24));
        $tokenHash = SmsGatewayModel::hashToken($rawToken);

        $gatewayData = [
            'device_id'     => $deviceId,
            'device_name'   => $deviceName,
            'token_hash'    => $tokenHash,
            'status'        => 'ONLINE',
            'sim_operator'  => $simOperator,
            'sim_slot'      => $simSlot,
            'phone_number'  => $phoneNumber,
            'app_version'   => $appVersion,
            'last_seen_at'  => date('Y-m-d H:i:s'),
        ];

        // 6. Upsert gateway record
        $existing = $this->gatewayModel->findByDeviceId($deviceId);
        if ($existing) {
            $this->gatewayModel->update($existing['id'], $gatewayData);
        } else {
            $this->gatewayModel->insert($gatewayData);
        }

        // 7. Mark pairing code used
        $this->pairingModel->markAsUsed($validPairing['id'], $deviceId);

        // 8. Audit log
        $this->auditLogModel->log(
            actorType: 'GATEWAY',
            actorId: $deviceId,
            action: 'DEVICE_PAIRED',
            target: $deviceName,
            metadata: [
                'sim_operator' => $simOperator,
                'device_model' => $raw['device_model'] ?? null,
                'os_version'   => $raw['os_version'] ?? null,
            ]
        );

        log_message('info', "[Gateway::pair] Device successfully paired! ID: {$deviceId} | Name: {$deviceName} | Operator: {$simOperator}");

        return $this->response->setStatusCode(200)->setJSON([
            'status'  => 'success',
            'message' => 'Device successfully paired to SMS Gateway.',
            'data'    => [
                'device_id'     => $deviceId,
                'device_name'   => $deviceName,
                'device_token'  => $rawToken,
                'server_time'   => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Gateway Heartbeat
     * POST /gateway/heartbeat
     */
    public function heartbeat(): ResponseInterface
    {
        $gateway = $this->getAuthenticatedGateway();
        $json = $this->extractRequestData();

        $this->gatewayModel->recordHeartbeat($gateway['device_id'], $json ?? []);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Heartbeat acknowledged',
            'data'    => [
                'device_id'   => $gateway['device_id'],
                'status'      => 'ONLINE',
                'server_time' => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Gateway Profile Info
     * GET /gateway/profile
     */
    public function profile(): ResponseInterface
    {
        $gateway = $this->getAuthenticatedGateway();
        unset($gateway['token_hash']);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $gateway,
        ]);
    }

    /**
     * Revoke device token
     * POST /gateway/revoke
     */
    public function revoke(): ResponseInterface
    {
        $gateway = $this->getAuthenticatedGateway();
        $this->gatewayModel->revokeToken($gateway['device_id']);

        $this->auditLogModel->log(
            actorType: 'GATEWAY',
            actorId: $gateway['device_id'],
            action: 'DEVICE_TOKEN_REVOKED',
            target: $gateway['device_name']
        );

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Device token has been revoked successfully.',
        ]);
    }

    /**
     * Fetch next available pending job
     * GET /gateway/jobs/next
     */
    public function getNextJobs(): ResponseInterface
    {
        $gateway = $this->getAuthenticatedGateway();

        // Check Rate Limit
        if ($this->gatewayModel->isRateLimitExceeded($gateway)) {
            return $this->response->setStatusCode(429)->setJSON([
                'status'  => 'error',
                'code'    => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Gateway sending rate limit reached. Please wait before polling.',
                'data'    => [],
            ]);
        }

        $limit = (int)($this->request->getGet('limit') ?? 1);
        if ($limit < 1 || $limit > 10) {
            $limit = 1;
        }

        $jobs = $this->jobModel->getNextAvailableJobs($limit);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $jobs,
        ]);
    }

    /**
     * Atomically claim a job
     * POST /gateway/jobs/{id}/claim
     */
    public function claimJob(string $jobId): ResponseInterface
    {
        $gateway = $this->getAuthenticatedGateway();
        $lockTimeout = (int)($this->request->getGet('lock_seconds') ?? 60);

        $claimed = $this->jobModel->claimJob($jobId, $gateway['device_id'], $lockTimeout);

        if (!$claimed) {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'error',
                'code'    => 'JOB_CLAIM_FAILED',
                'message' => 'Job could not be claimed. It may have already been claimed by another gateway or is not pending.',
            ]);
        }

        $job = $this->jobModel->findByJobId($jobId);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Job claimed successfully.',
            'data'    => $job,
        ]);
    }

    /**
     * Mark job as SENDING (dispatched to SmsManager)
     * POST /gateway/jobs/{id}/start
     */
    public function startJob(string $jobId): ResponseInterface
    {
        $gateway = $this->getAuthenticatedGateway();
        $updated = $this->jobModel->markAsSending($jobId, $gateway['device_id']);

        if (!$updated) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'code'    => 'START_JOB_FAILED',
                'message' => 'Unable to mark job as SENDING. Job might not be claimed by this device.',
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Job marked as SENDING.',
        ]);
    }

    /**
     * Operator Delivery / Status Report
     * POST /gateway/jobs/{id}/report
     */
    public function reportStatus(string $jobId): ResponseInterface
    {
        $gateway = $this->getAuthenticatedGateway();
        $raw = $this->extractRequestData();

        $json = [
            'status'                  => strtoupper(trim((string)($raw['status'] ?? ''))),
            'operator_status_code'    => $raw['operator_status_code'] ?? $raw['operatorStatusCode'] ?? $raw['code'] ?? null,
            'operator_status_message' => $raw['operator_status_message'] ?? $raw['operatorStatusMessage'] ?? $raw['message'] ?? null,
            'is_recoverable'          => $raw['is_recoverable'] ?? $raw['isRecoverable'] ?? true,
            'raw_payload'             => $raw['raw_payload'] ?? $raw['rawPayload'] ?? null,
        ];

        $rules = [
            'status'                  => 'required|in_list[SENT,DELIVERED,FAILED]',
            'operator_status_code'    => 'permit_empty|max_length[50]',
            'operator_status_message' => 'permit_empty|max_length[255]',
            'is_recoverable'          => 'permit_empty',
            'raw_payload'             => 'permit_empty',
        ];

        if (!$this->validateData($json, $rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'code'    => 'VALIDATION_FAILED',
                'message' => 'Invalid report data.',
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        $status = strtoupper(trim($json['status']));
        $statusCode = $json['operator_status_code'] ?? null;
        $statusMsg = $json['operator_status_message'] ?? null;

        // Record Delivery Report
        $this->reportModel->recordReport([
            'job_id'                  => $jobId,
            'device_id'               => $gateway['device_id'],
            'status'                  => $status,
            'operator_status_code'    => $statusCode,
            'operator_status_message' => $statusMsg,
            'raw_payload'             => $json['raw_payload'] ?? null,
            'reported_at'             => date('Y-m-d H:i:s'),
        ]);

        if ($status === 'SENT') {
            $this->jobModel->markAsSent($jobId, $gateway['device_id']);
        } elseif ($status === 'DELIVERED') {
            $this->jobModel->markAsDelivered($jobId, $gateway['device_id']);
        } elseif ($status === 'FAILED') {
            $isRecoverable = isset($json['is_recoverable']) ? (bool)$json['is_recoverable'] : true;
            $failResult = $this->jobModel->markAsFailed($jobId, $gateway['device_id'], $statusMsg ?? 'Operator send error', $isRecoverable);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Failure reported and evaluated.',
                'data'    => $failResult,
            ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Job status {$status} recorded successfully.",
            'data'    => [
                'job_id'      => $jobId,
                'status'      => $status,
                'reported_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Admin/Testing Helper: Generate a new Pairing Code
     * POST /api/v1/admin/pairing/generate
     */
    public function generatePairingCode(): ResponseInterface
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $deviceName = $json['device_name'] ?? 'Android Gateway Device';
        $expiryMinutes = (int)($json['expiry_minutes'] ?? 15);

        $pairing = $this->pairingModel->generateCode($deviceName, $expiryMinutes);

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Pairing code generated successfully',
            'data'    => $pairing,
        ]);
    }
}

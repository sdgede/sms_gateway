<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\SmsAuditLogModel;
use App\Models\SmsDeliveryReportModel;
use App\Models\SmsGatewayModel;
use App\Models\SmsIncomingMessageModel;
use App\Models\SmsJobModel;
use App\Models\SmsPhoneLineModel;
use CodeIgniter\HTTP\ResponseInterface;

class HttpSmsController extends BaseController
{
    protected SmsPhoneLineModel $phoneLineModel;
    protected SmsJobModel $jobModel;
    protected SmsGatewayModel $gatewayModel;
    protected SmsDeliveryReportModel $reportModel;
    protected SmsIncomingMessageModel $incomingModel;
    protected SmsAuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->phoneLineModel = new SmsPhoneLineModel();
        $this->jobModel = new SmsJobModel();
        $this->gatewayModel = new SmsGatewayModel();
        $this->reportModel = new SmsDeliveryReportModel();
        $this->incomingModel = new SmsIncomingMessageModel();
        $this->auditLogModel = new SmsAuditLogModel();
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
                } else {
                    parse_str($raw, $parsed);
                    if (!empty($parsed) && is_array($parsed)) {
                        $data = $parsed;
                    }
                }
            }
        }
        if (empty($data) || !is_array($data)) {
            $rawInput = $this->request->getRawInput();
            if (!empty($rawInput) && is_array($rawInput)) {
                $data = $rawInput;
            }
        }
        if (empty($data) || !is_array($data)) {
            $data = $this->request->getVar() ?? [];
        }
        return is_array($data) ? $data : [];
    }

    /**
     * Format timestamp to ISO-8601 UTC with microseconds (e.g. 2026-09-09T03:00:00.000000Z)
     */
    private function formatIsoUtc(?string $datetime = null): string
    {
        $time = !empty($datetime) ? strtotime($datetime) : time();
        return gmdate('Y-m-d\TH:i:s.000000\Z', $time);
    }

    /**
     * 1. Register SIM Line & FCM Token (Login & Token Refresh)
     * PUT /v1/phones/fcm-token
     */
    public function updateFcmToken(): ResponseInterface
    {
        $raw = $this->extractRequestData();
        $rawBodyString = (string)$this->request->getBody();

        log_message('info', "[HttpSms::updateFcmToken] Incoming request: {$rawBodyString}");

        $fcmToken = trim((string)($raw['fcm_token'] ?? ''));
        $phoneNumber = trim((string)($raw['phone_number'] ?? ''));
        $sim = 'SIM1'; // Strict Single SIM Mode

        if (empty($fcmToken) || empty($phoneNumber)) {
            return $this->response->setStatusCode(422)->setJSON([
                'data'    => null,
                'message' => 'The fcm_token and phone_number fields are required.',
                'status'  => 'error',
            ]);
        }

        $userId = 'usr_' . substr(md5(env('app.smsApiKey', 'sms_key')), 0, 12);
        $line = $this->phoneLineModel->registerLine($phoneNumber, $sim, $fcmToken, $userId);

        // Also update / register gateway device in sms_gateways (Single SIM)
        $deviceId = 'dev_' . preg_replace('/[^a-zA-Z0-9]/', '', $phoneNumber);
        $existingGw = $this->gatewayModel->findByDeviceId($deviceId);
        $gwData = [
            'device_id'     => $deviceId,
            'device_name'   => "Android Gateway ({$phoneNumber})",
            'status'        => 'ONLINE',
            'phone_number'  => $phoneNumber,
            'sim_slot'      => 1,
            'last_seen_at'  => date('Y-m-d H:i:s'),
        ];
        if ($existingGw) {
            $this->gatewayModel->update($existingGw['id'], $gwData);
        } else {
            $this->gatewayModel->insert($gwData);
        }

        $this->auditLogModel->log(
            actorType: 'ANDROID_APP',
            actorId: $phoneNumber,
            action: 'FCM_TOKEN_REGISTERED',
            target: $sim,
            metadata: ['fcm_token' => substr($fcmToken, 0, 20) . '...']
        );

        return $this->response->setStatusCode(200)->setJSON([
            'data' => [
                'id'      => $line['id'],
                'user_id' => $userId,
            ],
            'message' => 'ok',
            'status'  => 'success',
        ]);
    }

    /**
     * 2. Fetch Outstanding Message for Worker
     * GET /v1/messages/outstanding?message_id=<messageId>
     */
    public function getOutstandingMessage(): ResponseInterface
    {
        $messageId = trim((string)($this->request->getGet('message_id') ?? ''));

        if (empty($messageId)) {
            return $this->response->setStatusCode(400)->setJSON([
                'data'    => null,
                'message' => 'Missing message_id parameter',
                'status'  => 'error',
            ]);
        }

        $job = $this->jobModel->findByIdOrClientId($messageId);
        if (!$job) {
            return $this->response->setStatusCode(404)->setJSON([
                'data'    => null,
                'message' => 'Message not found',
                'status'  => 'error',
            ]);
        }

        $createdAtIso = $this->formatIsoUtc($job['created_at'] ?? null);
        $updatedAtIso = $this->formatIsoUtc($job['updated_at'] ?? null);
        $sentAtIso = !empty($job['sent_at']) ? $this->formatIsoUtc($job['sent_at']) : null;
        $deliveredAtIso = !empty($job['delivered_at']) ? $this->formatIsoUtc($job['delivered_at']) : null;

        $responseMessage = [
            'id'                  => $job['job_id'],
            'contact'             => $job['recipient'],
            'content'             => $job['message'],
            'sim'                 => 'SIM1', // Default SIM1 or from job metadata
            'owner'               => $job['assigned_device_id'] ?? '',
            'encrypted'           => false,
            'status'              => 'outstanding',
            'type'                => 'sms',
            'created_at'          => $createdAtIso,
            'order_timestamp'     => $createdAtIso,
            'request_received_at' => $createdAtIso,
            'updated_at'          => $updatedAtIso,
            'failure_reason'      => $job['failed_reason'] ?? null,
            'last_attempted_at'   => null,
            'received_at'         => null,
            'sent_at'             => $sentAtIso,
            'send_time'           => null,
            'attachments'         => [],
        ];

        return $this->response->setStatusCode(200)->setJSON([
            'data'    => $responseMessage,
            'message' => 'ok',
            'status'  => 'success',
        ]);
    }

    /**
     * 3. Delivery Status Report Event
     * POST /v1/messages/{messageId}/events
     */
    public function recordMessageEvent(string $messageId): ResponseInterface
    {
        $raw = $this->extractRequestData();
        $rawBodyString = (string)$this->request->getBody();

        log_message('info', "[HttpSms::recordMessageEvent] Message {$messageId} event: {$rawBodyString}");

        $eventName = strtoupper(trim((string)($raw['event_name'] ?? '')));
        $reason = $raw['reason'] ?? null;
        $timestamp = $raw['timestamp'] ?? date('Y-m-d H:i:s');

        $job = $this->jobModel->findByIdOrClientId($messageId);
        if (!$job) {
            // Per contract: 404 is considered success by app (message already deleted/done)
            return $this->response->setStatusCode(200)->setJSON([
                'data'    => null,
                'message' => 'ok',
                'status'  => 'success',
            ]);
        }

        $now = date('Y-m-d H:i:s');
        if ($eventName === 'SENT') {
            $this->jobModel->update($job['id'], [
                'status'     => SmsJobModel::STATUS_SENT,
                'sent_at'    => $now,
                'updated_at' => $now,
            ]);
        } elseif ($eventName === 'DELIVERED') {
            $this->jobModel->update($job['id'], [
                'status'       => SmsJobModel::STATUS_DELIVERED,
                'delivered_at' => $now,
                'updated_at'   => $now,
            ]);
        } elseif ($eventName === 'FAILED') {
            $this->jobModel->update($job['id'], [
                'status'        => SmsJobModel::STATUS_FAILED,
                'failed_reason' => $reason ?? 'Generic failure',
                'updated_at'    => $now,
            ]);
        }

        // Record Delivery Report
        $this->reportModel->recordReport([
            'job_id'                  => $job['job_id'],
            'device_id'               => $job['assigned_device_id'] ?? 'ANDROID_FCM',
            'status'                  => $eventName,
            'operator_status_code'    => $eventName,
            'operator_status_message' => $reason,
            'raw_payload'             => $rawBodyString,
            'reported_at'             => $now,
        ]);

        return $this->response->setStatusCode(200)->setJSON([
            'data'    => null,
            'message' => 'ok',
            'status'  => 'success',
        ]);
    }

    /**
     * 4. Device Heartbeat
     * POST /v1/heartbeats
     */
    public function recordHeartbeat(): ResponseInterface
    {
        $raw = $this->extractRequestData();
        $deviceId = trim((string)($raw['device_id'] ?? 'default_android_device'));

        $phoneNumbers = $raw['phone_numbers'] ?? [];
        $primaryPhone = !empty($phoneNumbers[0]) ? $phoneNumbers[0] : null;

        $existing = $this->gatewayModel->findByDeviceId($deviceId);
        $gwData = [
            'device_id'       => $deviceId,
            'device_name'     => !empty($raw['sim_carrier']) ? "Android ({$raw['sim_carrier']})" : "Android Gateway",
            'status'          => 'ONLINE',
            'sim_operator'    => $raw['sim_carrier'] ?? null,
            'phone_number'    => $primaryPhone ?? ($existing['phone_number'] ?? null),
            'battery_level'   => isset($raw['battery_level']) ? (int)$raw['battery_level'] : null,
            'is_charging'     => !empty($raw['is_charging']) ? 1 : 0,
            'app_version'     => $raw['app_version'] ?? null,
            'last_seen_at'    => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->gatewayModel->update($existing['id'], $gwData);
        } else {
            $this->gatewayModel->insert($gwData);
        }

        return $this->response->setStatusCode(200)->setJSON([
            'data'    => null,
            'message' => 'ok',
            'status'  => 'success',
        ]);
    }

    /**
     * 5. Receive Incoming SMS/MMS from Android Device
     * POST /v1/messages/receive
     */
    public function receiveMessage(): ResponseInterface
    {
        $raw = $this->extractRequestData();
        $rawBodyString = (string)$this->request->getBody();

        log_message('info', "[HttpSms::receiveMessage] Incoming SMS received from device: {$rawBodyString}");

        $sim = $raw['sim'] ?? 'SIM1';
        $from = $raw['from'] ?? '';
        $to = $raw['to'] ?? '';
        $content = $raw['content'] ?? '';
        $encrypted = !empty($raw['encrypted']) ? 1 : 0;
        $attachments = isset($raw['attachments']) ? json_encode($raw['attachments']) : null;
        $timestamp = $raw['timestamp'] ?? date('Y-m-d H:i:s');

        if (empty($from) || empty($content)) {
            return $this->response->setStatusCode(422)->setJSON([
                'data'    => null,
                'message' => 'From and content fields are required',
                'status'  => 'error',
            ]);
        }

        $this->incomingModel->insert([
            'sim'                => $sim,
            'from_number'        => $from,
            'to_number'          => $to,
            'content'            => $content,
            'encrypted'          => $encrypted,
            'attachments'        => $attachments,
            'received_timestamp' => $timestamp,
        ]);

        $this->auditLogModel->log(
            actorType: 'ANDROID_DEVICE',
            actorId: $from,
            action: 'INCOMING_SMS_RECEIVED',
            target: $to,
            metadata: ['sim' => $sim, 'length' => strlen($content)]
        );

        return $this->response->setStatusCode(200)->setJSON([
            'data'    => null,
            'message' => 'ok',
            'status'  => 'success',
        ]);
    }
}

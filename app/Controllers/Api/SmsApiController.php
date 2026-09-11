<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\SmsAuditLogModel;
use App\Models\SmsDeliveryReportModel;
use App\Models\SmsJobModel;
use CodeIgniter\HTTP\ResponseInterface;

class SmsApiController extends BaseController
{
    protected SmsJobModel $jobModel;
    protected SmsDeliveryReportModel $reportModel;
    protected SmsAuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->jobModel = new SmsJobModel();
        $this->reportModel = new SmsDeliveryReportModel();
        $this->auditLogModel = new SmsAuditLogModel();
    }

    /**
     * Dispatch / Queue a new SMS message
     * POST /api/v1/sms/send
     */
    public function send(): ResponseInterface
    {
        $json = $this->request->getJSON(true) ?? $this->request->getPost();

        // Validation Rules
        $rules = [
            'recipient'         => 'required|min_length[8]|max_length[20]',
            'message'           => 'required|min_length[1]',
            'client_message_id' => 'permit_empty|max_length[100]',
            'priority'          => 'permit_empty|in_list[1,2,3]',
            'max_attempt'       => 'permit_empty|is_natural_no_zero|less_than_equal_to[10]',
        ];

        if (!$this->validateData($json, $rules)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'code'    => 'VALIDATION_FAILED',
                'message' => 'The given data was invalid.',
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        try {
            $result = $this->jobModel->createJob($json);
            $job = $result['job'];
            $isReplay = $result['is_replay'];

            // Log Audit
            $this->auditLogModel->log(
                actorType: 'API_CLIENT',
                actorId: 'API_KEY',
                action: $isReplay ? 'SMS_JOB_REPLAY_DETECTED' : 'SMS_JOB_QUEUED',
                target: $job['job_id'],
                metadata: [
                    'client_message_id' => $job['client_message_id'] ?? null,
                    'recipient'         => $job['recipient'],
                    'priority'          => $job['priority'],
                    'status'            => $job['status'],
                ]
            );

            $statusCode = $isReplay ? 200 : 202;

            return $this->response->setStatusCode($statusCode)->setJSON([
                'status'  => 'success',
                'message' => $isReplay ? 'Existing SMS job returned (Idempotent)' : 'SMS job queued successfully',
                'data'    => [
                    'job_id'            => $job['job_id'],
                    'client_message_id' => $job['client_message_id'] ?? null,
                    'recipient'         => $job['recipient'],
                    'status'            => $job['status'],
                    'priority'          => (int)$job['priority'],
                    'attempt'           => (int)$job['attempt'],
                    'is_replay'         => $isReplay,
                    'available_at'      => $job['available_at'] ?? null,
                    'created_at'        => $job['created_at'] ?? date('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[SmsApiController::send] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'code'    => 'INTERNAL_ERROR',
                'message' => 'Failed to enqueue SMS job: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get SMS Job status
     * GET /api/v1/sms/status/{idOrClientId}
     */
    public function status(string $idOrClientId): ResponseInterface
    {
        $job = $this->jobModel->findByIdOrClientId(trim($idOrClientId));

        if (!$job) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'code'    => 'NOT_FOUND',
                'message' => "SMS job '{$idOrClientId}' not found.",
            ]);
        }

        // Fetch reports
        $reports = $this->reportModel->where('job_id', $job['job_id'])->orderBy('id', 'DESC')->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'job_id'             => $job['job_id'],
                'client_message_id'  => $job['client_message_id'],
                'recipient'          => $job['recipient'],
                'message'            => $job['message'],
                'status'             => $job['status'],
                'priority'           => (int)$job['priority'],
                'attempt'            => (int)$job['attempt'],
                'max_attempt'        => (int)$job['max_attempt'],
                'assigned_device_id' => $job['assigned_device_id'],
                'sent_at'            => $job['sent_at'],
                'delivered_at'       => $job['delivered_at'],
                'failed_reason'      => $job['failed_reason'],
                'created_at'         => $job['created_at'],
                'updated_at'         => $job['updated_at'],
                'delivery_reports'   => $reports,
            ],
        ]);
    }

    /**
     * Get Aggregate Statistics
     * GET /api/v1/sms/statistics
     */
    public function statistics(): ResponseInterface
    {
        $stats = $this->jobModel->getStatistics();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $stats,
        ]);
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class SmsDeliveryReportModel extends Model
{
    protected $table            = 'sms_delivery_reports';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'job_id',
        'device_id',
        'status',
        'operator_status_code',
        'operator_status_message',
        'raw_payload',
        'reported_at',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Record delivery report from device
     */
    public function recordReport(array $data): int
    {
        $insertData = [
            'job_id'                  => $data['job_id'],
            'device_id'               => $data['device_id'] ?? null,
            'status'                  => $data['status'],
            'operator_status_code'    => $data['operator_status_code'] ?? null,
            'operator_status_message' => $data['operator_status_message'] ?? null,
            'raw_payload'             => isset($data['raw_payload']) ? (is_string($data['raw_payload']) ? $data['raw_payload'] : json_encode($data['raw_payload'])) : null,
            'reported_at'             => $data['reported_at'] ?? date('Y-m-d H:i:s'),
            'created_at'              => date('Y-m-d H:i:s'),
        ];

        return $this->insert($insertData);
    }
}

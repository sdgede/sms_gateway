<?php

namespace App\Models;

use CodeIgniter\Model;

class SmsAuditLogModel extends Model
{
    protected $table            = 'sms_audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'actor_type',
        'actor_id',
        'action',
        'target',
        'ip_address',
        'metadata',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Log an action in the system
     */
    public function log(string $actorType, ?string $actorId, string $action, ?string $target = null, ?array $metadata = null, ?string $ip = null): int
    {
        $data = [
            'actor_type' => $actorType,
            'actor_id'   => $actorId,
            'action'     => $action,
            'target'     => $target,
            'ip_address' => $ip ?? service('request')->getIPAddress(),
            'metadata'   => $metadata ? json_encode($metadata) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return $this->insert($data);
    }
}

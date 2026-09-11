<?php

namespace App\Models;

use CodeIgniter\Model;

class SmsGatewayModel extends Model
{
    protected $table            = 'sms_gateways';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'device_id',
        'device_name',
        'token_hash',
        'status',
        'sim_operator',
        'sim_slot',
        'phone_number',
        'battery_level',
        'signal_strength',
        'is_charging',
        'app_version',
        'rate_limit_per_minute',
        'rate_limit_per_hour',
        'rate_limit_per_day',
        'last_seen_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    /**
     * Hash token for secure storage
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Find active gateway by plain token
     */
    public function findByToken(string $plainToken): ?array
    {
        $hash = self::hashToken($plainToken);
        $gateway = $this->where('token_hash', $hash)->first();

        if (!$gateway) {
            return null;
        }

        return $gateway;
    }

    /**
     * Find gateway by device_id
     */
    public function findByDeviceId(string $deviceId): ?array
    {
        return $this->where('device_id', $deviceId)->first();
    }

    /**
     * Update heartbeat metrics and set ONLINE if not DISABLED
     */
    public function recordHeartbeat(string $deviceId, array $metrics = []): bool
    {
        $gateway = $this->findByDeviceId($deviceId);
        if (!$gateway) {
            return false;
        }

        $data = [
            'last_seen_at'    => date('Y-m-d H:i:s'),
            'battery_level'   => isset($metrics['battery_level']) ? (int)$metrics['battery_level'] : $gateway['battery_level'],
            'signal_strength' => isset($metrics['signal_strength']) ? (int)$metrics['signal_strength'] : $gateway['signal_strength'],
            'is_charging'     => isset($metrics['is_charging']) ? ((bool)$metrics['is_charging'] ? 1 : 0) : $gateway['is_charging'],
            'sim_operator'    => $metrics['sim_operator'] ?? $gateway['sim_operator'],
            'phone_number'    => $metrics['phone_number'] ?? $gateway['phone_number'],
            'app_version'     => $metrics['app_version'] ?? $gateway['app_version'],
        ];

        if ($gateway['status'] !== 'DISABLED') {
            $data['status'] = 'ONLINE';
        }

        return $this->update($gateway['id'], $data);
    }

    /**
     * Check if rate limit is exceeded for this gateway
     */
    public function isRateLimitExceeded(array $gateway): bool
    {
        $db = \Config\Database::connect();
        $deviceId = $gateway['device_id'];

        // Check 1-minute limit
        $oneMinAgo = date('Y-m-d H:i:s', strtotime('-1 minute'));
        $countMin = $db->table('sms_jobs')
            ->where('assigned_device_id', $deviceId)
            ->where('sent_at >=', $oneMinAgo)
            ->countAllResults();

        if ($countMin >= ($gateway['rate_limit_per_minute'] ?? 30)) {
            return true;
        }

        // Check 1-hour limit
        $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $countHour = $db->table('sms_jobs')
            ->where('assigned_device_id', $deviceId)
            ->where('sent_at >=', $oneHourAgo)
            ->countAllResults();

        if ($countHour >= ($gateway['rate_limit_per_hour'] ?? 500)) {
            return true;
        }

        // Check 1-day limit
        $oneDayAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));
        $countDay = $db->table('sms_jobs')
            ->where('assigned_device_id', $deviceId)
            ->where('sent_at >=', $oneDayAgo)
            ->countAllResults();

        if ($countDay >= ($gateway['rate_limit_per_day'] ?? 5000)) {
            return true;
        }

        return false;
    }

    /**
     * Revoke device token
     */
    public function revokeToken(string $deviceId): bool
    {
        $gateway = $this->findByDeviceId($deviceId);
        if (!$gateway) {
            return false;
        }

        return $this->update($gateway['id'], [
            'token_hash' => null,
            'status'     => 'OFFLINE',
        ]);
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

class SmsPairingCodeModel extends Model
{
    protected $table            = 'sms_pairing_codes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'code',
        'device_name',
        'expires_at',
        'is_used',
        'used_by_device_id',
        'used_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Generate a new unique one-time pairing code
     */
    public function generateCode(?string $deviceName = null, int $expiryMinutes = 15): array
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($this->where('code', $code)->where('is_used', 0)->first());

        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));

        $data = [
            'code'        => $code,
            'device_name' => $deviceName,
            'expires_at'  => $expiresAt,
            'is_used'     => 0,
        ];

        $id = $this->insert($data);
        $data['id'] = $id;

        return $data;
    }

    /**
     * Validate pairing code
     */
    public function validateCode(string $code): ?array
    {
        $code = trim(strtoupper($code));
        $now = date('Y-m-d H:i:s');

        return $this->where('code', $code)
            ->where('is_used', 0)
            ->where('expires_at >=', $now)
            ->first();
    }

    /**
     * Mark code as used
     */
    public function markAsUsed(int $id, string $deviceId): bool
    {
        return $this->update($id, [
            'is_used'            => 1,
            'used_by_device_id'  => $deviceId,
            'used_at'            => date('Y-m-d H:i:s'),
        ]);
    }
}

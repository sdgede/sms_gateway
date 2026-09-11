<?php

namespace App\Models;

use CodeIgniter\Model;

class SmsPhoneLineModel extends Model
{
    protected $table            = 'sms_phone_lines';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'user_id',
        'fcm_token',
        'phone_number',
        'sim',
        'device_id',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Upsert phone line mapping with FCM Token
     */
    public function registerLine(string $phoneNumber, string $sim, string $fcmToken, ?string $userId = 'usr_default_admin'): array
    {
        $existing = $this->where('phone_number', $phoneNumber)
            ->where('sim', $sim)
            ->first();

        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $this->update($existing['id'], [
                'fcm_token'  => $fcmToken,
                'user_id'    => $userId,
                'is_active'  => 1,
                'updated_at' => $now,
            ]);
            $existing['fcm_token'] = $fcmToken;
            return $existing;
        }

        $id = 'phone_' . substr(md5($phoneNumber . '_' . $sim . '_' . time()), 0, 16);
        $data = [
            'id'           => $id,
            'user_id'      => $userId,
            'fcm_token'    => $fcmToken,
            'phone_number' => $phoneNumber,
            'sim'          => $sim,
            'is_active'    => 1,
            'created_at'   => $now,
            'updated_at'   => $now,
        ];

        $this->insert($data);

        return $data;
    }

    /**
     * Find active FCM token by phone number and SIM slot
     */
    public function findTokenByPhoneAndSim(string $phoneNumber, ?string $sim = null): ?string
    {
        $builder = $this->where('phone_number', $phoneNumber)->where('is_active', 1);
        if (!empty($sim)) {
            $builder->where('sim', $sim);
        }
        $line = $builder->orderBy('updated_at', 'DESC')->first();

        return $line['fcm_token'] ?? null;
    }

    /**
     * Get any active FCM token for fallback
     */
    public function getAnyActiveToken(): ?array
    {
        return $this->where('is_active', 1)
            ->where('fcm_token IS NOT NULL')
            ->orderBy('updated_at', 'DESC')
            ->first();
    }
}

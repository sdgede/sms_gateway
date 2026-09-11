<?php

namespace App\Models;

use CodeIgniter\Model;

class SmsJobModel extends Model
{
    protected $table            = 'sms_jobs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'job_id',
        'client_message_id',
        'recipient',
        'message',
        'status',
        'priority',
        'attempt',
        'max_attempt',
        'assigned_device_id',
        'available_at',
        'claimed_at',
        'claim_expires_at',
        'sent_at',
        'delivered_at',
        'failed_reason',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public const STATUS_PENDING          = 'PENDING';
    public const STATUS_CLAIMED          = 'CLAIMED';
    public const STATUS_SENDING          = 'SENDING';
    public const STATUS_SENT             = 'SENT';
    public const STATUS_DELIVERED        = 'DELIVERED';
    public const STATUS_FAILED           = 'FAILED';
    public const STATUS_RETRY            = 'RETRY';
    public const STATUS_FAILED_PERMANENT = 'FAILED_PERMANENT';

    /**
     * Create or retrieve idempotent SMS Job
     */
    public function createJob(array $payload): array
    {
        // 1. Idempotency Check
        if (!empty($payload['client_message_id'])) {
            $existing = $this->where('client_message_id', $payload['client_message_id'])->first();
            if ($existing) {
                return [
                    'job'       => $existing,
                    'is_replay' => true,
                ];
            }
        }

        // 2. Generate unique job_id: SMS-YYYYMMDD-XXXXXX
        $jobId = 'SMS-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        // Format clean phone number
        $recipient = $this->cleanPhoneNumber($payload['recipient']);

        $data = [
            'job_id'            => $jobId,
            'client_message_id' => $payload['client_message_id'] ?? null,
            'recipient'         => $recipient,
            'message'           => $payload['message'],
            'status'            => self::STATUS_PENDING,
            'priority'          => isset($payload['priority']) ? (int)$payload['priority'] : 2,
            'attempt'           => 0,
            'max_attempt'       => isset($payload['max_attempt']) ? (int)$payload['max_attempt'] : 3,
            'available_at'      => date('Y-m-d H:i:s'),
        ];

        $id = $this->insert($data);
        $data['id'] = $id;

        // Broadcast to WebSocket server if running
        try {
            \App\Libraries\WebSocketBroadcaster::broadcastNewJob($data);
        } catch (\Throwable $e) {
            // Non-blocking
        }

        return [
            'job'       => $data,
            'is_replay' => false,
        ];
    }

    /**
     * Find job by job_id or client_message_id
     */
    public function findByJobId(string $jobId): ?array
    {
        return $this->where('job_id', $jobId)->first();
    }

    public function findByIdOrClientId(string $idOrClientId): ?array
    {
        return $this->where('job_id', $idOrClientId)
            ->orWhere('client_message_id', $idOrClientId)
            ->first();
    }

    /**
     * Fetch next available pending job(s)
     */
    public function getNextAvailableJobs(int $limit = 5): array
    {
        // Auto process any ready retries and stale claims on-the-fly
        $this->processRetries();
        $this->recoverStaleClaims();

        $now = date('Y-m-d H:i:s');

        return $this->where('status', self::STATUS_PENDING)
            ->where('available_at <=', $now)
            ->orderBy('priority', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll($limit);
    }

    /**
     * Atomically claim a job by device
     */
    public function claimJob(string $jobId, string $deviceId, int $lockTimeoutSeconds = 60): bool
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $claimExpiresAt = date('Y-m-d H:i:s', time() + $lockTimeoutSeconds);

        // Atomic conditional update
        $builder = $db->table($this->table);
        $builder->where('job_id', $jobId)
            ->groupStart()
                ->where('status', self::STATUS_PENDING)
                ->orGroupStart()
                    ->where('status', self::STATUS_CLAIMED)
                    ->where('claim_expires_at <', $now)
                ->groupEnd()
            ->groupEnd();

        $builder->update([
            'status'             => self::STATUS_CLAIMED,
            'assigned_device_id' => $deviceId,
            'claimed_at'         => $now,
            'claim_expires_at'   => $claimExpiresAt,
            'updated_at'         => $now,
        ]);

        return $db->affectedRows() > 0;
    }

    /**
     * Mark job status as SENDING
     */
    public function markAsSending(string $jobId, string $deviceId): bool
    {
        $job = $this->findByJobId($jobId);
        if (!$job || $job['assigned_device_id'] !== $deviceId) {
            return false;
        }

        return $this->update($job['id'], [
            'status'     => self::STATUS_SENDING,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark job status as SENT
     */
    public function markAsSent(string $jobId, string $deviceId): bool
    {
        $job = $this->findByJobId($jobId);
        if (!$job) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        return $this->update($job['id'], [
            'status'             => self::STATUS_SENT,
            'sent_at'            => $now,
            'assigned_device_id' => $deviceId,
            'updated_at'         => $now,
        ]);
    }

    /**
     * Mark job status as DELIVERED
     */
    public function markAsDelivered(string $jobId, string $deviceId): bool
    {
        $job = $this->findByJobId($jobId);
        if (!$job) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        return $this->update($job['id'], [
            'status'       => self::STATUS_DELIVERED,
            'delivered_at' => $now,
            'updated_at'   => $now,
        ]);
    }

    /**
     * Handle job failure and evaluate retry backoff schedule
     */
    public function markAsFailed(string $jobId, string $deviceId, string $reason, bool $isRecoverable = true): array
    {
        $job = $this->findByJobId($jobId);
        if (!$job) {
            return ['success' => false, 'error' => 'Job not found'];
        }

        $now = date('Y-m-d H:i:s');
        $attempt = (int)$job['attempt'] + 1;
        $maxAttempt = (int)$job['max_attempt'];

        if (!$isRecoverable || $attempt >= $maxAttempt) {
            // Permanent failure
            $this->update($job['id'], [
                'status'        => self::STATUS_FAILED_PERMANENT,
                'attempt'       => $attempt,
                'failed_reason' => $reason,
                'updated_at'    => $now,
            ]);

            return [
                'success' => true,
                'status'  => self::STATUS_FAILED_PERMANENT,
                'attempt' => $attempt,
            ];
        }

        // Recoverable retry backoff schedule
        // Attempt 1: 30s, Attempt 2: 5m (300s), Attempt 3: 15m (900s)
        $delays = [
            1 => 30,
            2 => 300,
            3 => 900,
        ];
        $delaySeconds = $delays[$attempt] ?? 900;
        $availableAt = date('Y-m-d H:i:s', time() + $delaySeconds);

        $this->update($job['id'], [
            'status'             => self::STATUS_RETRY,
            'attempt'            => $attempt,
            'available_at'       => $availableAt,
            'assigned_device_id' => null,
            'failed_reason'      => $reason,
            'updated_at'         => $now,
        ]);

        return [
            'success'      => true,
            'status'       => self::STATUS_RETRY,
            'attempt'      => $attempt,
            'available_at' => $availableAt,
            'delay_secs'   => $delaySeconds,
        ];
    }

    /**
     * Recover stuck/stale claimed jobs when a gateway goes offline
     */
    public function recoverStaleClaims(): int
    {
        $now = date('Y-m-d H:i:s');
        $db = \Config\Database::connect();

        $builder = $db->table($this->table);
        $builder->where('status', self::STATUS_CLAIMED)
            ->where('claim_expires_at <', $now)
            ->update([
                'status'             => self::STATUS_PENDING,
                'assigned_device_id' => null,
                'claimed_at'         => null,
                'claim_expires_at'   => null,
                'updated_at'         => $now,
            ]);

        return $db->affectedRows();
    }

    /**
     * Process retry queue: move RETRY with available_at <= NOW back to PENDING
     */
    public function processRetries(): int
    {
        $now = date('Y-m-d H:i:s');
        $db = \Config\Database::connect();

        $builder = $db->table($this->table);
        $builder->where('status', self::STATUS_RETRY)
            ->where('available_at <=', $now)
            ->update([
                'status'     => self::STATUS_PENDING,
                'updated_at' => $now,
            ]);

        return $db->affectedRows();
    }

    /**
     * Get aggregate statistics
     */
    public function getStatistics(): array
    {
        $db = \Config\Database::connect();
        $counts = $db->table($this->table)
            ->select('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $stats = [
            'total'            => 0,
            'pending'          => 0,
            'claimed'          => 0,
            'sending'          => 0,
            'sent'             => 0,
            'delivered'        => 0,
            'failed'           => 0,
            'retry'            => 0,
            'failed_permanent' => 0,
        ];

        foreach ($counts as $row) {
            $key = strtolower($row['status']);
            $count = (int)$row['total'];
            $stats[$key] = $count;
            $stats['total'] += $count;
        }

        return $stats;
    }

    /**
     * Clean phone number format
     */
    private function cleanPhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9+]/', '', trim($phone));
        if (str_starts_with($cleaned, '08')) {
            return '+62' . substr($cleaned, 1);
        }
        if (str_starts_with($cleaned, '628')) {
            return '+' . $cleaned;
        }
        return $cleaned;
    }
}

<?php

namespace App\Libraries;

use App\Models\SmsPhoneLineModel;

class SmsDispatcher
{
    /**
     * Parse boolean value flexibly from env (handles true/false/1/0/TURE/yes/etc)
     */
    private static function parseBoolEnv(array $keys, bool $default = false): bool
    {
        foreach ($keys as $k) {
            $val = env($k);
            if ($val !== null && $val !== '') {
                if (is_bool($val)) {
                    return $val;
                }
                $str = strtolower(trim((string)$val));
                if (in_array($str, ['true', 'ture', '1', 'yes', 'on', 'enable', 'enabled'], true)) {
                    return true;
                }
                if (in_array($str, ['false', 'flase', '0', 'no', 'off', 'disable', 'disabled'], true)) {
                    return false;
                }
            }
        }
        return $default;
    }

    /**
     * Get active dispatch methods configured via .env
     * 
     * Supported .env variables:
     * - USE_FIREBASE = true/false (or USE_FIREBSSE, fcm.enabled)
     * - USE_SSE      = true/false (or USE_SSI, USER_SSI, sse.enabled)
     * - USE_WEBSOCKET= true/false (or USE_WEBSOKET, websocket.enabled)
     */
    public static function getActiveMethods(): array
    {
        $useFirebase = self::parseBoolEnv(['USE_FIREBASE', 'USE_FIREBSSE', 'fcm.enabled', 'USE_FCM'], true);
        $useSse = self::parseBoolEnv(['USE_SSE', 'USE_SSI', 'USER_SSI', 'sse.enabled'], false);
        $useWebSocket = self::parseBoolEnv(['USE_WEBSOCKET', 'USE_WEBSOKET', 'websocket.enabled', 'USE_WS'], false);

        // If all are false, default to Firebase as primary
        if (!$useFirebase && !$useSse && !$useWebSocket) {
            $useFirebase = true;
        }

        return [
            'firebase'  => $useFirebase,
            'sse'       => $useSse,
            'websocket' => $useWebSocket,
        ];
    }

    /**
     * Get primary active method label for UI
     */
    public static function getActiveMethodLabels(): array
    {
        $methods = self::getActiveMethods();
        $labels = [];

        if ($methods['firebase']) {
            $labels[] = [
                'id'    => 'firebase',
                'name'  => 'Firebase FCM (HTTP v1)',
                'badge' => 'USE_FIREBASE=true',
            ];
        }
        if ($methods['sse']) {
            $labels[] = [
                'id'    => 'sse',
                'name'  => 'Server-Sent Events (SSE)',
                'badge' => 'USE_SSE=true',
            ];
        }
        if ($methods['websocket']) {
            $labels[] = [
                'id'    => 'websocket',
                'name'  => 'WebSocket Real-Time',
                'badge' => 'USE_WEBSOCKET=true',
            ];
        }

        return $labels;
    }

    /**
     * Dispatch SMS Job to Android using all active methods
     */
    public static function dispatchJob(array $job): array
    {
        $methods = self::getActiveMethods();
        $dispatched = [
            'firebase'  => false,
            'sse'       => false,
            'websocket' => false,
        ];

        $jobId = $job['job_id'] ?? '';

        // 1. Firebase FCM Push (HTTP v1)
        if ($methods['firebase']) {
            try {
                $phoneLineModel = new SmsPhoneLineModel();
                $lines = $phoneLineModel->where('is_active', 1)->where('fcm_token IS NOT NULL')->findAll();
                if (empty($lines)) {
                    $anyLine = $phoneLineModel->getAnyActiveToken();
                    if ($anyLine) {
                        $lines = [$anyLine];
                    }
                }

                if (!empty($lines)) {
                    log_message('info', "[SmsDispatcher] [Method: Firebase] Triggering FCM push for Job {$jobId} to " . count($lines) . " line(s)...");
                    foreach ($lines as $line) {
                        if (!empty($line['fcm_token'])) {
                            log_message('info', "[SmsDispatcher] [Method: Firebase] Pushing to line {$line['phone_number']} (FCM: " . substr($line['fcm_token'], 0, 20) . "...)");
                            FcmService::pushMessage($line['fcm_token'], $jobId);
                        }
                    }
                    $dispatched['firebase'] = true;
                } else {
                    log_message('warning', "[SmsDispatcher] [Method: Firebase] No active FCM line found for Job {$jobId}. Device needs pairing.");
                }
            } catch (\Throwable $e) {
                log_message('error', "[SmsDispatcher] [Method: Firebase] Failed: " . $e->getMessage());
            }
        }

        // 2. Server-Sent Events (SSE Stream)
        if ($methods['sse']) {
            try {
                log_message('info', "[SmsDispatcher] [Method: SSE] Job {$jobId} is ready for live SSE Stream pick-up at /api/v1/gateway/jobs/stream.");
                $dispatched['sse'] = true;
            } catch (\Throwable $e) {
                log_message('error', "[SmsDispatcher] [Method: SSE] Failed: " . $e->getMessage());
            }
        }

        // 3. WebSocket Broadcast
        if ($methods['websocket']) {
            try {
                $sent = WebSocketBroadcaster::broadcastNewJob($job);
                log_message('info', "[SmsDispatcher] [Method: WebSocket] Job {$jobId} broadcasted to WebSocket daemon (status: " . ($sent ? 'Sent' : 'Daemon Offline') . ").");
                $dispatched['websocket'] = $sent;
            } catch (\Throwable $e) {
                log_message('error', "[SmsDispatcher] [Method: WebSocket] Failed: " . $e->getMessage());
            }
        }

        return $dispatched;
    }
}

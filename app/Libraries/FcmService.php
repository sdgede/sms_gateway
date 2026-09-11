<?php

namespace App\Libraries;

class FcmService
{
    /**
     * Get Firebase FCM configuration status
     */
    public static function getConfigStatus(): array
    {
        $envFile = env('fcm.credentialsFile');
        $possiblePaths = [
            $envFile,
            !empty($envFile) ? ROOTPATH . ltrim($envFile, '/') : null,
            WRITEPATH . 'firebase/service-account.json',
            ROOTPATH . 'writable/firebase/service-account.json',
        ];

        foreach ($possiblePaths as $p) {
            if (!empty($p) && file_exists($p)) {
                $json = @file_get_contents($p);
                $creds = json_decode($json, true);
                if ($creds && !empty($creds['project_id'])) {
                    return [
                        'configured' => true,
                        'mode'       => 'HTTP_V1',
                        'project_id' => $creds['project_id'],
                        'client_email' => $creds['client_email'] ?? '',
                        'file'       => basename($p),
                    ];
                }
            }
        }

        $serverKey = env('fcm.serverKey', '');
        if (!empty($serverKey)) {
            return [
                'configured' => true,
                'mode'       => 'LEGACY',
                'project_id' => null,
                'client_email' => null,
                'file'       => 'Server Key (Legacy)',
            ];
        }

        return [
            'configured' => false,
            'mode'       => 'NONE',
            'project_id' => null,
            'client_email' => null,
            'file'       => null,
        ];
    }

    /**
     * Send data-only FCM push to trigger Android worker for a new message
     */
    public static function pushMessage(string $fcmToken, string $messageId): bool
    {
        return self::sendDataNotification($fcmToken, [
            'KEY_MESSAGE_ID' => $messageId,
            'message_id'     => $messageId,
            'id'             => $messageId,
        ]);
    }

    /**
     * Send data-only FCM push to trigger Android heartbeat
     */
    public static function pushHeartbeat(string $fcmToken, ?string $heartbeatId = null): bool
    {
        return self::sendDataNotification($fcmToken, [
            'KEY_HEARTBEAT_ID' => $heartbeatId ?? ('hb_' . time()),
        ]);
    }

    /**
     * Internal FCM HTTP dispatcher (Supports both HTTP v1 Service Account and Legacy Server Key)
     */
    private static function sendDataNotification(string $fcmToken, array $dataPayload): bool
    {
        // 1. Try Firebase HTTP v1 (service-account.json)
        $envFile = env('fcm.credentialsFile');
        $possiblePaths = [
            $envFile,
            !empty($envFile) ? ROOTPATH . ltrim($envFile, '/') : null,
            WRITEPATH . 'firebase/service-account.json',
            ROOTPATH . 'writable/firebase/service-account.json',
        ];

        $credentialsFile = null;
        foreach ($possiblePaths as $p) {
            if (!empty($p) && file_exists($p)) {
                $credentialsFile = $p;
                break;
            }
        }

        if ($credentialsFile) {
            return self::sendHttpV1($credentialsFile, $fcmToken, $dataPayload);
        }

        // 2. Fallback to Firebase Legacy Server Key
        $serverKey = env('fcm.serverKey', '');
        if (!empty($serverKey)) {
            return self::sendLegacy($serverKey, $fcmToken, $dataPayload);
        }

        log_message('warning', '[FCM] No Firebase credentials configured (neither service-account.json nor fcm.serverKey).');
        return false;
    }

    /**
     * Firebase Cloud Messaging HTTP v1 API
     */
    private static function sendHttpV1(string $credentialsPath, string $fcmToken, array $dataPayload): bool
    {
        $json = @file_get_contents($credentialsPath);
        $creds = json_decode($json, true);
        if (!$creds || empty($creds['client_email']) || empty($creds['private_key']) || empty($creds['project_id'])) {
            log_message('error', "[FCM HTTP v1] Invalid service-account.json format at {$credentialsPath}.");
            return false;
        }

        $accessToken = self::getGoogleOAuthToken($creds);
        if (!$accessToken) {
            log_message('error', '[FCM HTTP v1] Failed to generate Google OAuth2 token using private key.');
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$creds['project_id']}/messages:send";

        // Convert all data payload values to string as required by FCM v1
        $stringData = array_map('strval', $dataPayload);

        $body = [
            'message' => [
                'token' => $fcmToken,
                'data'  => $stringData,
            ],
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json; UTF-8',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            log_message('error', "[FCM HTTP v1] Curl error to {$fcmToken}: {$curlError}");
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            log_message('info', "[FCM HTTP v1] SUCCESS push to token (" . substr($fcmToken, 0, 20) . "...) | Code: {$httpCode} | Payload: " . json_encode($stringData));
            return true;
        }

        log_message('error', "[FCM HTTP v1] FAILED push to token (" . substr($fcmToken, 0, 20) . "...) | HTTP Code: {$httpCode} | Response: {$response}");
        return false;
    }

    /**
     * Firebase Cloud Messaging Legacy API
     */
    private static function sendLegacy(string $serverKey, string $fcmToken, array $dataPayload): bool
    {
        $payload = [
            'to'       => $fcmToken,
            'data'     => $dataPayload,
            'priority' => 'high',
        ];

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            log_message('error', "[FCM Legacy] Curl error to {$fcmToken}: {$curlError}");
            return false;
        }

        if ($httpCode === 200) {
            log_message('info', "[FCM Legacy] SUCCESS push to token (" . substr($fcmToken, 0, 20) . "...) | Response: {$response}");
            return true;
        }

        log_message('error', "[FCM Legacy] FAILED push to token (" . substr($fcmToken, 0, 20) . "...) | HTTP Code: {$httpCode} | Response: {$response}");
        return false;
    }

    /**
     * Generate Google OAuth2 Bearer Access Token using Service Account Private Key (Pure PHP / RS256)
     */
    private static function getGoogleOAuthToken(array $creds): ?string
    {
        $now = time();
        $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = base64_encode(json_encode([
            'iss'   => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now,
        ]));

        // Sign with private key
        $signature = '';
        $dataToSign = $header . '.' . $claim;
        if (!openssl_sign($dataToSign, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
            log_message('error', '[FCM OAuth2] openssl_sign failed. Please check private_key in service-account.json.');
            return null;
        }

        $jwt = $dataToSign . '.' . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            log_message('error', "[FCM OAuth2] Token request curl error: {$curlError}");
            return null;
        }

        $tokenData = json_decode($res, true);
        if (empty($tokenData['access_token'])) {
            log_message('error', "[FCM OAuth2] Failed to get access_token. HTTP {$httpCode}: {$res}");
            return null;
        }

        return $tokenData['access_token'] ?? null;
    }
}

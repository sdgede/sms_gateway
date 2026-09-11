<?php

namespace App\Libraries;

class FcmService
{
    /**
     * Get Firebase FCM configuration status
     */
    /**
     * Get Firebase FCM configuration status
     */
    public static function getConfigStatus(): array
    {
        $creds = self::loadCredentials();
        if ($creds && !empty($creds['project_id'])) {
            return [
                'configured'   => true,
                'mode'         => 'HTTP_V1',
                'project_id'   => $creds['project_id'],
                'client_email' => $creds['client_email'] ?? '',
                'file'         => $creds['_source'] ?? 'service-account.json',
            ];
        }

        return [
            'configured'   => false,
            'mode'         => 'NONE',
            'project_id'   => null,
            'client_email' => null,
            'file'         => null,
        ];
    }

    /**
     * Helper to load service account credentials from files or env string
     */
    private static function loadCredentials(): ?array
    {
        // 1. Check direct JSON string in env (fcm.credentialsJson or fcm.serviceAccount)
        $rawEnvJson = env('fcm.credentialsJson') ?: env('fcm.serviceAccount');
        if (!empty($rawEnvJson)) {
            $decoded = json_decode($rawEnvJson, true);
            if (empty($decoded)) {
                // Try base64 decode if encoded
                $decoded = json_decode(base64_decode($rawEnvJson), true);
            }
            if ($decoded && !empty($decoded['project_id']) && !empty($decoded['private_key'])) {
                $decoded['_source'] = 'Environment (.env fcm.credentialsJson)';
                return $decoded;
            }
        }

        // 2. Search standard file locations
        $envFile = env('fcm.credentialsFile');
        $possiblePaths = array_filter([
            $envFile,
            !empty($envFile) ? ROOTPATH . ltrim($envFile, '/') : null,
            WRITEPATH . 'firebase/service-account.json',
            ROOTPATH . 'writable/firebase/service-account.json',
            WRITEPATH . 'service-account.json',
            ROOTPATH . 'service-account.json',
            APPPATH . 'Config/service-account.json',
        ]);

        foreach ($possiblePaths as $p) {
            if (file_exists($p)) {
                $json = @file_get_contents($p);
                $creds = json_decode($json, true);
                if ($creds && !empty($creds['project_id']) && !empty($creds['private_key'])) {
                    $creds['_source'] = basename($p);
                    $creds['_path'] = $p;
                    return $creds;
                }
            }
        }

        return null;
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
     * Internal FCM HTTP dispatcher (HTTP v1 Service Account)
     */
    private static function sendDataNotification(string $fcmToken, array $dataPayload): bool
    {
        $creds = self::loadCredentials();

        if ($creds) {
            return self::sendHttpV1WithCreds($creds, $fcmToken, $dataPayload);
        }

        log_message('error', '[FCM] No Google Firebase service-account.json found. Google disabled legacy FCM keys. Please upload service-account.json to writable/firebase/service-account.json');
        return false;
    }

    /**
     * Firebase Cloud Messaging HTTP v1 API
     */
    private static function sendHttpV1WithCreds(array $creds, string $fcmToken, array $dataPayload): bool
    {
        if (empty($creds['client_email']) || empty($creds['private_key']) || empty($creds['project_id'])) {
            log_message('error', '[FCM HTTP v1] Invalid service-account.json: missing client_email, private_key, or project_id.');
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

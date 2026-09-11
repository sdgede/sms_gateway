<?php

namespace App\Libraries;

class FcmService
{
    /**
     * Send data-only FCM push to trigger Android worker for a new message
     */
    public static function pushMessage(string $fcmToken, string $messageId): bool
    {
        return self::sendDataNotification($fcmToken, [
            'KEY_MESSAGE_ID' => $messageId,
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
     * Internal FCM HTTP dispatcher
     */
    private static function sendDataNotification(string $fcmToken, array $dataPayload): bool
    {
        $serverKey = env('fcm.serverKey', '');
        if (empty($serverKey)) {
            log_message('warning', '[FCM] FCM Server Key not configured in .env (fcm.serverKey). Skipping push.');
            return false;
        }

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
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            log_message('error', "[FCM] Curl error sending push: {$err}");
            return false;
        }

        log_message('info', "[FCM] Push dispatched to {$fcmToken} | HTTP Code: {$httpCode} | Response: {$response}");

        return $httpCode === 200;
    }
}

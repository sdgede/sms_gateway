<?php

namespace App\Helpers;

/**
 * SmsGatewayHelper
 * 
 * Helper class for external/existing CI4 applications (e.g. ws_mobile_kolektor, ws_mobnas)
 * to dispatch transactional SMS requests to the SMS Gateway Web Service.
 */
class SmsGatewayHelper
{
    /**
     * Get Base URL of SMS Gateway API
     */
    protected static function getGatewayBaseUrl(): string
    {
        return env('sms.gateway.url', 'http://127.0.0.1:8080');
    }

    /**
     * Get API Key for SMS Gateway API
     */
    protected static function getApiKey(): string
    {
        return env('sms.gateway.api_key', 'sms_secret_api_key_2026');
    }

    /**
     * Send SMS Message to Queue
     * 
     * @param array $payload [
     *   'recipient'         => '081234567890',  // Required
     *   'message'           => 'Text message',  // Required
     *   'client_message_id' => 'TRX-12345',     // Optional (for idempotency)
     *   'priority'          => 1,               // Optional (1=High/OTP, 2=Normal, 3=Low)
     *   'max_attempt'       => 3,               // Optional
     * ]
     * @return array [ 'status' => 'success'|'error', 'data' => [...], 'message' => '...' ]
     */
    public static function send(array $payload): array
    {
        $url = rtrim(self::getGatewayBaseUrl(), '/') . '/api/v1/sms/send';
        $apiKey = self::getApiKey();

        $client = \Config\Services::curlrequest([
            'timeout'     => 5,
            'http_errors' => false,
        ]);

        try {
            $response = $client->post($url, [
                'headers' => [
                    'X-API-Key'    => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => [
                    'recipient'         => $payload['recipient'] ?? '',
                    'message'           => $payload['message'] ?? '',
                    'client_message_id' => $payload['client_message_id'] ?? null,
                    'priority'          => $payload['priority'] ?? 2,
                    'max_attempt'       => $payload['max_attempt'] ?? 3,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true) ?? [];

            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'status'  => 'success',
                    'data'    => $body['data'] ?? [],
                    'message' => $body['message'] ?? 'SMS queued successfully',
                ];
            }

            return [
                'status'  => 'error',
                'code'    => $body['code'] ?? 'HTTP_' . $statusCode,
                'message' => $body['message'] ?? 'Failed to send SMS to gateway',
                'errors'  => $body['errors'] ?? null,
            ];
        } catch (\Throwable $e) {
            log_message('error', '[SmsGatewayHelper::send] Connection error: ' . $e->getMessage());
            return [
                'status'  => 'error',
                'code'    => 'CONNECTION_ERROR',
                'message' => 'Failed to connect to SMS Gateway: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check SMS Job Status
     */
    public static function getStatus(string $jobIdOrClientId): array
    {
        $url = rtrim(self::getGatewayBaseUrl(), '/') . '/api/v1/sms/status/' . urlencode($jobIdOrClientId);
        $apiKey = self::getApiKey();

        $client = \Config\Services::curlrequest([
            'timeout'     => 5,
            'http_errors' => false,
        ]);

        try {
            $response = $client->get($url, [
                'headers' => [
                    'X-API-Key' => $apiKey,
                    'Accept'    => 'application/json',
                ],
            ]);

            $body = json_decode($response->getBody(), true) ?? [];
            return $body;
        } catch (\Throwable $e) {
            return [
                'status'  => 'error',
                'message' => 'Connection error: ' . $e->getMessage(),
            ];
        }
    }
}

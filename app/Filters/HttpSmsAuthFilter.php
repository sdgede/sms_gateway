<?php

namespace App\Filters;

use App\Models\SmsGatewayModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class HttpSmsAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $expectedKey = env('app.smsApiKey', 'sms_secret_api_key_2026');

        // 1. Check x-api-key header (case insensitive)
        $providedKey = $request->getHeaderLine('x-api-key');
        if (empty($providedKey)) {
            $providedKey = $request->getHeaderLine('X-API-Key') ?: $request->getHeaderLine('X-Api-Key');
        }

        // 2. Check Bearer token in Authorization header
        if (empty($providedKey)) {
            $authHeader = $request->getHeaderLine('Authorization');
            if (empty($authHeader)) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
            }
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $providedKey = trim($matches[1]);
            } elseif (!empty($authHeader)) {
                $providedKey = trim($authHeader);
            }
        }

        // 3. Fallback to X-Device-Token or query parameter
        if (empty($providedKey)) {
            $providedKey = $request->getHeaderLine('X-Device-Token') ?: ($request->getGet('token') ?? '');
        }

        // If explicit key or token is provided, validate it
        if (!empty($providedKey)) {
            if (!empty($expectedKey) && hash_equals($expectedKey, $providedKey)) {
                return $request;
            }

            $gatewayModel = new SmsGatewayModel();
            $gateway = $gatewayModel->findByToken($providedKey);
            if ($gateway) {
                if ($gateway['status'] === 'DISABLED') {
                    $response = service('response');
                    return $response->setStatusCode(403)->setJSON([
                        'data'    => null,
                        'message' => 'This gateway device has been disabled.',
                        'status'  => 'error',
                    ]);
                }
                $request->authenticatedGateway = $gateway;
                return $request;
            }

            // If an explicit API key was provided but was completely wrong:
            log_message('warning', '[HttpSmsAuth] 401 Unauthorized: Invalid API key/token from IP: ' . $request->getIPAddress());
            $response = service('response');
            return $response->setStatusCode(401)->setJSON([
                'data'    => null,
                'message' => 'Cannot validate the API key or pairing token. Please check your credentials.',
                'status'  => 'error',
            ]);
        }

        // 4. Mobile Client Identification (Pairing Code / Device ID / App Payload)
        // Mobile setup connects via QR Code / Pairing Code without sending X-API-Key
        $deviceId = $request->getHeaderLine('X-Device-ID') ?: ($request->getGet('device_id') ?? '');
        $clientVersion = $request->getHeaderLine('X-Client-Version');

        $jsonBody = null;
        try {
            $jsonBody = $request->getJSON(true);
        } catch (\Throwable $e) {
            $jsonBody = null;
        }

        if (empty($deviceId) && is_array($jsonBody) && !empty($jsonBody['device_id'])) {
            $deviceId = (string)$jsonBody['device_id'];
        }

        if (!empty($deviceId)) {
            $gatewayModel = new SmsGatewayModel();
            $gateway = $gatewayModel->findByDeviceId($deviceId);
            if ($gateway && $gateway['status'] === 'DISABLED') {
                $response = service('response');
                return $response->setStatusCode(403)->setJSON([
                    'data'    => null,
                    'message' => 'This gateway device has been disabled.',
                    'status'  => 'error',
                ]);
            }
            if ($gateway) {
                $request->authenticatedGateway = $gateway;
            }
            return $request;
        }

        // Allow mobile client with X-Client-Version header
        if (!empty($clientVersion)) {
            return $request;
        }

        // Allow mobile payloads (heartbeats, fcm-token, events, incoming receive)
        if (is_array($jsonBody)) {
            if (
                isset($jsonBody['phone_numbers']) ||
                isset($jsonBody['fcm_token']) ||
                isset($jsonBody['event_name']) ||
                isset($jsonBody['from']) ||
                isset($jsonBody['battery_level']) ||
                isset($jsonBody['sim_carrier'])
            ) {
                return $request;
            }
        }

        // Allow outstanding message fetch by message_id
        if (!empty($request->getGet('message_id'))) {
            return $request;
        }

        log_message('warning', '[HttpSmsAuth] 401 Unauthorized: No valid credentials or device ID from IP: ' . $request->getIPAddress());
        $response = service('response');
        return $response->setStatusCode(401)->setJSON([
            'data'    => null,
            'message' => 'Cannot validate the device credentials. Please register or pair your device.',
            'status'  => 'error',
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}


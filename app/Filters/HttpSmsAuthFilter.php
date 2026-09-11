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

        if (empty($providedKey)) {
            log_message('warning', '[HttpSmsAuth] 401 Unauthorized: No token provided from IP: ' . $request->getIPAddress());
            $response = service('response');
            return $response->setStatusCode(401)->setJSON([
                'data'    => null,
                'message' => 'Cannot validate the API key or pairing token. Please check your credentials.',
                'status'  => 'error',
            ]);
        }

        // Check against global API key
        if (!empty($expectedKey) && hash_equals($expectedKey, $providedKey)) {
            return $request;
        }

        // Check if token belongs to a paired Gateway Device
        $gatewayModel = new SmsGatewayModel();
        $gateway = $gatewayModel->findByToken($providedKey);
        if ($gateway && $gateway['status'] !== 'DISABLED') {
            $request->authenticatedGateway = $gateway;
            return $request;
        }

        log_message('warning', '[HttpSmsAuth] 401 Unauthorized: Invalid key/token from IP: ' . $request->getIPAddress() . ' | Key: ' . substr($providedKey, 0, 8) . '...');
        $response = service('response');
        return $response->setStatusCode(401)->setJSON([
            'data'    => null,
            'message' => 'Cannot validate the API key or pairing token. Please check your credentials.',
            'status'  => 'error',
        ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}

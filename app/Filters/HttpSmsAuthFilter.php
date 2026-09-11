<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class HttpSmsAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $expectedKey = env('app.smsApiKey', 'sms_secret_api_key_2026');

        // Check x-api-key header (case insensitive)
        $providedKey = $request->getHeaderLine('x-api-key');
        if (empty($providedKey)) {
            $providedKey = $request->getHeaderLine('X-API-Key') ?: $request->getHeaderLine('X-Api-Key');
        }

        // Fallback to Bearer token
        if (empty($providedKey)) {
            $authHeader = $request->getHeaderLine('Authorization');
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $providedKey = trim($matches[1]);
            }
        }

        if (empty($providedKey) || !hash_equals($expectedKey, $providedKey)) {
            log_message('warning', '[HttpSmsAuth] 401 Unauthorized request from IP: ' . $request->getIPAddress() . ' | Key: ' . substr($providedKey, 0, 8) . '...');
            $response = service('response');
            $response->setStatusCode(401);
            $response->setJSON([
                'data'    => null,
                'message' => 'Cannot validate the API key. Please check your credentials.',
                'status'  => 'error',
            ]);
            return $response;
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed
    }
}

<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class InternalApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $expectedKey = env('app.smsApiKey', 'sms_secret_api_key_2026');
        $providedKey = $request->getHeaderLine('X-API-Key');

        // Also allow Bearer token if matched with API Key
        if (empty($providedKey)) {
            $authHeader = $request->getHeaderLine('Authorization');
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $providedKey = trim($matches[1]);
            }
        }

        if (empty($providedKey) || !hash_equals($expectedKey, $providedKey)) {
            $response = service('response');
            $response->setStatusCode(401);
            $response->setJSON([
                'status'  => 'error',
                'code'    => 'UNAUTHORIZED_API_CLIENT',
                'message' => 'Missing or invalid X-API-Key header.',
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

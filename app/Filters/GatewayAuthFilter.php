<?php

namespace App\Filters;

use App\Models\SmsGatewayModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class GatewayAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $plainToken = null;

        // 1. Check standard Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader)) {
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $plainToken = trim($matches[1]);
            } else {
                $plainToken = trim($authHeader);
            }
        }

        // 2. Check Apache / LiteSpeed server environment variables if header was stripped
        if (empty($plainToken)) {
            $serverAuth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
            if (!empty($serverAuth)) {
                if (preg_match('/Bearer\s+(.*)$/i', $serverAuth, $matches)) {
                    $plainToken = trim($matches[1]);
                } else {
                    $plainToken = trim($serverAuth);
                }
            }
        }

        // 3. Check X-Device-Token or X-Token header
        if (empty($plainToken)) {
            $devToken = $request->getHeaderLine('X-Device-Token') ?: $request->getHeaderLine('X-Token');
            if (!empty($devToken)) {
                $plainToken = trim($devToken);
            }
        }

        // 4. Fallback to query parameter or request body
        if (empty($plainToken)) {
            $plainToken = $request->getGet('token') ?: $request->getGet('device_token');
        }

        if (empty($plainToken)) {
            log_message('warning', '[GatewayAuth] Missing Authorization token from IP: ' . $request->getIPAddress() . ' | Path: ' . $request->getPath());
            $response = service('response');
            $response->setStatusCode(401);
            $response->setJSON([
                'status'  => 'error',
                'code'    => 'UNAUTHORIZED',
                'message' => 'Missing or invalid Authorization token. Expected Bearer token.',
            ]);
            return $response;
        }

        $gatewayModel = new SmsGatewayModel();
        $gateway = $gatewayModel->findByToken($plainToken);

        if (!$gateway) {
            log_message('warning', '[GatewayAuth] Invalid device token: ' . substr($plainToken, 0, 15) . '... from IP: ' . $request->getIPAddress());
            $response = service('response');
            $response->setStatusCode(401);
            $response->setJSON([
                'status'  => 'error',
                'code'    => 'INVALID_TOKEN',
                'message' => 'Invalid device token or token has been revoked.',
            ]);
            return $response;
        }

        if ($gateway['status'] === 'DISABLED') {
            $response = service('response');
            $response->setStatusCode(403);
            $response->setJSON([
                'status'  => 'error',
                'code'    => 'DEVICE_DISABLED',
                'message' => 'This gateway device has been disabled by administrator.',
            ]);
            return $response;
        }

        // Store authenticated gateway data in request for controllers to access
        $request->authenticatedGateway = $gateway;

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after request
    }
}

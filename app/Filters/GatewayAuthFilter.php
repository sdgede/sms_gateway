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
        $authHeader = $request->getHeaderLine('Authorization');
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            log_message('warning', '[GatewayAuth] Missing or invalid Authorization header from IP: ' . $request->getIPAddress() . ' | Header: ' . substr($authHeader, 0, 30));
            $response = service('response');
            $response->setStatusCode(401);
            $response->setJSON([
                'status'  => 'error',
                'code'    => 'UNAUTHORIZED',
                'message' => 'Missing or invalid Authorization header. Expected Bearer token.',
            ]);
            return $response;
        }

        $plainToken = trim($matches[1]);
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
        // CodeIgniter 4 allows binding custom properties on request or Services
        $request->authenticatedGateway = $gateway;

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after request
    }
}

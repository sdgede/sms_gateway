<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// --------------------------------------------------------------------
// Web Dashboard & Interactive Testing UI
// --------------------------------------------------------------------
$routes->get('/', 'Home::index');
$routes->get('web/data', 'Home::getLiveData');
$routes->post('web/pairing/generate', 'Home::generatePairing');
$routes->post('web/sms/send', 'Home::sendTestSms');
$routes->post('web/gateway/action', 'Home::gatewayAction');
$routes->post('web/worker/run', 'Home::runWorker');

// --------------------------------------------------------------------
// 1. API v1 - Internal SMS Dispatch (Protected by internal_api_auth / X-API-Key)
// --------------------------------------------------------------------
$routes->group('api/v1', ['filter' => 'internal_api_auth'], static function ($routes) {
    $routes->post('sms/send', 'Api\SmsApiController::send');
    $routes->get('sms/status/(:segment)', 'Api\SmsApiController::status/$1');
    $routes->get('sms/statistics', 'Api\SmsApiController::statistics');

    // Admin pairing code helper
    $routes->post('admin/pairing/generate', 'Api\GatewayApiController::generatePairingCode');
});

// --------------------------------------------------------------------
// 2. API v1 - Gateway Mobile Device API (with prefix api/v1/gateway)
// --------------------------------------------------------------------
$routes->post('api/v1/gateway/pair', 'Api\GatewayApiController::pair');

$routes->group('api/v1/gateway', ['filter' => 'gateway_auth'], static function ($routes) {
    $routes->post('heartbeat', 'Api\GatewayApiController::heartbeat');
    $routes->get('profile', 'Api\GatewayApiController::profile');
    $routes->post('revoke', 'Api\GatewayApiController::revoke');

    // SMS Job Queue operations
    $routes->get('jobs/next', 'Api\GatewayApiController::getNextJobs');
    $routes->post('jobs/(:segment)/claim', 'Api\GatewayApiController::claimJob/$1');
    $routes->post('jobs/(:segment)/start', 'Api\GatewayApiController::startJob/$1');
    $routes->post('jobs/(:segment)/report', 'Api\GatewayApiController::reportStatus/$1');
});

// --------------------------------------------------------------------
// 3. Alias Gateway Routes (Direct /gateway/... for shorter URLs)
// --------------------------------------------------------------------
$routes->post('gateway/pair', 'Api\GatewayApiController::pair');

$routes->group('gateway', ['filter' => 'gateway_auth'], static function ($routes) {
    $routes->post('heartbeat', 'Api\GatewayApiController::heartbeat');
    $routes->get('profile', 'Api\GatewayApiController::profile');
    $routes->post('revoke', 'Api\GatewayApiController::revoke');

    $routes->get('jobs/next', 'Api\GatewayApiController::getNextJobs');
    $routes->post('jobs/(:segment)/claim', 'Api\GatewayApiController::claimJob/$1');
    $routes->post('jobs/(:segment)/start', 'Api\GatewayApiController::startJob/$1');
    $routes->post('jobs/(:segment)/report', 'Api\GatewayApiController::reportStatus/$1');
});

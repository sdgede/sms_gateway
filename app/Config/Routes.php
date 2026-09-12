<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ====================================================================
// 1. WEB DASHBOARD & INTERACTIVE UI
// Namespace: App\Controllers\Web
// ====================================================================
$routes->get('/', 'Web\DashboardController::index');
$routes->get('web/data', 'Web\DashboardController::getLiveData');
$routes->post('web/pairing/generate', 'Web\DashboardController::generatePairing');
$routes->post('web/pairing/delete', 'Web\DashboardController::deletePairing');
$routes->post('web/sms/send', 'Web\DashboardController::sendTestSms');
$routes->post('web/sms/requeue', 'Web\DashboardController::requeueSms');
$routes->post('web/sms/delete', 'Web\DashboardController::deleteSms');
$routes->post('web/gateway/action', 'Web\DashboardController::gatewayAction');
$routes->post('web/phone-line/delete', 'Web\DashboardController::deletePhoneLine');
$routes->post('web/worker/run', 'Web\DashboardController::runWorker');

// ====================================================================
// 2. MOBILE ANDROID GATEWAY APP (com.httpsms compatible contract)
// Namespace: App\Controllers\Mobile
// Protected by httpsms_auth (Device ID / Pairing / Key)
// ====================================================================
$routes->group('v1', ['filter' => 'httpsms_auth'], static function ($routes) {
    $routes->put('phones/fcm-token', 'Mobile\MobileApiController::updateFcmToken');
    $routes->get('messages/outstanding', 'Mobile\MobileApiController::getOutstandingMessage');
    $routes->post('messages/(:segment)/events', 'Mobile\MobileApiController::recordMessageEvent/$1');
    $routes->post('heartbeats', 'Mobile\MobileApiController::recordHeartbeat');
    $routes->post('messages/receive', 'Mobile\MobileApiController::receiveMessage');
});

$routes->group('api/v1', ['filter' => 'httpsms_auth'], static function ($routes) {
    $routes->put('phones/fcm-token', 'Mobile\MobileApiController::updateFcmToken');
    $routes->get('messages/outstanding', 'Mobile\MobileApiController::getOutstandingMessage');
    $routes->post('messages/(:segment)/events', 'Mobile\MobileApiController::recordMessageEvent/$1');
    $routes->post('heartbeats', 'Mobile\MobileApiController::recordHeartbeat');
    $routes->post('messages/receive', 'Mobile\MobileApiController::receiveMessage');
});

// ====================================================================
// 3. INTERNAL SMS DISPATCH API (For Backend & Microservices)
// Namespace: App\Controllers\Internal
// Protected by internal_api_auth (X-API-Key)
// ====================================================================
$routes->group('api/v1', ['filter' => 'internal_api_auth'], static function ($routes) {
    $routes->post('sms/send', 'Internal\InternalSmsController::send');
    $routes->get('sms/status/(:segment)', 'Internal\InternalSmsController::status/$1');
    $routes->get('sms/statistics', 'Internal\InternalSmsController::statistics');

    // Admin pairing code helper
    $routes->post('admin/pairing/generate', 'Gateway\GatewayApiController::generatePairingCode');
});

// ====================================================================
// 4. DEDICATED HARDWARE & CUSTOM GATEWAY DEVICE API
// Namespace: App\Controllers\Gateway
// Protected by gateway_auth (Device Token)
// ====================================================================
$routes->post('api/v1/gateway/pair', 'Gateway\GatewayApiController::pair');

$routes->group('api/v1/gateway', ['filter' => 'gateway_auth'], static function ($routes) {
    $routes->match(['GET', 'POST'], 'heartbeat', 'Gateway\GatewayApiController::heartbeat');
    $routes->get('profile', 'Gateway\GatewayApiController::profile');
    $routes->post('revoke', 'Gateway\GatewayApiController::revoke');

    // SMS Job Queue operations (SSE stream & polling)
    $routes->get('jobs/stream', 'Gateway\GatewayApiController::streamJobs');
    $routes->match(['GET', 'POST'], 'jobs/next', 'Gateway\GatewayApiController::getNextJobs');
    $routes->match(['GET', 'POST'], 'jobs/poll', 'Gateway\GatewayApiController::getNextJobs');
    $routes->match(['GET', 'POST'], 'jobs/pending', 'Gateway\GatewayApiController::getNextJobs');

    // Claim, Start, Report
    $routes->post('jobs/(:segment)/claim', 'Gateway\GatewayApiController::claimJob/$1');
    $routes->post('jobs/claim', 'Gateway\GatewayApiController::claimJob');
    $routes->post('jobs/(:segment)/start', 'Gateway\GatewayApiController::startJob/$1');
    $routes->post('jobs/start', 'Gateway\GatewayApiController::startJob');
    $routes->post('jobs/(:segment)/report', 'Gateway\GatewayApiController::reportStatus/$1');
    $routes->post('jobs/report', 'Gateway\GatewayApiController::reportStatus');
});

// Alias Gateway Routes (Direct /gateway/... for shorter URLs)
$routes->post('gateway/pair', 'Gateway\GatewayApiController::pair');

$routes->group('gateway', ['filter' => 'gateway_auth'], static function ($routes) {
    $routes->match(['GET', 'POST'], 'heartbeat', 'Gateway\GatewayApiController::heartbeat');
    $routes->get('profile', 'Gateway\GatewayApiController::profile');
    $routes->post('revoke', 'Gateway\GatewayApiController::revoke');

    $routes->get('jobs/stream', 'Gateway\GatewayApiController::streamJobs');
    $routes->match(['GET', 'POST'], 'jobs/next', 'Gateway\GatewayApiController::getNextJobs');
    $routes->match(['GET', 'POST'], 'jobs/poll', 'Gateway\GatewayApiController::getNextJobs');
    $routes->match(['GET', 'POST'], 'jobs/pending', 'Gateway\GatewayApiController::getNextJobs');

    $routes->post('jobs/(:segment)/claim', 'Gateway\GatewayApiController::claimJob/$1');
    $routes->post('jobs/claim', 'Gateway\GatewayApiController::claimJob');
    $routes->post('jobs/(:segment)/start', 'Gateway\GatewayApiController::startJob/$1');
    $routes->post('jobs/start', 'Gateway\GatewayApiController::startJob');
    $routes->post('jobs/(:segment)/report', 'Gateway\GatewayApiController::reportStatus/$1');
    $routes->post('jobs/report', 'Gateway\GatewayApiController::reportStatus');
});

<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// --------------------------------------------------------------------
// Web Dashboard & Interactive Testing UI
// --------------------------------------------------------------------
$routes->get('/', 'Home::index');
$routes->get('web/data', 'Home::getLiveData');
$routes->post('web/pairing/generate', 'Home::generatePairing');
$routes->post('web/pairing/delete', 'Home::deletePairing');
$routes->post('web/sms/send', 'Home::sendTestSms');
$routes->post('web/sms/requeue', 'Home::requeueSms');
$routes->post('web/sms/delete', 'Home::deleteSms');
$routes->post('web/gateway/action', 'Home::gatewayAction');
$routes->post('web/phone-line/delete', 'Home::deletePhoneLine');
$routes->post('web/incoming/delete', 'Home::deleteIncoming');
$routes->post('web/worker/run', 'Home::runWorker');

// --------------------------------------------------------------------
// 0. com.httpsms Contract & Mobile Endpoints (/v1/... and /api/v1/...)
// Protected by httpsms_auth (Device ID / Pairing / Key)
// --------------------------------------------------------------------
$routes->group('v1', ['filter' => 'httpsms_auth'], static function ($routes) {
    $routes->put('phones/fcm-token', 'Api\HttpSmsController::updateFcmToken');
    $routes->get('messages/outstanding', 'Api\HttpSmsController::getOutstandingMessage');
    $routes->post('messages/(:segment)/events', 'Api\HttpSmsController::recordMessageEvent/$1');
    $routes->post('heartbeats', 'Api\HttpSmsController::recordHeartbeat');
    $routes->post('messages/receive', 'Api\HttpSmsController::receiveMessage');
});

$routes->group('api/v1', ['filter' => 'httpsms_auth'], static function ($routes) {
    // Mobile gateway endpoints
    $routes->put('phones/fcm-token', 'Api\HttpSmsController::updateFcmToken');
    $routes->get('messages/outstanding', 'Api\HttpSmsController::getOutstandingMessage');
    $routes->post('messages/(:segment)/events', 'Api\HttpSmsController::recordMessageEvent/$1');
    $routes->post('heartbeats', 'Api\HttpSmsController::recordHeartbeat');
    $routes->post('messages/receive', 'Api\HttpSmsController::receiveMessage');
});

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
    $routes->match(['get', 'post'], 'heartbeat', 'Api\GatewayApiController::heartbeat');
    $routes->get('profile', 'Api\GatewayApiController::profile');
    $routes->post('revoke', 'Api\GatewayApiController::revoke');

    // SMS Job Queue operations (Support GET & POST for next/poll & real-time stream)
    $routes->get('jobs/stream', 'Api\GatewayApiController::streamJobs');
    $routes->match(['get', 'post'], 'jobs/next', 'Api\GatewayApiController::getNextJobs');
    $routes->match(['get', 'post'], 'jobs/poll', 'Api\GatewayApiController::getNextJobs');
    $routes->match(['get', 'post'], 'jobs/pending', 'Api\GatewayApiController::getNextJobs');

    // Claim, Start, Report (Support both /jobs/{id}/claim and /jobs/claim)
    $routes->post('jobs/(:segment)/claim', 'Api\GatewayApiController::claimJob/$1');
    $routes->post('jobs/claim', 'Api\GatewayApiController::claimJob');
    $routes->post('jobs/(:segment)/start', 'Api\GatewayApiController::startJob/$1');
    $routes->post('jobs/start', 'Api\GatewayApiController::startJob');
    $routes->post('jobs/(:segment)/report', 'Api\GatewayApiController::reportStatus/$1');
    $routes->post('jobs/report', 'Api\GatewayApiController::reportStatus');
});

// --------------------------------------------------------------------
// 3. Alias Gateway Routes (Direct /gateway/... for shorter URLs)
// --------------------------------------------------------------------
$routes->post('gateway/pair', 'Api\GatewayApiController::pair');

$routes->group('gateway', ['filter' => 'gateway_auth'], static function ($routes) {
    $routes->match(['get', 'post'], 'heartbeat', 'Api\GatewayApiController::heartbeat');
    $routes->get('profile', 'Api\GatewayApiController::profile');
    $routes->post('revoke', 'Api\GatewayApiController::revoke');

    $routes->get('jobs/stream', 'Api\GatewayApiController::streamJobs');
    $routes->match(['get', 'post'], 'jobs/next', 'Api\GatewayApiController::getNextJobs');
    $routes->match(['get', 'post'], 'jobs/poll', 'Api\GatewayApiController::getNextJobs');
    $routes->match(['get', 'post'], 'jobs/pending', 'Api\GatewayApiController::getNextJobs');

    $routes->post('jobs/(:segment)/claim', 'Api\GatewayApiController::claimJob/$1');
    $routes->post('jobs/claim', 'Api\GatewayApiController::claimJob');
    $routes->post('jobs/(:segment)/start', 'Api\GatewayApiController::startJob/$1');
    $routes->post('jobs/start', 'Api\GatewayApiController::startJob');
    $routes->post('jobs/(:segment)/report', 'Api\GatewayApiController::reportStatus/$1');
    $routes->post('jobs/report', 'Api\GatewayApiController::reportStatus');
});

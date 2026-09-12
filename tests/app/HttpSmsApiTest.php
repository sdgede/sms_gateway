<?php

namespace Tests\App;

use App\Models\SmsJobModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class HttpSmsApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private string $apiKey = 'sms_secret_api_key_2026';

    /**
     * Test complete com.httpsms contract endpoints
     */
    public function testHttpSmsContractFlow(): void
    {
        // 1. Test Auth: 401 when x-api-key is invalid
        $badAuth = $this->withHeaders(['x-api-key' => 'wrong_key'])
            ->withBody(json_encode([
                'fcm_token'    => 'test_fcm_token_123',
                'phone_number' => '+60123456789',
                'sim'          => 'SIM1',
            ]))
            ->put('v1/phones/fcm-token');
        $badAuth->assertStatus(401);
        $badAuthJson = json_decode($badAuth->getJSON(), true);
        $this->assertEquals('error', $badAuthJson['status']);

        // 2. Test Login / FCM Token Registration: PUT /v1/phones/fcm-token
        $loginResult = $this->withHeaders(['x-api-key' => $this->apiKey])
            ->withBody(json_encode([
                'fcm_token'    => 'test_fcm_token_123456',
                'phone_number' => '+60123456789',
                'sim'          => 'SIM1',
            ]))
            ->put('v1/phones/fcm-token');
        $loginResult->assertStatus(200);
        $loginJson = json_decode($loginResult->getJSON(), true);
        $this->assertEquals('success', $loginJson['status']);
        $this->assertEquals('ok', $loginJson['message']);
        $this->assertNotEmpty($loginJson['data']['id']);
        $this->assertNotEmpty($loginJson['data']['user_id']);

        // 3. Create a test message in database
        $jobModel = new SmsJobModel();
        $jobResult = $jobModel->createJob([
            'recipient' => '+628123456789',
            'message'   => 'Test SMS content for FCM worker',
            'priority'  => 1,
        ]);
        $job = $jobResult['job'];
        $messageId = $job['job_id'];

        // 4. Test Fetch Outstanding Message: GET /v1/messages/outstanding?message_id=<id>
        $fetchResult = $this->withHeaders(['x-api-key' => $this->apiKey])
            ->get("v1/messages/outstanding?message_id={$messageId}");
        $fetchResult->assertStatus(200);
        $fetchJson = json_decode($fetchResult->getJSON(), true);
        $this->assertEquals('success', $fetchJson['status']);
        $this->assertEquals('ok', $fetchJson['message']);
        $msgData = $fetchJson['data'];
        $this->assertEquals($messageId, $msgData['id']);
        $this->assertEquals('+628123456789', $msgData['contact']);
        $this->assertEquals('Test SMS content for FCM worker', $msgData['content']);
        $this->assertEquals('SIM1', $msgData['sim']);
        $this->assertEquals('outstanding', $msgData['status']);
        $this->assertArrayHasKey('created_at', $msgData);

        // 5. Test Event Reporting: POST /v1/messages/{id}/events (SENT)
        $sentEvent = $this->withHeaders(['x-api-key' => $this->apiKey])
            ->withBody(json_encode([
                'event_name' => 'SENT',
                'timestamp'  => date('c'),
            ]))
            ->post("v1/messages/{$messageId}/events");
        $sentEvent->assertStatus(200);
        $sentJson = json_decode($sentEvent->getJSON(), true);
        $this->assertEquals('success', $sentJson['status']);

        // Verify status updated to SENT, attempt incremented, and device assigned
        $updatedJob = $jobModel->findByJobId($messageId);
        $this->assertEquals(SmsJobModel::STATUS_SENT, $updatedJob['status']);
        $this->assertGreaterThanOrEqual(1, (int)$updatedJob['attempt']);
        $this->assertNotEmpty($updatedJob['assigned_device_id']);

        // 6. Test Event Reporting: POST /v1/messages/{id}/events (DELIVERED)
        $delivEvent = $this->withHeaders(['x-api-key' => $this->apiKey])
            ->withBody(json_encode([
                'event_name' => 'DELIVERED',
                'timestamp'  => date('c'),
            ]))
            ->post("v1/messages/{$messageId}/events");
        $delivEvent->assertStatus(200);
        $delivJob = $jobModel->findByJobId($messageId);
        $this->assertEquals(SmsJobModel::STATUS_DELIVERED, $delivJob['status']);
        $this->assertGreaterThanOrEqual(1, (int)$delivJob['attempt']);
        $this->assertNotEmpty($delivJob['assigned_device_id']);

        // 7. Test Heartbeat: POST /v1/heartbeats
        $heartbeatResult = $this->withHeaders(['x-api-key' => $this->apiKey])
            ->withBody(json_encode([
                'device_id'                     => '3f9c-uuid-1234',
                'app_version'                   => '1.0.0',
                'timestamp'                     => time(),
                'sms_permission'                => true,
                'battery_optimization_disabled' => true,
                'battery_level'                 => 90,
                'is_charging'                   => true,
                'active_subscription_id'        => 1,
                'sim_carrier'                   => 'Telkomsel',
                'network_type'                  => 'WIFI',
                'phone_numbers'                 => ['+60123456789'],
            ]))
            ->post('v1/heartbeats');
        $heartbeatResult->assertStatus(200);
        $hbJson = json_decode($heartbeatResult->getJSON(), true);
        $this->assertEquals('success', $hbJson['status']);

        // 8. Test Incoming SMS Receive: POST /v1/messages/receive
        $receiveResult = $this->withHeaders(['x-api-key' => $this->apiKey])
            ->withBody(json_encode([
                'sim'         => 'SIM1',
                'from'        => '+628987654321',
                'to'          => '+60123456789',
                'content'     => 'Balasan SMS dari pelanggan',
                'encrypted'   => false,
                'timestamp'   => date('c'),
                'attachments' => null,
            ]))
            ->post('v1/messages/receive');
        $receiveResult->assertStatus(200);
        $recJson = json_decode($receiveResult->getJSON(), true);
        $this->assertEquals('success', $recJson['status']);

        // 9. Test Mobile Heartbeat to api/v1/heartbeats without X-API-Key (Android Client)
        $mobileHb = $this->withHeaders(['X-Client-Version' => '1.0.0'])
            ->withBody(json_encode([
                'device_id'                     => '700d6d4f-7725-4f2c-b0a9-bbe41ec4b4b1',
                'app_version'                   => '1.0.0',
                'timestamp'                     => 1789117867,
                'sms_permission'                => true,
                'battery_optimization_disabled' => true,
                'battery_level'                 => 100,
                'is_charging'                   => true,
                'active_subscription_id'        => 1,
                'sim_carrier'                   => 'TELKOMSEL',
                'network_type'                  => 'CELLULAR',
                'phone_numbers'                 => ['+6282147836034'],
            ]))
            ->post('api/v1/heartbeats');
        $mobileHb->assertStatus(200);
        $mobileHbJson = json_decode($mobileHb->getJSON(), true);
        $this->assertEquals('success', $mobileHbJson['status']);
    }
}

<?php

namespace Tests\App;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class SmsGatewayApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    private string $apiKey = 'sms_secret_api_key_2026';

    /**
     * Test Complete End-to-End SMS Gateway Flow
     */
    public function testCompleteGatewayLifecycle(): void
    {
        // 1. Generate Pairing Code via Admin Helper
        $genResult = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->post('api/v1/admin/pairing/generate', [
                'device_name'    => 'Xiaomi Gateway 01',
                'expiry_minutes' => 10,
            ]);

        $genResult->assertStatus(201);
        $genJson = json_decode($genResult->getJSON(), true);
        $this->assertEquals('success', $genJson['status']);
        $pairingCode = $genJson['data']['code'];
        $this->assertNotEmpty($pairingCode);

        // 2. Android Device Pairs using the code
        $deviceId = 'android-device-uuid-12345';
        $pairResult = $this->post('gateway/pair', [
            'pairing_code'  => $pairingCode,
            'device_id'     => $deviceId,
            'device_name'   => 'Xiaomi Gateway 01',
            'sim_operator'  => 'Telkomsel',
            'sim_slot'      => 1,
            'phone_number'  => '+6281234567890',
            'app_version'   => '1.0.0',
        ]);

        $pairResult->assertStatus(200);
        $pairJson = json_decode($pairResult->getJSON(), true);
        $this->assertEquals('success', $pairJson['status']);
        $deviceToken = $pairJson['data']['device_token'];
        $this->assertNotEmpty($deviceToken);

        // 3. Pairing Code cannot be reused
        $reuseResult = $this->post('gateway/pair', [
            'pairing_code' => $pairingCode,
            'device_id'    => 'another-device',
        ]);
        $reuseResult->assertStatus(400);

        // 4. Android Device sends Heartbeat
        $hbResult = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->post('gateway/heartbeat', [
                'battery_level'   => 95,
                'signal_strength' => 88,
                'is_charging'     => true,
            ]);
        $hbResult->assertStatus(200);

        // 5. Android Device checks Profile
        $profileResult = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->get('gateway/profile');
        $profileResult->assertStatus(200);
        $profileJson = json_decode($profileResult->getJSON(), true);
        $this->assertEquals('ONLINE', $profileJson['data']['status']);
        $this->assertEquals('Telkomsel', $profileJson['data']['sim_operator']);

        // 6. External App Queues a new SMS
        $clientMessageId = 'TRX-TEST-' . time();
        $sendResult = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->post('api/v1/sms/send', [
                'recipient'         => '081298765432',
                'message'           => 'Kode OTP anda adalah 123456',
                'client_message_id' => $clientMessageId,
                'priority'          => 1,
            ]);

        $sendResult->assertStatus(202);
        $sendJson = json_decode($sendResult->getJSON(), true);
        $this->assertEquals('success', $sendJson['status']);
        $jobId = $sendJson['data']['job_id'];
        $this->assertEquals('+6281298765432', $sendJson['data']['recipient']);
        $this->assertEquals('PENDING', $sendJson['data']['status']);

        // 7. Test Idempotency (replay same client_message_id)
        $replayResult = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->post('api/v1/sms/send', [
                'recipient'         => '081298765432',
                'message'           => 'Kode OTP anda adalah 123456',
                'client_message_id' => $clientMessageId,
                'priority'          => 1,
            ]);
        $replayResult->assertStatus(200);
        $replayJson = json_decode($replayResult->getJSON(), true);
        $this->assertTrue($replayJson['data']['is_replay']);
        $this->assertEquals($jobId, $replayJson['data']['job_id']);

        // 8. Android Gateway polls for next job
        $nextResult = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->get('gateway/jobs/next?limit=1');
        $nextResult->assertStatus(200);
        $nextJson = json_decode($nextResult->getJSON(), true);
        $this->assertNotEmpty($nextJson['data']);
        $this->assertEquals($jobId, $nextJson['data'][0]['job_id']);

        // 9. Android Gateway claims the job
        $claimResult = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->post("gateway/jobs/{$jobId}/claim");
        $claimResult->assertStatus(200);
        $claimJson = json_decode($claimResult->getJSON(), true);
        $this->assertEquals('CLAIMED', $claimJson['data']['status']);
        $this->assertEquals($deviceId, $claimJson['data']['assigned_device_id']);

        // 10. Another device cannot claim the same job (Locking)
        $conflictResult = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->post("gateway/jobs/{$jobId}/claim");
        $conflictResult->assertStatus(409);

        // 11. Android Gateway starts sending (dispatches to SmsManager)
        $startResult = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->post("gateway/jobs/{$jobId}/start");
        $startResult->assertStatus(200);

        // 12. Android Gateway reports SENT (Network Ack)
        $sentReportResult = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->post("gateway/jobs/{$jobId}/report", [
                'status'                  => 'SENT',
                'operator_status_code'    => 'RESULT_OK',
                'operator_status_message' => 'SMS handed over to cellular network',
            ]);
        $sentReportResult->assertStatus(200);

        // 13. Check SMS Status via API
        $statusResult = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->get("api/v1/sms/status/{$jobId}");
        $statusResult->assertStatus(200);
        $statusJson = json_decode($statusResult->getJSON(), true);
        $this->assertEquals('SENT', $statusJson['data']['status']);
        $this->assertNotEmpty($statusJson['data']['sent_at']);

        // 14. Android Gateway reports DELIVERED (Operator Delivery Report)
        $delivReportResult = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->post("gateway/jobs/{$jobId}/report", [
                'status'                  => 'DELIVERED',
                'operator_status_code'    => 'DELIVERY_OK',
                'operator_status_message' => 'Delivered to handset',
            ]);
        $delivReportResult->assertStatus(200);

        // 15. Check final DELIVERED status
        $finalStatus = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->get("api/v1/sms/status/{$jobId}");
        $finalJson = json_decode($finalStatus->getJSON(), true);
        $this->assertEquals('DELIVERED', $finalJson['data']['status']);
        $this->assertNotEmpty($finalJson['data']['delivered_at']);
        $this->assertCount(2, $finalJson['data']['delivery_reports']);

        // 16. Test Statistics Endpoint
        $statsResult = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->get('api/v1/sms/statistics');
        $statsResult->assertStatus(200);
        $statsJson = json_decode($statsResult->getJSON(), true);
        $this->assertEquals(1, $statsJson['data']['total']);
        $this->assertEquals(1, $statsJson['data']['delivered']);

        // 17. Test Token Revocation
        $revokeResult = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->post('gateway/revoke');
        $revokeResult->assertStatus(200);

        // Subsequent call with revoked token must fail with 401
        $unauthResult = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->get('gateway/profile');
        $unauthResult->assertStatus(401);
    }

    /**
     * Test Recoverable Failure and Retry Backoff Scheduling
     */
    public function testRetryBackoffWorkflow(): void
    {
        // 1. Setup Gateway
        $genResult = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->post('api/v1/admin/pairing/generate', ['device_name' => 'Gateway Retry Test']);
        $pairingCode = json_decode($genResult->getJSON(), true)['data']['code'];

        $pairResult = $this->post('gateway/pair', [
            'pairing_code' => $pairingCode,
            'device_id'    => 'retry-test-device',
        ]);
        $deviceToken = json_decode($pairResult->getJSON(), true)['data']['device_token'];

        // 2. Queue Job
        $sendResult = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->post('api/v1/sms/send', [
                'recipient'   => '081200000001',
                'message'     => 'Testing retry error',
                'max_attempt' => 3,
            ]);
        $jobId = json_decode($sendResult->getJSON(), true)['data']['job_id'];

        // 3. Claim Job
        $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->post("gateway/jobs/{$jobId}/claim");

        // 4. Report temporary failure (Recoverable error)
        $failResult = $this->withHeaders(['Authorization' => 'Bearer ' . $deviceToken])
            ->post("gateway/jobs/{$jobId}/report", [
                'status'                  => 'FAILED',
                'operator_status_code'    => 'RESULT_ERROR_RADIO_OFF',
                'operator_status_message' => 'No cellular service',
                'is_recoverable'          => true,
            ]);

        $failResult->assertStatus(200);
        $failJson = json_decode($failResult->getJSON(), true);
        $this->assertEquals('RETRY', $failJson['data']['status']);
        $this->assertEquals(1, $failJson['data']['attempt']);
        $this->assertEquals(30, $failJson['data']['delay_secs']); // 30s delay for attempt 1

        // Verify Status in Database
        $check = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->get("api/v1/sms/status/{$jobId}");
        $checkJson = json_decode($check->getJSON(), true);
        $this->assertEquals('RETRY', $checkJson['data']['status']);
        $this->assertEquals(1, $checkJson['data']['attempt']);
    }
}

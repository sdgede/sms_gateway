<?php

namespace Tests\App;

use App\Models\SmsJobModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class HomeDashboardTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = 'App';

    public function testDashboardRendersSuccessfully(): void
    {
        $result = $this->get('/');
        $result->assertStatus(200);
        $result->assertSee('Internal SMS Gateway Control Center');
    }

    public function testGetLiveDataReturnsJson(): void
    {
        $result = $this->get('web/data');
        $result->assertStatus(200);
        $result->assertJSONFragment(['status' => 'success']);
    }

    public function testGenerateAndDelPairingCode(): void
    {
        $gen = $this->withBody(json_encode(['device_name' => 'Test Unit Android']))
            ->post('web/pairing/generate');
        $gen->assertStatus(200);
        $genJson = json_decode($gen->getJSON(), true);
        $this->assertEquals('success', $genJson['status']);
        $this->assertNotEmpty($genJson['data']['code']);

        $codeId = $genJson['data']['id'];
        $del = $this->withBody(json_encode(['id' => $codeId]))
            ->post('web/pairing/delete');
        $del->assertStatus(200);
        $delJson = json_decode($del->getJSON(), true);
        $this->assertEquals('success', $delJson['status']);
    }

    public function testSendAndRequeueAndRunWorker(): void
    {
        // 1. Send test SMS
        $send = $this->withBody(json_encode([
            'recipient' => '+628991234567',
            'message'   => 'Dashboard test SMS content',
            'priority'  => 1,
        ]))->post('web/sms/send');
        $send->assertStatus(200);
        $sendJson = json_decode($send->getJSON(), true);
        $this->assertEquals('success', $sendJson['status']);
        $jobId = $sendJson['data']['job_id'];

        // 2. Requeue SMS
        $requeue = $this->withBody(json_encode(['job_id' => $jobId]))
            ->post('web/sms/requeue');
        $requeue->assertStatus(200);
        $requeueJson = json_decode($requeue->getJSON(), true);
        $this->assertEquals('success', $requeueJson['status']);

        // 3. Run worker
        $worker = $this->post('web/worker/run');
        $worker->assertStatus(200);
        $workerJson = json_decode($worker->getJSON(), true);
        $this->assertEquals('success', $workerJson['status']);

        // 4. Delete SMS
        $delete = $this->withBody(json_encode(['job_id' => $jobId]))
            ->post('web/sms/delete');
        $delete->assertStatus(200);
    }

    public function testBulkResendSmsWithInterval(): void
    {
        $jobModel = new SmsJobModel();
        $jobModel->createJob([
            'recipient' => '+628111111111',
            'message'   => 'P2P Test 1',
            'status'    => 'DELIVERED',
        ]);
        $jobModel->createJob([
            'recipient' => '+628111111111',
            'message'   => 'P2P Test 2',
            'status'    => 'SENT',
        ]);

        // Test bulk resend with clone_new mode and 3-second interval
        $res = $this->withBody(json_encode([
            'interval'  => 3,
            'mode'      => 'clone_new',
            'recipient' => '+628111111111',
            'limit'     => 10,
        ]))->post('web/sms/bulk-resend');

        $res->assertStatus(200);
        $resJson = json_decode($res->getJSON(), true);
        $this->assertEquals('success', $resJson['status']);
        $this->assertEquals(2, $resJson['data']['total']);
        $this->assertEquals(3, $resJson['data']['interval_seconds']);
        $this->assertEquals('clone_new', $resJson['data']['mode']);
        $this->assertEquals(3, $resJson['data']['estimated_seconds']);

        // Test bulk resend with reset_existing mode
        $resReset = $this->withBody(json_encode([
            'interval' => 5,
            'mode'     => 'reset_existing',
            'limit'    => 5,
        ]))->post('web/sms/bulk-resend');

        $resReset->assertStatus(200);
        $resetJson = json_decode($resReset->getJSON(), true);
        $this->assertEquals('success', $resetJson['status']);
        $this->assertGreaterThanOrEqual(1, $resetJson['data']['total']);
        $this->assertEquals('reset_existing', $resetJson['data']['mode']);
    }
}

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
}

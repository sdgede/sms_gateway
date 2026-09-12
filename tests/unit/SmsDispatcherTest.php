<?php

namespace Tests\Unit;

use App\Libraries\SmsDispatcher;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SmsDispatcherTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset env
        unset($_ENV['USE_FIREBASE'], $_ENV['USE_SSE'], $_ENV['USE_WEBSOCKET'], $_ENV['USE_FIREBSSE'], $_ENV['USE_SSI'], $_ENV['USE_WEBSOKET']);
        unset($_SERVER['USE_FIREBASE'], $_SERVER['USE_SSE'], $_SERVER['USE_WEBSOCKET']);
        putenv('USE_FIREBASE');
        putenv('USE_SSE');
        putenv('USE_WEBSOCKET');
    }

    private function setEnvVar(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }

    public function testGetActiveMethodsReturnsConfiguredMethods(): void
    {
        $this->setEnvVar('USE_FIREBASE', 'true');
        $this->setEnvVar('USE_SSE', 'false');
        $this->setEnvVar('USE_WEBSOCKET', 'true');

        $active = SmsDispatcher::getActiveMethods();
        $this->assertTrue($active['firebase']);
        $this->assertFalse($active['sse']);
        $this->assertTrue($active['websocket']);
    }

    public function testHandlesFlexibleBooleanStrings(): void
    {
        $this->setEnvVar('USE_FIREBASE', 'TURE');
        $this->setEnvVar('USE_SSE', '1');
        $this->setEnvVar('USE_WEBSOCKET', 'yes');

        $active = SmsDispatcher::getActiveMethods();
        $this->assertTrue($active['firebase']);
        $this->assertTrue($active['sse']);
        $this->assertTrue($active['websocket']);
    }

    public function testGetActiveMethodLabels(): void
    {
        putenv('USE_FIREBASE=true');
        putenv('USE_SSE=false');
        putenv('USE_WEBSOCKET=false');

        $labels = SmsDispatcher::getActiveMethodLabels();
        $this->assertCount(1, $labels);
        $this->assertSame('firebase', $labels[0]['id']);
        $this->assertSame('Firebase FCM (HTTP v1)', $labels[0]['name']);
    }

    public function testDispatchJobNoTargetDevice(): void
    {
        putenv('USE_FIREBASE=true');
        putenv('USE_SSE=true');
        putenv('USE_WEBSOCKET=true');

        $job = [
            'job_id' => 'SMS-TEST-001',
            'recipient' => '081234567890',
            'message' => 'Hello test',
            'target_device_id' => null
        ];

        $results = SmsDispatcher::dispatchJob($job);
        $this->assertIsArray($results);
        $this->assertArrayHasKey('firebase', $results);
        $this->assertArrayHasKey('sse', $results);
        $this->assertArrayHasKey('websocket', $results);
    }
}

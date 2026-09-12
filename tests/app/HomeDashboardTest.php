<?php

namespace Tests\App;

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
}

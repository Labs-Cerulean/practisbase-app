<?php

namespace Tests\Unit;

use App\Support\Deployment;
use PHPUnit\Framework\TestCase;

class DeploymentTest extends TestCase
{
    public function test_staging_is_the_app_env_or_the_railway_environment_name(): void
    {
        $this->assertTrue(Deployment::isStaging('staging', 'production'));
        $this->assertTrue(Deployment::isStaging('production', 'staging'));
        $this->assertFalse(Deployment::isStaging('production', 'production'));
        $this->assertFalse(Deployment::isStaging('local', null));
    }

    public function test_the_live_hosts_are_recognised(): void
    {
        $this->assertTrue(Deployment::isLiveHost('https://www.practisbase.com'));
        $this->assertTrue(Deployment::isLiveHost('https://practisbase.com/login'));
        $this->assertFalse(Deployment::isLiveHost('https://practisbase-staging.up.railway.app'));
        $this->assertFalse(Deployment::isLiveHost('http://localhost'));
    }
}

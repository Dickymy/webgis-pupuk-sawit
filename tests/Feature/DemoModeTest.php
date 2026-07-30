<?php

namespace Tests\Feature;

use Tests\TestCase;

class DemoModeTest extends TestCase
{
    protected function tearDown(): void
    {
        config(['app.demo_mode' => false]);
        parent::tearDown();
    }

    public function test_demo_mode_config_exists_in_env_example(): void
    {
        $content = file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('APP_DEMO_MODE', $content);
    }

    public function test_demo_mode_default_false(): void
    {
        $this->assertFalse(config('app.demo_mode', false));
    }

    public function test_demo_mode_does_not_alter_calculations(): void
    {
        // When demo mode is on, calculations should remain unchanged
        config(['app.demo_mode' => true]);

        $weights = config('fertilization.reliability_weights');
        $this->assertEquals(100, array_sum($weights));

        $interval = config('fertilization.window.min_interval_days');
        $this->assertEquals(120, $interval);
    }
}

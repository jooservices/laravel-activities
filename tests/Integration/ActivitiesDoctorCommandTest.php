<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Integration;

use Illuminate\Support\Facades\Artisan;
use JOOservices\LaravelActivities\Tests\TestCase;

final class ActivitiesDoctorCommandTest extends TestCase
{
    public function test_doctor_reports_healthy_runtime(): void
    {
        $this->artisan('activities:doctor')
            ->expectsOutputToContain('config.loaded')
            ->expectsOutputToContain('bindings')
            ->assertSuccessful();
    }

    public function test_doctor_json_and_indexes(): void
    {
        $this->artisan('activities:ensure-indexes')->assertSuccessful();
        $this->artisan('activities:ensure-indexes')->assertSuccessful();

        $exitCode = Artisan::call('activities:doctor', ['--json' => true, '--check-indexes' => true]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('status', $payload);
        $this->assertArrayHasKey('checks', $payload);
    }

    public function test_array_store_warns_outside_production(): void
    {
        $this->app['config']->set('activities.store', 'array');

        $exitCode = Artisan::call('activities:doctor', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertSame('warn', $payload['status']);
    }

    public function test_strict_treats_array_store_warning_as_failure(): void
    {
        $this->app['config']->set('activities.store', 'array');

        $this->artisan('activities:doctor', ['--strict' => true])
            ->assertFailed();
    }

    public function test_invalid_sanitizer_config_fails(): void
    {
        $this->app['config']->set('activities.sanitization.redacted_value', '');

        $this->artisan('activities:doctor')->assertFailed();
    }
}

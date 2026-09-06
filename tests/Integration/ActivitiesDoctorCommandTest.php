<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Integration;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use JOOservices\LaravelActivities\Tests\TestCase;
use MongoDB\Laravel\Connection;

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

    public function test_doctor_warns_when_index_keys_differ_from_expected(): void
    {
        $connection = DB::connection('mongodb');
        $this->assertInstanceOf(Connection::class, $connection);

        $collectionName = config('activities.collection');
        $this->assertIsString($collectionName);

        $collection = $connection->getCollection($collectionName);
        $collection->insertOne(['_probe' => true]);

        foreach (ActivityRepository::expectedIndexes() as $index) {
            $collection->createIndex($index['keys'], $index['options']);
        }

        $collection->dropIndex('activities_created_at');
        $collection->createIndex(['tenant_id' => 1], ['name' => 'activities_created_at']);

        try {
            $exitCode = Artisan::call('activities:doctor', ['--json' => true, '--check-indexes' => true]);
            $payload = json_decode(Artisan::output(), true);
        } finally {
            $collection->dropIndex('activities_created_at');
            foreach (ActivityRepository::expectedIndexes() as $index) {
                $collection->createIndex($index['keys'], $index['options']);
            }
            $collection->deleteMany(['_probe' => true]);
        }

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($payload);
        $this->assertSame('warn', $payload['status']);
        $this->assertIsArray($payload['checks']);

        $indexCheck = null;
        foreach ($payload['checks'] as $check) {
            $this->assertIsArray($check);
            if (($check['name'] ?? null) === 'indexes') {
                $indexCheck = $check;
                break;
            }
        }

        $this->assertIsArray($indexCheck);
        $this->assertSame('warn', $indexCheck['status']);
        $this->assertIsString($indexCheck['message']);
        $this->assertStringContainsString('activities_created_at', $indexCheck['message']);
    }
}

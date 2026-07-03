<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests;

use JOOservices\LaravelActivities\ActivitiesServiceProvider;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Testing\ArrayActivityStore;

final class ArrayStoreFlowTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('activities.store', 'array');
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_array_store_supports_cursor_pagination_and_for_subject(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $query = $this->app->make(ActivityQueryInterface::class);

        foreach (['alpha', 'beta', 'gamma'] as $label) {
            $recorder->record(new ActivityRecordDto(
                subjectType: TestSubject::class,
                subjectId: '90',
                activity: 'demo.created',
                description: $label,
            ));
            usleep(2000);
        }

        $firstPage = $query->list(new ActivityFilterDto(
            subjectType: TestSubject::class,
            subjectId: '90',
            limit: 2,
        ));

        $this->assertCount(2, $firstPage->items);
        $this->assertNotNull($firstPage->nextCursor);

        $secondPage = $query->list(new ActivityFilterDto(
            subjectType: TestSubject::class,
            subjectId: '90',
            limit: 2,
            cursor: $firstPage->nextCursor,
        ));

        $this->assertCount(1, $secondPage->items);
        $this->assertNull($secondPage->nextCursor);

        $subjectList = $query->forSubject(new TestSubject(90), new ActivityFilterDto(limit: 10));
        $this->assertSame(3, $subjectList->total);
    }

    public function test_array_store_doctor_skips_mongodb_requirement(): void
    {
        $this->artisan('activities:doctor', ['--json' => true])
            ->assertSuccessful();
    }

    public function test_service_provider_registers_array_store_bindings(): void
    {
        $provider = new ActivitiesServiceProvider($this->app);
        $provider->register();

        $this->assertInstanceOf(ArrayActivityStore::class, $this->app->make(ActivityRecorderInterface::class));
        $this->assertInstanceOf(ArrayActivityStore::class, $this->app->make(ActivityQueryInterface::class));
    }
}

<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Support\DefaultActivitySanitizer;
use JOOservices\LaravelActivities\Testing\ArrayActivityStore;
use JOOservices\LaravelActivities\Tests\TestCase;
use JOOservices\LaravelActivities\Tests\TestSubject;

final class ArrayActivityStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('activities.store', 'array');
        $this->app->forgetInstance(ActivityRecorderInterface::class);
        $this->app->forgetInstance(ActivityQueryInterface::class);
    }

    public function test_array_store_records_and_lists_without_mongodb(): void
    {
        $store = new ArrayActivityStore(new DefaultActivitySanitizer());

        $store->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: '1',
            activity: 'demo.created',
            correlationId: 'corr-1',
            batchId: 'batch-1',
        ));

        $list = $store->list(new ActivityFilterDto(correlationId: 'corr-1'));

        $this->assertSame(1, $list->total);
        $this->assertSame('corr-1', $list->items[0]->correlationId);
        $this->assertSame('batch-1', $list->items[0]->batchId);
    }

    public function test_array_store_supports_record_for_and_flush(): void
    {
        $store = new ArrayActivityStore(new DefaultActivitySanitizer());

        $store->recordFor(
            subject: new TestSubject(2),
            activity: 'demo.updated',
            actor: new TestSubject(1),
            description: 'Updated',
        );

        $this->assertSame(1, $store->list(new ActivityFilterDto())->total);

        $store->flush();

        $this->assertSame(0, $store->list(new ActivityFilterDto())->total);
    }

    public function test_array_store_supports_offset_pagination(): void
    {
        $store = new ArrayActivityStore(new DefaultActivitySanitizer());

        foreach (range(1, 3) as $index) {
            $store->record(new ActivityRecordDto(
                subjectType: TestSubject::class,
                subjectId: (string) $index,
                activity: 'demo.created',
            ));
        }

        $secondPage = $store->list(new ActivityFilterDto(limit: 2, page: 2));

        $this->assertSame(3, $secondPage->total);
        $this->assertCount(1, $secondPage->items);
    }
}

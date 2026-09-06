<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Exceptions\InvalidActivityFilterException;
use JOOservices\LaravelActivities\Services\ActivityPayloadLimiter;
use JOOservices\LaravelActivities\Services\ActivityPayloadPreparer;
use JOOservices\LaravelActivities\Support\ActivityFilterGuard;
use JOOservices\LaravelActivities\Support\DefaultActivitySanitizer;
use JOOservices\LaravelActivities\Testing\ArrayActivityStore;
use JOOservices\LaravelActivities\Tests\TestSubject;

final class ArrayActivityStoreTest extends UnitTestCase
{
    public function test_it_records_plugin_slug_and_tenant_and_correlation(): void
    {
        $store = $this->store();
        $slug = $this->faker()->slug(1);
        $tenant = (string) $this->faker()->randomNumber(4, true);
        $correlation = $this->faker()->uuid();

        $store->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: (string) $this->faker()->randomNumber(3, true),
            activity: $this->faker()->slug(2),
            context: ['plugin_slug' => $slug],
            correlationId: $correlation,
            tenantId: $tenant,
        ));

        $list = $store->list(new ActivityFilterDto(
            contextKey: 'plugin_slug',
            contextValue: $slug,
            tenantId: $tenant,
            correlationId: $correlation,
        ));

        $this->assertCount(1, $list->items);
        $this->assertSame($slug, $list->items[0]->pluginSlug);
        $this->assertSame($tenant, $list->items[0]->tenantId);
        $this->assertNull($list->total);
        $this->assertFalse($list->hasMore);
    }

    public function test_it_caps_limit_and_does_not_fake_totals(): void
    {
        $store = $this->store(maxLimit: 2);
        $subjectId = (string) $this->faker()->randomNumber(3, true);

        foreach (range(1, 3) as $ignored) {
            $store->record(new ActivityRecordDto(
                subjectType: TestSubject::class,
                subjectId: $subjectId,
                activity: $this->faker()->slug(2),
            ));
        }

        $this->expectException(InvalidActivityFilterException::class);
        $store->list(new ActivityFilterDto(subjectId: $subjectId, limit: 50));
    }

    public function test_offset_pagination_returns_real_total(): void
    {
        $store = $this->store();
        $subjectId = (string) $this->faker()->randomNumber(3, true);

        foreach (range(1, 3) as $ignored) {
            $store->record(new ActivityRecordDto(
                subjectType: TestSubject::class,
                subjectId: $subjectId,
                activity: $this->faker()->slug(2),
            ));
        }

        $page = $store->list(new ActivityFilterDto(
            subjectId: $subjectId,
            pagination: 'offset',
            limit: 2,
            page: 2,
        ));

        $this->assertSame(3, $page->total);
        $this->assertSame(2, $page->lastPage);
        $this->assertCount(1, $page->items);
    }

    public function test_for_subject_and_actor_forward_filters(): void
    {
        $store = $this->store();
        $subject = new TestSubject($this->faker()->numberBetween(1, 99));
        $actor = new TestSubject($this->faker()->numberBetween(1, 99));
        $activity = $this->faker()->slug(2);

        $store->recordFor($subject, $activity, $actor);

        $forSubject = $store->forSubject($subject);
        $forActor = $store->forActor($actor);

        $this->assertCount(1, $forSubject->items);
        $this->assertCount(1, $forActor->items);
        $this->assertSame($activity, $forSubject->items[0]->activity);
    }

    public function test_record_for_keeps_correlation_and_tenant(): void
    {
        $store = $this->store();
        $correlation = $this->faker()->uuid();
        $tenant = (string) $this->faker()->randomNumber(3, true);

        $dto = $store->recordFor(
            subject: new TestSubject($this->faker()->numberBetween(1, 99)),
            activity: $this->faker()->slug(2),
            correlationId: $correlation,
            tenantId: $tenant,
        );

        $this->assertSame($correlation, $dto->correlationId);
        $this->assertSame($tenant, $dto->tenantId);

        $store->flush();
        $empty = $store->list(new ActivityFilterDto());
        $this->assertCount(0, $empty->items);
    }

    private function store(int $maxLimit = 100): ArrayActivityStore
    {
        $sanitizer = new DefaultActivitySanitizer(keys: ['password'], replacement: '[redacted]');
        $preparer = new ActivityPayloadPreparer($sanitizer, new ActivityPayloadLimiter([]));
        $guard = new ActivityFilterGuard(allow: [], maxLimit: $maxLimit);

        return new ArrayActivityStore($preparer, $guard);
    }
}

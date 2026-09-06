<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Integration;

use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Exceptions\InvalidActivityFilterException;
use JOOservices\LaravelActivities\Services\ActivityQuery;
use JOOservices\LaravelActivities\Tests\TestCase;
use JOOservices\LaravelActivities\Tests\TestSubject;

final class ActivityQueryTest extends TestCase
{
    public function test_cursor_list_does_not_fake_total(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $query = $this->app->make(ActivityQueryInterface::class);
        $subjectId = (string) $this->faker()->randomNumber(3, true);

        foreach (range(1, 3) as $offset) {
            \Illuminate\Support\Carbon::setTestNow('2026-01-01T00:00:0' . $offset . '+00:00');
            $recorder->record(new ActivityRecordDto(
                subjectType: TestSubject::class,
                subjectId: $subjectId,
                activity: $this->faker()->slug(2),
            ));
        }
        \Illuminate\Support\Carbon::setTestNow();

        $list = $query->list(new ActivityFilterDto(subjectId: $subjectId, limit: 2));

        $this->assertNull($list->total);
        $this->assertNull($list->lastPage);
        $this->assertTrue($list->hasMore);
        $this->assertNotNull($list->nextCursor);
        $this->assertCount(2, $list->items);

        $pageTwo = $query->list(new ActivityFilterDto(
            subjectId: $subjectId,
            limit: 2,
            cursor: $list->nextCursor,
        ));
        $this->assertCount(1, $pageTwo->items);
        $this->assertFalse($pageTwo->hasMore);
    }

    public function test_page_two_requires_offset_mode(): void
    {
        $query = $this->app->make(ActivityQueryInterface::class);

        $this->expectException(InvalidActivityFilterException::class);

        $query->list(new ActivityFilterDto(page: 2));
    }

    public function test_offset_and_prefix_and_tenant_filters(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $query = $this->app->make(ActivityQueryInterface::class);
        $tenant = (string) $this->faker()->randomNumber(4, true);
        $otherTenant = (string) $this->faker()->randomNumber(4, true);

        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: '1',
            activity: 'crawl_target.created',
            tenantId: $tenant,
        ));
        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: '1',
            activity: 'user.login',
            tenantId: $tenant,
        ));
        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: '1',
            activity: 'crawl_target.updated',
            tenantId: $otherTenant,
        ));

        $list = $query->list(new ActivityFilterDto(
            activityPrefix: 'crawl_target.',
            tenantId: $tenant,
            pagination: 'offset',
            limit: 10,
        ));

        $this->assertSame(1, $list->total);
        $this->assertSame('crawl_target.created', $list->items[0]->activity);
    }

    public function test_for_subject_forwards_pagination(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $query = $this->app->make(ActivityQueryInterface::class);
        $subject = new TestSubject($this->faker()->numberBetween(1, 99));

        foreach (range(1, 3) as $ignored) {
            $recorder->recordFor($subject, $this->faker()->slug(2));
        }

        $page = $query->forSubject($subject, new ActivityFilterDto(
            pagination: 'offset',
            limit: 2,
            page: 2,
        ));

        $this->assertSame(3, $page->total);
        $this->assertCount(1, $page->items);
    }

    public function test_for_actor_filters_by_actor(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $query = $this->app->make(ActivityQuery::class);
        $actor = new TestSubject($this->faker()->numberBetween(1, 99));
        $other = new TestSubject($this->faker()->numberBetween(100, 199));

        $recorder->recordFor(new TestSubject(1), 'a.one', $actor);
        $recorder->recordFor(new TestSubject(2), 'a.two', $other);

        $list = $query->forActor($actor);

        $this->assertCount(1, $list->items);
        $this->assertSame((string) $actor->getKey(), $list->items[0]->actorId);
    }
}

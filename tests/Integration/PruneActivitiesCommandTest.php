<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Integration;

use Illuminate\Support\Carbon;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Tests\TestCase;
use JOOservices\LaravelActivities\Tests\TestSubject;

final class PruneActivitiesCommandTest extends TestCase
{
    public function test_force_deletes_old_rows_in_one_pass(): void
    {
        Carbon::setTestNow('2026-01-10T00:00:00+00:00');
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $query = $this->app->make(ActivityQueryInterface::class);
        $subjectId = (string) $this->faker()->randomNumber(3, true);

        Carbon::setTestNow('2025-01-01T00:00:00+00:00');
        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: $subjectId,
            activity: 'old.event',
        ));

        Carbon::setTestNow('2026-01-10T00:00:00+00:00');
        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: $subjectId,
            activity: 'new.event',
        ));

        $this->artisan('activities:prune', ['--days' => 30, '--force' => true, '--json' => true])
            ->assertSuccessful();

        $remaining = $query->list(new ActivityFilterDto(
            subjectId: $subjectId,
            pagination: 'offset',
        ));

        $this->assertSame(1, $remaining->total);
        $this->assertSame('new.event', $remaining->items[0]->activity);

        Carbon::setTestNow();
    }

    public function test_context_options_must_be_paired(): void
    {
        $this->artisan('activities:prune', ['--context-key' => 'plugin_slug'])
            ->assertFailed();
    }

    public function test_disabled_retention_without_days_is_noop(): void
    {
        $this->app['config']->set('activities.retention.enabled', false);

        $this->artisan('activities:prune', ['--json' => true])
            ->assertSuccessful();
    }

    public function test_invalid_days_fails(): void
    {
        $this->artisan('activities:prune', ['--days' => '0', '--json' => true])
            ->assertFailed();
    }
}

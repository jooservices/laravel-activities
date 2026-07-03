<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests;

use Illuminate\Support\Carbon;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Facades\Activity;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;

final class ActivityServiceTest extends TestCase
{
    public function test_record_persists_append_only_activity(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);

        $dto = $recorder->record(new ActivityRecordDto(
            subjectType: 'App\\Models\\User',
            subjectId: '7',
            activity: 'crawl_target.created',
            description: 'Created crawl target',
            data: ['url' => 'https://onejav.com/new'],
            actorType: 'App\\Models\\User',
            actorId: '1',
            context: ['plugin_slug' => 'onejav'],
        ));

        $this->assertSame('crawl_target.created', $dto->activity);
        $this->assertSame('Created crawl target', $dto->description);
        $this->assertSame(['url' => 'https://onejav.com/new'], $dto->data);
        $this->assertSame('onejav', $dto->context['plugin_slug'] ?? null);
        $this->assertNotSame('', $dto->id);
    }

    public function test_record_for_accepts_subject_and_actor_objects(): void
    {
        $subject = new TestSubject(42);
        $actor = new TestSubject(1);

        $dto = Activity::recordFor(
            subject: $subject,
            activity: 'crawl.dispatched',
            actor: $actor,
            description: 'Dispatched crawl job',
            data: ['target_id' => 42],
            context: ['plugin_slug' => 'onejav'],
        );

        $this->assertSame(TestSubject::class, $dto->subjectType);
        $this->assertSame('42', $dto->subjectId);
        $this->assertSame(TestSubject::class, $dto->actorType);
        $this->assertSame('1', $dto->actorId);
    }

    public function test_query_filters_by_subject_and_context(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $query = $this->app->make(ActivityQueryInterface::class);

        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: '10',
            activity: 'crawl_target.created',
            context: ['plugin_slug' => 'onejav'],
        ));

        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: '11',
            activity: 'crawl_target.created',
            context: ['plugin_slug' => 'jable'],
        ));

        $onejav = $query->list(new ActivityFilterDto(
            contextKey: 'plugin_slug',
            contextValue: 'onejav',
            limit: 10,
        ));

        $this->assertSame(1, $onejav->total);
        $this->assertSame('10', $onejav->items[0]->subjectId);

        $subjectList = $query->forSubject(new TestSubject(10));

        $this->assertSame(1, $subjectList->total);
        $this->assertSame('crawl_target.created', $subjectList->items[0]->activity);
    }

    public function test_cursor_pagination_returns_next_cursor(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $query = $this->app->make(ActivityQueryInterface::class);

        $labels = [
            ['2026-01-01 00:00:02', 'third'],
            ['2026-01-01 00:00:01', 'second'],
            ['2026-01-01 00:00:00', 'first'],
        ];

        foreach ($labels as [$timestamp, $label]) {
            Carbon::setTestNow($timestamp);

            $recorder->record(new ActivityRecordDto(
                subjectType: TestSubject::class,
                subjectId: '20',
                activity: 'crawl_target.created',
                description: $label,
            ));
        }

        Carbon::setTestNow();

        $firstPage = $query->list(new ActivityFilterDto(
            subjectType: TestSubject::class,
            subjectId: '20',
            limit: 2,
            cursor: null,
        ));

        $this->assertCount(2, $firstPage->items);
        $this->assertNotNull($firstPage->nextCursor);

        $secondPage = $query->list(new ActivityFilterDto(
            subjectType: TestSubject::class,
            subjectId: '20',
            limit: 2,
            cursor: $firstPage->nextCursor,
        ));

        $this->assertCount(1, $secondPage->items);
        $this->assertNull($secondPage->nextCursor);
    }

    public function test_sanitizer_redacts_sensitive_data_on_record(): void
    {
        $this->app['config']->set('activities.sanitization.sensitive_keys', ['token']);

        $dto = $this->app->make(ActivityRecorderInterface::class)->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: '30',
            activity: 'secure.updated',
            data: ['token' => 'secret'],
        ));

        $this->assertSame('[redacted]', $dto->data['token'] ?? null);
    }

    public function test_prune_and_export_commands_work(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: '40',
            activity: 'crawl_target.created',
            context: ['plugin_slug' => 'onejav'],
        ));

        $this->artisan('activities:doctor')->assertSuccessful();
        $this->artisan('activities:prune', ['--days' => 365, '--dry-run' => true])->assertSuccessful();

        $path = sys_get_temp_dir().'/activities-export-test.jsonl';
        @unlink($path);

        $this->artisan('activities:export', ['--output' => $path, '--force' => true])
            ->assertSuccessful();

        $this->assertFileExists($path);
        @unlink($path);
    }

    public function test_ensure_indexes_command_succeeds(): void
    {
        $this->artisan('activities:ensure-indexes')
            ->assertSuccessful();

        $indexes = $this->app->make(ActivityRepository::class)->ensureIndexes();

        $this->assertStringContainsString('activities_subject_created_at', $indexes);
        $this->assertStringContainsString('activities_correlation_created_at', $indexes);
    }
}

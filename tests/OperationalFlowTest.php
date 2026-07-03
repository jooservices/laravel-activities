<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Contracts\ActivitySanitizerInterface;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Facades\Activity;

final class OperationalFlowTest extends TestCase
{
    public function test_doctor_command_reports_healthy_runtime(): void
    {
        $this->artisan('activities:doctor')
            ->expectsOutputToContain('config.loaded')
            ->expectsOutputToContain('bindings')
            ->assertSuccessful();
    }

    public function test_doctor_command_can_render_json_output(): void
    {
        $exitCode = Artisan::call('activities:doctor', ['--json' => true]);
        $payload = json_decode(Artisan::output(), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('status', $payload);
        $this->assertArrayHasKey('checks', $payload);
    }

    public function test_doctor_command_can_check_indexes(): void
    {
        $this->artisan('activities:ensure-indexes')->assertSuccessful();

        $this->artisan('activities:doctor', ['--check-indexes' => true])
            ->expectsOutputToContain('indexes')
            ->assertSuccessful();
    }

    public function test_doctor_command_reports_invalid_sanitizer_config(): void
    {
        $this->app['config']->set('activities.sanitization.redacted_value', '');

        $this->artisan('activities:doctor')
            ->expectsOutputToContain('sanitize')
            ->assertFailed();
    }

    public function test_doctor_command_reports_invalid_retention_config(): void
    {
        $this->app['config']->set('activities.retention.default_days', 0);

        $this->artisan('activities:doctor')
            ->expectsOutputToContain('retention')
            ->assertFailed();
    }

    public function test_doctor_command_reports_invalid_storage_config(): void
    {
        $this->app['config']->set('activities.connection', '');

        $this->artisan('activities:doctor')
            ->expectsOutputToContain('config.storage')
            ->assertFailed();
    }

    public function test_doctor_command_reports_binding_failures(): void
    {
        $this->app->offsetUnset(ActivitySanitizerInterface::class);

        $this->artisan('activities:doctor')
            ->expectsOutputToContain('bindings')
            ->assertFailed();
    }

    public function test_prune_command_force_deletes_old_records(): void
    {
        Carbon::setTestNow('2020-01-01 00:00:00');

        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: '50',
            activity: 'crawl_target.created',
        ));

        Carbon::setTestNow('2026-01-01 00:00:00');

        $this->artisan('activities:prune', ['--days' => 30, '--force' => true])
            ->assertSuccessful();

        $remaining = $this->app->make(ActivityQueryInterface::class)->list(new ActivityFilterDto(
            subjectType: TestSubject::class,
            subjectId: '50',
        ));

        $this->assertSame(0, $remaining->total);

        Carbon::setTestNow();
    }

    public function test_prune_command_rejects_mismatched_context_options(): void
    {
        $this->artisan('activities:prune', ['--context-key' => 'plugin_slug'])
            ->assertFailed();
    }

    public function test_prune_command_rejects_invalid_days(): void
    {
        $this->artisan('activities:prune', ['--days' => 0])
            ->assertFailed();
    }

    public function test_prune_command_skips_when_retention_disabled(): void
    {
        $this->app['config']->set('activities.retention.enabled', false);

        $this->artisan('activities:prune', ['--json' => true])
            ->assertSuccessful();
    }

    public function test_export_command_writes_jsonl_to_stdout(): void
    {
        $this->app->make(ActivityRecorderInterface::class)->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: '61',
            activity: 'crawl_target.created',
        ));

        $this->artisan('activities:export')
            ->assertSuccessful();
    }

    public function test_export_command_writes_jsonl_to_file(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: '60',
            activity: 'crawl_target.created',
        ));

        $path = sys_get_temp_dir().'/activities-export-flow.jsonl';
        @unlink($path);

        $this->artisan('activities:export', ['--output' => $path, '--force' => true, '--json' => true])
            ->assertSuccessful();

        $this->assertFileExists($path);
        $this->assertNotSame('', trim((string) file_get_contents($path)));
        @unlink($path);
    }

    public function test_export_command_rejects_existing_output_without_force(): void
    {
        $path = sys_get_temp_dir().'/activities-export-existing.jsonl';
        file_put_contents($path, '{}');

        $this->artisan('activities:export', ['--output' => $path])
            ->assertFailed();

        @unlink($path);
    }

    public function test_export_command_rejects_missing_output_directory(): void
    {
        $path = sys_get_temp_dir().'/activities-missing-dir/export.jsonl';

        $this->artisan('activities:export', ['--output' => $path])
            ->assertFailed();
    }

    public function test_prune_command_can_render_json_output(): void
    {
        $this->artisan('activities:prune', ['--json' => true, '--dry-run' => true])
            ->assertSuccessful();
    }

    public function test_offset_pagination_still_works_for_page_two(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);

        foreach (range(1, 3) as $index) {
            $recorder->record(new ActivityRecordDto(
                subjectType: TestSubject::class,
                subjectId: '70',
                activity: 'crawl_target.created',
                description: 'item-'.$index,
            ));
        }

        $secondPage = $this->app->make(ActivityQueryInterface::class)->list(new ActivityFilterDto(
            subjectType: TestSubject::class,
            subjectId: '70',
            limit: 2,
            page: 2,
        ));

        $this->assertSame(3, $secondPage->total);
        $this->assertCount(1, $secondPage->items);
    }

    public function test_facade_record_for_delegates_to_recorder(): void
    {
        $dto = Activity::recordFor(
            subject: new TestSubject(80),
            activity: 'crawl.dispatched',
            description: 'via facade',
        );

        $this->assertSame('via facade', $dto->description);
    }

    public function test_facade_list_delegates_to_query(): void
    {
        Activity::recordFor(
            subject: new TestSubject(81),
            activity: 'crawl.dispatched',
        );

        $list = Activity::list(new ActivityFilterDto(
            subjectType: TestSubject::class,
            subjectId: '81',
        ));

        $this->assertSame(1, $list->total);
    }
}

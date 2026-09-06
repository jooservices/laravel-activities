<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Integration;

use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Tests\TestCase;
use JOOservices\LaravelActivities\Tests\TestSubject;

final class ExportActivitiesCommandTest extends TestCase
{
    public function test_it_exports_jsonl_and_csv_with_formula_prefix(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $formula = '=cmd|' . $this->faker()->word();

        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: (string) $this->faker()->randomNumber(3, true),
            activity: 'export.demo',
            description: $formula,
        ));

        $dir = sys_get_temp_dir();
        $jsonl = $dir . '/activities-export-' . $this->faker()->uuid() . '.jsonl';
        $csv = $dir . '/activities-export-' . $this->faker()->uuid() . '.csv';

        $this->artisan('activities:export', [
            '--output' => $jsonl,
            '--format' => 'jsonl',
            '--json' => true,
        ])->assertSuccessful();

        $this->artisan('activities:export', [
            '--output' => $csv,
            '--format' => 'csv',
            '--json' => true,
        ])->assertSuccessful();

        $jsonlBody = (string) file_get_contents($jsonl);
        $csvBody = (string) file_get_contents($csv);

        $this->assertStringContainsString('export.demo', $jsonlBody);
        $this->assertStringContainsString("'" . $formula, $csvBody);

        unlink($jsonl);
        unlink($csv);
    }

    public function test_it_refuses_to_overwrite_without_force(): void
    {
        $path = sys_get_temp_dir() . '/activities-export-' . $this->faker()->uuid() . '.jsonl';
        file_put_contents($path, 'existing');

        $this->artisan('activities:export', ['--output' => $path])
            ->assertFailed();

        $this->assertSame('existing', (string) file_get_contents($path));

        $this->artisan('activities:export', ['--output' => $path, '--force' => true])
            ->assertSuccessful();

        unlink($path);
    }

    public function test_invalid_format_fails(): void
    {
        $this->artisan('activities:export', ['--format' => 'xml'])
            ->assertFailed();
    }
}

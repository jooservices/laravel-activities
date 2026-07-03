<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Models\Activity;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;

final class ExportActivitiesCommand extends Command
{
    protected $signature = 'activities:export
        {--subject-type= : Filter by subject type}
        {--subject-id= : Filter by subject id}
        {--activity= : Filter by activity name}
        {--from= : Include activities created on/after this date}
        {--to= : Exclude activities created on/after this date}
        {--output= : Output file path. Writes to stdout when omitted}
        {--force : Overwrite an existing output file}
        {--json : Output machine-readable summary when exporting to a file}';

    protected $description = 'Export activities as JSONL.';

    public function handle(ActivityRepository $repository): int
    {
        $output = $this->filledOption('output') ? (string) $this->option('output') : null;
        $validationMessage = $this->validateOutputPath($output);

        if ($validationMessage !== null) {
            $this->error($validationMessage);

            return self::FAILURE;
        }

        $handle = $this->openExportHandle($output);

        if (! is_resource($handle)) {
            $this->error('Unable to open export output path.');

            return self::FAILURE;
        }

        $count = $this->exportActivities($repository, $handle);
        $this->finalizeExport($output, $handle, $count);

        return self::SUCCESS;
    }

    private function validateOutputPath(?string $output): ?string
    {
        if ($output === null) {
            return null;
        }

        if (is_file($output) && ! $this->option('force')) {
            return 'Output file already exists. Pass --force to overwrite.';
        }

        if (! is_dir(dirname($output))) {
            return 'Output directory does not exist.';
        }

        return null;
    }

    /**
     * @return resource
     */
    private function openExportHandle(?string $output)
    {
        return $output === null ? STDOUT : fopen($output, 'wb');
    }

    private function exportActivities(ActivityRepository $repository, $handle): int
    {
        $count = 0;

        foreach ($repository->latestByFilter($this->buildFilter(), PHP_INT_MAX) as $activity) {
            if ($this->shouldSkipActivity($activity)) {
                continue;
            }

            fwrite($handle, (string) json_encode($activity->getAttributes(), JSON_THROW_ON_ERROR).PHP_EOL);
            $count++;
        }

        return $count;
    }

    private function shouldSkipActivity(Activity $activity): bool
    {
        if ($this->filledOption('from')) {
            $from = CarbonImmutable::parse((string) $this->option('from'));

            if ($activity->created_at < $from) {
                return true;
            }
        }

        if ($this->filledOption('to')) {
            $to = CarbonImmutable::parse((string) $this->option('to'));

            if ($activity->created_at >= $to) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  resource  $handle
     */
    private function finalizeExport(?string $output, $handle, int $count): void
    {
        if ($output === null) {
            return;
        }

        fclose($handle);
        $summary = ['format' => 'jsonl', 'output' => $output, 'exported' => $count];

        if ($this->option('json')) {
            $this->line((string) json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return;
        }

        $this->info("Exported {$count} activities to {$output}.");
    }

    private function buildFilter(): ActivityFilterDto
    {
        return new ActivityFilterDto(
            subjectType: $this->filledOption('subject-type') ? (string) $this->option('subject-type') : null,
            subjectId: $this->filledOption('subject-id') ? (string) $this->option('subject-id') : null,
            activities: $this->filledOption('activity') ? [(string) $this->option('activity')] : null,
            limit: (int) config('activities.export.chunk_size', 500),
        );
    }

    private function filledOption(string $option): bool
    {
        $value = $this->option($option);

        return $value !== null && $value !== '';
    }
}

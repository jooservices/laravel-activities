<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Console\Commands;

use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Models\Activity;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use JOOservices\LaravelActivities\Services\ActivityDtoFactory;
use JOOservices\LaravelActivities\Support\ActivityFilterGuard;

final class ExportActivitiesCommand extends Command
{
    protected $signature = 'activities:export
        {--subject-type= : Filter by subject type}
        {--subject-id= : Filter by subject id}
        {--activity= : Filter by activity name}
        {--tenant= : Filter by tenant id}
        {--from= : Include activities created on/after this date}
        {--to= : Exclude activities created on/after this date}
        {--format=jsonl : Export format: jsonl or csv}
        {--output= : Output file path. Writes to stdout when omitted}
        {--force : Overwrite an existing output file}
        {--json : Output machine-readable summary when exporting to a file}';

    protected $description = 'Export activities as JSONL or CSV.';

    public function handle(ActivityRepository $repository, ActivityFilterGuard $guard): int
    {
        $output = $this->filledOption('output') ? (string) $this->option('output') : null;
        $format = strtolower((string) $this->option('format'));

        if (! in_array($format, ['jsonl', 'csv'], true)) {
            $this->error('Format must be jsonl or csv.');

            return self::FAILURE;
        }

        $validationMessage = $this->validateOutputPath($output);

        if ($validationMessage !== null) {
            $this->error($validationMessage);

            return self::FAILURE;
        }

        $filter = $this->buildFilter();
        $guard->assert($filter);

        $handle = $this->openExportHandle($output);

        if (! is_resource($handle)) {
            $this->error('Unable to open export output path.');

            return self::FAILURE;
        }

        if ($format === 'csv' && fputcsv($handle, $this->csvHeaders()) === false) {
            $this->error('Unable to write CSV headers.');

            return self::FAILURE;
        }

        $count = 0;
        $failed = false;
        $chunkSize = max(1, (int) config('activities.export.chunk_size', 500));

        $repository->exportChunk($filter, $chunkSize, function (Collection $batch, int $page) use ($handle, $format, &$count, &$failed): void {
            foreach ($batch as $activity) {
                if (! $activity instanceof Activity || $failed) {
                    continue;
                }

                $dto = ActivityDtoFactory::fromModel($activity);
                $written = $format === 'csv'
                    ? fputcsv($handle, $this->csvRow($dto))
                    : fwrite($handle, (string) json_encode($dto->toArray(), JSON_THROW_ON_ERROR) . PHP_EOL);

                if ($written === false) {
                    $failed = true;

                    continue;
                }

                $count++;
            }
        });

        if ($failed) {
            $this->error('Export write failed before all records were written.');

            return self::FAILURE;
        }

        $this->finalizeExport($output, $handle, $count, $format);

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

    /**
     * @param  resource  $handle
     */
    private function finalizeExport(?string $output, $handle, int $count, string $format): void
    {
        if ($output === null) {
            return;
        }

        fclose($handle);
        $summary = ['format' => $format, 'output' => $output, 'exported' => $count];

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
            from: $this->filledOption('from') ? (string) $this->option('from') : null,
            to: $this->filledOption('to') ? (string) $this->option('to') : null,
            tenantId: $this->filledOption('tenant') ? (string) $this->option('tenant') : null,
            limit: 1,
        );
    }

    /**
     * @return list<string>
     */
    private function csvHeaders(): array
    {
        return [
            'id',
            'subject_type',
            'subject_id',
            'activity',
            'description',
            'actor_type',
            'actor_id',
            'tenant_id',
            'correlation_id',
            'batch_id',
            'plugin_slug',
            'created_at',
        ];
    }

    /**
     * @return list<string>
     */
    private function csvRow(ActivityDto $dto): array
    {
        $createdAt = $dto->createdAt instanceof DateTimeInterface
            ? $dto->createdAt->format(DateTimeInterface::ATOM)
            : (string) $dto->createdAt;

        return [
            $this->csvSafe($dto->id),
            $this->csvSafe($dto->subjectType),
            $this->csvSafe($dto->subjectId),
            $this->csvSafe($dto->activity),
            $this->csvSafe($dto->description),
            $this->csvSafe($dto->actorType),
            $this->csvSafe($dto->actorId),
            $this->csvSafe($dto->tenantId),
            $this->csvSafe($dto->correlationId),
            $this->csvSafe($dto->batchId),
            $this->csvSafe($dto->pluginSlug),
            $this->csvSafe($createdAt),
        ];
    }

    private function csvSafe(mixed $value): string
    {
        $text = '';

        if ($value === null) {
            $text = '';
        }

        if (is_scalar($value)) {
            $text = (string) $value;
        }

        $significant = ltrim($text, " \t\r\n\0\x0B");

        if ($significant !== '' && in_array($significant[0], ['=', '+', '-', '@'], true)) {
            return "'" . $text;
        }

        return $text;
    }

    private function filledOption(string $option): bool
    {
        $value = $this->option($option);

        return $value !== null && $value !== '';
    }
}

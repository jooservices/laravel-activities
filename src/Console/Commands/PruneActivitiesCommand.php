<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use JOOservices\LaravelActivities\Support\ActivityFilterGuard;

final class PruneActivitiesCommand extends Command
{
    protected $signature = 'activities:prune
        {--days= : Override retention days}
        {--context-key= : Optional context key filter}
        {--context-value= : Optional context value filter}
        {--tenant= : Optional tenant id filter}
        {--dry-run : Count matching activities without deleting}
        {--force : Actually delete matching activities}
        {--json : Output machine-readable JSON}';

    protected $description = 'Prune activities older than the configured retention window.';

    public function handle(ActivityRepository $repository, ActivityFilterGuard $guard): int
    {
        $contextError = $this->validateContextOptions();

        if ($contextError !== null) {
            $this->renderError($contextError);

            return self::FAILURE;
        }

        $days = $this->resolveRetentionDays();

        if ($days === null) {
            $this->renderError('Retention days must be a positive integer.');

            return self::FAILURE;
        }

        if ($this->retentionDisabledWithoutOverride()) {
            $this->renderResult('dry-run', 0, 0, CarbonImmutable::now('UTC'));

            return self::SUCCESS;
        }

        $cutoff = CarbonImmutable::now('UTC')->subDays($days);
        $filter = $this->buildFilter();
        $guard->assert($filter);
        $result = $repository->pruneMatching($cutoff, $filter, (bool) $this->option('force'));

        $this->renderResult(
            $this->option('force') ? 'force' : 'dry-run',
            $result['matched'],
            $result['deleted'],
            $cutoff,
        );

        return self::SUCCESS;
    }

    private function validateContextOptions(): ?string
    {
        if ($this->filledOption('context-key') xor $this->filledOption('context-value')) {
            return 'Options --context-key and --context-value must be used together.';
        }

        return null;
    }

    private function resolveRetentionDays(): ?int
    {
        $days = $this->filledOption('days')
            ? filter_var($this->option('days'), FILTER_VALIDATE_INT)
            : (int) config('activities.retention.default_days', 365);

        if ($days === false || $days < 1) {
            return null;
        }

        return $days;
    }

    private function retentionDisabledWithoutOverride(): bool
    {
        return (bool) config('activities.retention.enabled', true) === false
            && ! $this->filledOption('days');
    }

    private function buildFilter(): ActivityFilterDto
    {
        return new ActivityFilterDto(
            contextKey: $this->filledOption('context-key') ? (string) $this->option('context-key') : null,
            contextValue: $this->filledOption('context-value') ? (string) $this->option('context-value') : null,
            tenantId: $this->filledOption('tenant') ? (string) $this->option('tenant') : null,
            limit: 1,
        );
    }

    private function renderResult(string $mode, int $matched, int $deleted, CarbonImmutable $cutoff): void
    {
        $payload = [
            'mode' => $mode,
            'cutoff' => $cutoff->toIso8601String(),
            'matched' => $matched,
            'deleted' => $deleted,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return;
        }

        $this->line('Matched: ' . $matched);
        $this->line('Deleted: ' . $deleted);
        $this->line('Mode: ' . $mode);
        $this->line('Cutoff: ' . $payload['cutoff']);
    }

    private function renderError(string $message): void
    {
        if ($this->option('json')) {
            $this->line((string) json_encode(['error' => $message], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return;
        }

        $this->error($message);
    }

    private function filledOption(string $option): bool
    {
        $value = $this->option($option);

        return $value !== null && $value !== '';
    }
}

<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Contracts;

use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;

interface ActivityRecorderInterface
{
    public function record(ActivityRecordDto $record): ActivityDto;

    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>|null  $context
     */
    public function recordFor(
        object $subject,
        string $activity,
        ?object $actor = null,
        ?string $description = null,
        ?array $data = null,
        ?array $context = null,
        ?string $correlationId = null,
        ?string $batchId = null,
        ?string $tenantId = null,
    ): ActivityDto;
}

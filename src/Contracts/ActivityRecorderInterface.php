<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Contracts;

use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;

interface ActivityRecorderInterface
{
    public function record(ActivityRecordDto $record): ActivityDto;

    public function recordFor(
        object $subject,
        string $activity,
        ?object $actor = null,
        ?string $description = null,
        ?array $data = null,
        ?array $context = null,
    ): ActivityDto;
}

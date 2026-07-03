<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Dto;

use JOOservices\Dto\Core\Dto;

final class ActivityRecordDto extends Dto
{
    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        public readonly string $subjectType,
        public readonly string $subjectId,
        public readonly string $activity,
        public readonly ?string $description = null,
        public readonly ?array $data = null,
        public readonly ?string $actorType = null,
        public readonly ?string $actorId = null,
        public readonly ?array $context = null,
        public readonly ?string $correlationId = null,
        public readonly ?string $batchId = null,
    ) {}
}

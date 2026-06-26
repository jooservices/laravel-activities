<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Dto;

use JOOservices\Dto\Core\Dto;

final class ActivityDto extends Dto
{
    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        public readonly string $id,
        public readonly string $subjectType,
        public readonly string $subjectId,
        public readonly string $activity,
        public readonly ?string $description,
        public readonly ?array $data,
        public readonly ?string $actorType,
        public readonly ?string $actorId,
        public readonly ?array $context,
        public readonly string $createdAt,
    ) {}
}

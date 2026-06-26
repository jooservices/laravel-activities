<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Dto;

use JOOservices\Dto\Core\Dto;

final class ActivityFilterDto extends Dto
{
    /**
     * @param  list<string>|null  $activities
     */
    public function __construct(
        public readonly ?string $subjectType = null,
        public readonly ?string $subjectId = null,
        public readonly ?string $contextKey = null,
        public readonly ?string $contextValue = null,
        public readonly ?array $activities = null,
        public readonly int $limit = 50,
        public readonly int $page = 1,
    ) {}
}

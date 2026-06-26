<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Dto;

use JOOservices\Dto\Core\Dto;

final class ActivityListDto extends Dto
{
    /**
     * @param  list<ActivityDto>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $lastPage,
    ) {}
}

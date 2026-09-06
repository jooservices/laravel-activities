<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Dto;

use JOOservices\Dto\Attributes\MapFrom;
use JOOservices\Dto\Attributes\MapTo;
use JOOservices\Dto\Core\Dto;

final class ActivityListDto extends Dto
{
    /**
     * @param  list<ActivityDto>  $items
     */
    public function __construct(
        public readonly array $items,
        #[MapFrom('per_page')]
        #[MapTo('per_page')]
        public readonly int $perPage,
        public readonly ?int $total = null,
        public readonly int $page = 1,
        #[MapFrom('last_page')]
        #[MapTo('last_page')]
        public readonly ?int $lastPage = null,
        #[MapFrom('next_cursor')]
        #[MapTo('next_cursor')]
        public readonly ?string $nextCursor = null,
        #[MapFrom('has_more')]
        #[MapTo('has_more')]
        public readonly bool $hasMore = false,
    ) {
    }
}

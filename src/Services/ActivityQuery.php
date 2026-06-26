<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Services;

use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityListDto;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use JOOservices\LaravelActivities\Support\SubjectReference;

final class ActivityQuery implements ActivityQueryInterface
{
    public function __construct(
        private readonly ActivityRepository $activities,
    ) {}

    public function list(ActivityFilterDto $filter): ActivityListDto
    {
        $paginator = $this->activities->paginateByFilter($filter);
        $items = [];

        foreach ($paginator->items() as $activity) {
            $items[] = ActivityMapper::toDto($activity);
        }

        return new ActivityListDto(
            items: $items,
            total: $paginator->total(),
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage(),
        );
    }

    public function forSubject(object $subject, ?ActivityFilterDto $filter = null): ActivityListDto
    {
        $subjectRef = SubjectReference::fromObject($subject);
        $base = $filter ?? new ActivityFilterDto();

        return $this->list(new ActivityFilterDto(
            subjectType: $subjectRef->type,
            subjectId: $subjectRef->id,
            contextKey: $base->contextKey,
            contextValue: $base->contextValue,
            activities: $base->activities,
            limit: $base->limit,
            page: $base->page,
        ));
    }
}

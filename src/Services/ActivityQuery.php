<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Services;

use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityListDto;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use JOOservices\LaravelActivities\Support\ActivityFilterGuard;
use JOOservices\LaravelActivities\Support\SubjectReference;

final class ActivityQuery implements ActivityQueryInterface
{
    public function __construct(
        private readonly ActivityRepository $activities,
        private readonly ActivityFilterGuard $guard,
    ) {
    }

    public function list(ActivityFilterDto $filter): ActivityListDto
    {
        $this->guard->assert($filter);

        if ($this->usesCursorPagination($filter)) {
            [$items, $nextCursor] = $this->activities->cursorPaginateByFilter($filter);
            $mapped = [];

            foreach ($items as $activity) {
                $mapped[] = ActivityDtoFactory::fromModel($activity);
            }

            return new ActivityListDto(
                items: $mapped,
                perPage: $this->guard->cappedLimit($filter),
                nextCursor: $nextCursor,
                hasMore: $nextCursor !== null,
            );
        }

        $paginator = $this->activities->paginateByFilter($filter);
        $items = [];

        foreach ($paginator->items() as $activity) {
            $items[] = ActivityDtoFactory::fromModel($activity);
        }

        return new ActivityListDto(
            items: $items,
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            page: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            hasMore: $paginator->hasMorePages(),
        );
    }

    public function forSubject(object $subject, ?ActivityFilterDto $filter = null): ActivityListDto
    {
        $subjectRef = SubjectReference::fromObject($subject);
        $base = $filter ?? new ActivityFilterDto();

        return $this->list($base->with(
            subjectType: $subjectRef->type,
            subjectId: $subjectRef->id,
        ));
    }

    public function forActor(object $actor, ?ActivityFilterDto $filter = null): ActivityListDto
    {
        $actorRef = SubjectReference::fromObject($actor);
        $base = $filter ?? new ActivityFilterDto();

        return $this->list($base->with(
            actorType: $actorRef->type,
            actorId: $actorRef->id,
        ));
    }

    private function usesCursorPagination(ActivityFilterDto $filter): bool
    {
        return $filter->pagination !== 'offset';
    }
}

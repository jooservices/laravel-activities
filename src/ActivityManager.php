<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities;

use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityListDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Services\ActivityQuery;
use JOOservices\LaravelActivities\Support\SubjectReference;
use JOOservices\LaravelActivities\Testing\ArrayActivityStore;

final class ActivityManager implements ActivityQueryInterface, ActivityRecorderInterface
{
    public function __construct(
        private readonly ActivityRecorderInterface $recorder,
        private readonly ActivityQueryInterface $query,
    ) {
    }

    public function record(ActivityRecordDto $record): ActivityDto
    {
        return $this->recorder->record($record);
    }

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
    ): ActivityDto {
        return $this->recorder->recordFor(
            $subject,
            $activity,
            $actor,
            $description,
            $data,
            $context,
            $correlationId,
            $batchId,
            $tenantId,
        );
    }

    public function list(ActivityFilterDto $filter): ActivityListDto
    {
        return $this->query->list($filter);
    }

    public function forSubject(object $subject, ?ActivityFilterDto $filter = null): ActivityListDto
    {
        return $this->query->forSubject($subject, $filter);
    }

    public function forActor(object $actor, ?ActivityFilterDto $filter = null): ActivityListDto
    {
        if ($this->query instanceof ActivityQuery || $this->query instanceof ArrayActivityStore) {
            return $this->query->forActor($actor, $filter);
        }

        $actorRef = SubjectReference::fromObject($actor);
        $base = $filter ?? new ActivityFilterDto();

        return $this->list($base->with(
            actorType: $actorRef->type,
            actorId: $actorRef->id,
        ));
    }
}

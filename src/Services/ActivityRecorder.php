<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Services;

use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use JOOservices\LaravelActivities\Support\SubjectReference;

final class ActivityRecorder implements ActivityRecorderInterface
{
    public function __construct(
        private readonly ActivityRepository $activities,
        private readonly ActivityPayloadPreparer $preparer,
    ) {
    }

    public function record(ActivityRecordDto $record): ActivityDto
    {
        $model = $this->activities->createActivity($this->preparer->prepare($record));

        return ActivityDtoFactory::fromModel($model);
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
        $subjectRef = SubjectReference::fromObject($subject);
        $actorRef = $actor !== null ? SubjectReference::fromObject($actor) : null;

        return $this->record(new ActivityRecordDto(
            subjectType: $subjectRef->type,
            subjectId: $subjectRef->id,
            activity: $activity,
            description: $description,
            data: $data,
            actorType: $actorRef?->type,
            actorId: $actorRef?->id,
            context: $context,
            correlationId: $correlationId,
            batchId: $batchId,
            tenantId: $tenantId,
        ));
    }
}

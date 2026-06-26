<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Services;

use Illuminate\Support\Carbon;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use JOOservices\LaravelActivities\Support\SubjectReference;

final class ActivityRecorder implements ActivityRecorderInterface
{
    public function __construct(
        private readonly ActivityRepository $activities,
    ) {}

    public function record(ActivityRecordDto $record): ActivityDto
    {
        $model = $this->activities->createActivity([
            'subject_type' => $record->subjectType,
            'subject_id' => $record->subjectId,
            'activity' => $record->activity,
            'description' => $record->description,
            'data' => $record->data,
            'actor_type' => $record->actorType,
            'actor_id' => $record->actorId,
            'context' => $record->context,
            'plugin_slug' => is_array($record->context) ? ($record->context['plugin_slug'] ?? null) : null,
            'created_at' => Carbon::now(),
        ]);

        return ActivityMapper::toDto($model);
    }

    public function recordFor(
        object $subject,
        string $activity,
        ?object $actor = null,
        ?string $description = null,
        ?array $data = null,
        ?array $context = null,
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
        ));
    }
}

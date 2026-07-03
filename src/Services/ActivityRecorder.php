<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Services;

use Illuminate\Support\Carbon;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Contracts\ActivitySanitizerInterface;
use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use JOOservices\LaravelActivities\Support\SubjectReference;

final class ActivityRecorder implements ActivityRecorderInterface
{
    public function __construct(
        private readonly ActivityRepository $activities,
        private readonly ActivitySanitizerInterface $sanitizer,
    ) {}

    public function record(ActivityRecordDto $record): ActivityDto
    {
        $context = $this->sanitizer->sanitize($record->context);

        $model = $this->activities->createActivity([
            'subject_type' => $record->subjectType,
            'subject_id' => $record->subjectId,
            'activity' => $record->activity,
            'description' => $record->description,
            'data' => $this->sanitizer->sanitize($record->data),
            'actor_type' => $record->actorType,
            'actor_id' => $record->actorId,
            'context' => $context,
            'plugin_slug' => is_array($context) ? ($context['plugin_slug'] ?? null) : null,
            'correlation_id' => $record->correlationId,
            'batch_id' => $record->batchId,
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

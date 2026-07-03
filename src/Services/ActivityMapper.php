<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Services;

use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Models\Activity;

final class ActivityMapper
{
    public static function toDto(Activity $activity): ActivityDto
    {
        return new ActivityDto(
            id: (string) $activity->getKey(),
            subjectType: $activity->subject_type,
            subjectId: $activity->subject_id,
            activity: $activity->activity,
            description: $activity->description,
            data: $activity->data,
            actorType: $activity->actor_type,
            actorId: $activity->actor_id,
            context: $activity->context,
            createdAt: $activity->created_at->toIso8601String(),
            correlationId: $activity->correlation_id,
            batchId: $activity->batch_id,
        );
    }
}

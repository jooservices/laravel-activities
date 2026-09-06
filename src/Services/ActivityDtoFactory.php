<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Services;

use DateTimeInterface;
use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Models\Activity;

final class ActivityDtoFactory
{
    public static function fromModel(Activity $activity): ActivityDto
    {
        $createdAt = $activity->created_at;

        return ActivityDto::from([
            'id' => (string) $activity->getKey(),
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'activity' => $activity->activity,
            'description' => $activity->description,
            'data' => $activity->data,
            'actor_type' => $activity->actor_type,
            'actor_id' => $activity->actor_id,
            'context' => $activity->context,
            'created_at' => $createdAt instanceof DateTimeInterface
                ? $createdAt
                : (string) $createdAt,
            'correlation_id' => $activity->correlation_id,
            'batch_id' => $activity->batch_id,
            'tenant_id' => $activity->tenant_id,
            'plugin_slug' => $activity->plugin_slug,
        ]);
    }
}

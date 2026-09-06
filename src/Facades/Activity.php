<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Facades;

use Illuminate\Support\Facades\Facade;
use JOOservices\LaravelActivities\ActivityManager;
use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityListDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;

/**
 * @method static ActivityDto record(ActivityRecordDto $record)
 * @method static ActivityDto recordFor(object $subject, string $activity, ?object $actor = null)
 * @method static ActivityListDto list(ActivityFilterDto $filter)
 * @method static ActivityListDto forSubject(object $subject, ?ActivityFilterDto $filter = null)
 * @method static ActivityListDto forActor(object $actor, ?ActivityFilterDto $filter = null)
 */
final class Activity extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActivityManager::class;
    }
}

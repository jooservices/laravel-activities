<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Facades;

use Illuminate\Support\Facades\Facade;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityListDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;

/**
 * @method static ActivityDto record(ActivityRecordDto $record)
 * @method static ActivityDto recordFor(object $subject, string $activity, ?object $actor = null, ?string $description = null, ?array $data = null, ?array $context = null)
 * @method static ActivityListDto list(ActivityFilterDto $filter)
 * @method static ActivityListDto forSubject(object $subject, ?ActivityFilterDto $filter = null)
 */
final class Activity extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActivityRecorderInterface::class;
    }

    public static function list(ActivityFilterDto $filter): ActivityListDto
    {
        return self::$app->make(ActivityQueryInterface::class)->list($filter);
    }

    public static function forSubject(object $subject, ?ActivityFilterDto $filter = null): ActivityListDto
    {
        return self::$app->make(ActivityQueryInterface::class)->forSubject($subject, $filter);
    }
}

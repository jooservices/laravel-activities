<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Facades;

use Illuminate\Support\Facades\Facade;
use JOOservices\LaravelActivities\ActivityManager;

/**
 * @mixin ActivityManager
 */
final class Activity extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActivityManager::class;
    }
}

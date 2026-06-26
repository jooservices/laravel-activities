<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities;

use Illuminate\Support\ServiceProvider;
use JOOservices\LaravelActivities\Console\Commands\EnsureActivityIndexesCommand;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Models\Activity;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use JOOservices\LaravelActivities\Services\ActivityQuery;
use JOOservices\LaravelActivities\Services\ActivityRecorder;

class ActivitiesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/activities.php',
            'activities',
        );

        $this->app->singleton(
            ActivityRepository::class,
            static fn (): ActivityRepository => new ActivityRepository(new Activity()),
        );

        $this->app->singleton(ActivityRecorderInterface::class, ActivityRecorder::class);
        $this->app->singleton(ActivityQueryInterface::class, ActivityQuery::class);
        $this->app->alias(ActivityRecorderInterface::class, 'activities.recorder');
        $this->app->alias(ActivityQueryInterface::class, 'activities.query');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                EnsureActivityIndexesCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/activities.php' => config_path('activities.php'),
            ], 'activities-config');
        }
    }
}

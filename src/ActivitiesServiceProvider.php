<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities;

use Illuminate\Support\ServiceProvider;
use JOOservices\LaravelActivities\Console\Commands\ActivitiesDoctorCommand;
use JOOservices\LaravelActivities\Console\Commands\EnsureActivityIndexesCommand;
use JOOservices\LaravelActivities\Console\Commands\ExportActivitiesCommand;
use JOOservices\LaravelActivities\Console\Commands\PruneActivitiesCommand;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Contracts\ActivitySanitizerInterface;
use JOOservices\LaravelActivities\Models\Activity;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use JOOservices\LaravelActivities\Services\ActivityQuery;
use JOOservices\LaravelActivities\Services\ActivityRecorder;
use JOOservices\LaravelActivities\Support\DefaultActivitySanitizer;
use JOOservices\LaravelActivities\Testing\ArrayActivityStore;

class ActivitiesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/activities.php',
            'activities',
        );

        $this->app->singleton(ActivitySanitizerInterface::class, DefaultActivitySanitizer::class);

        $this->app->singleton(
            ActivityRepository::class,
            static fn (): ActivityRepository => new ActivityRepository(new Activity()),
        );

        $this->registerStoreBindings();

        $this->app->alias(ActivityRecorderInterface::class, 'activities.recorder');
        $this->app->alias(ActivityQueryInterface::class, 'activities.query');
    }

    private function registerStoreBindings(): void
    {
        $this->app->singleton(ArrayActivityStore::class);
        $this->app->singleton(ActivityRecorder::class);
        $this->app->singleton(ActivityQuery::class);

        $this->app->singleton(
            ActivityRecorderInterface::class,
            function ($app): ActivityRecorderInterface {
                if (config('activities.store') === 'array') {
                    return $app->make(ArrayActivityStore::class);
                }

                return $app->make(ActivityRecorder::class);
            },
        );

        $this->app->singleton(
            ActivityQueryInterface::class,
            function ($app): ActivityQueryInterface {
                if (config('activities.store') === 'array') {
                    return $app->make(ArrayActivityStore::class);
                }

                return $app->make(ActivityQuery::class);
            },
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                EnsureActivityIndexesCommand::class,
                ActivitiesDoctorCommand::class,
                PruneActivitiesCommand::class,
                ExportActivitiesCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/activities.php' => config_path('activities.php'),
            ], 'activities-config');
        }
    }
}

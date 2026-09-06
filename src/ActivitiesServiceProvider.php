<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use JOOservices\LaravelActivities\Console\Commands\ActivitiesDoctorCommand;
use JOOservices\LaravelActivities\Console\Commands\EnsureActivityIndexesCommand;
use JOOservices\LaravelActivities\Console\Commands\ExportActivitiesCommand;
use JOOservices\LaravelActivities\Console\Commands\PruneActivitiesCommand;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Contracts\ActivitySanitizerInterface;
use JOOservices\LaravelActivities\Exceptions\InvalidActivityConfigurationException;
use JOOservices\LaravelActivities\Models\Activity;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use JOOservices\LaravelActivities\Services\ActivityPayloadLimiter;
use JOOservices\LaravelActivities\Services\ActivityPayloadPreparer;
use JOOservices\LaravelActivities\Services\ActivityQuery;
use JOOservices\LaravelActivities\Services\ActivityRecorder;
use JOOservices\LaravelActivities\Support\ActivityFilterGuard;
use JOOservices\LaravelActivities\Support\DefaultActivitySanitizer;
use JOOservices\LaravelActivities\Testing\ArrayActivityStore;

final class ActivitiesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/activities.php',
            'activities',
        );

        $this->app->singleton(ActivitySanitizerInterface::class, function (): DefaultActivitySanitizer {
            /** @var list<string> $keys */
            $keys = array_values((array) config('activities.sanitization.sensitive_keys', []));
            /** @var list<string> $patterns */
            $patterns = array_values((array) config('activities.sanitization.sensitive_patterns', []));
            /** @var list<string> $valuePatterns */
            $valuePatterns = array_values((array) config('activities.sanitization.value_patterns', []));

            return new DefaultActivitySanitizer(
                keys: $keys,
                replacement: $this->stringConfig('activities.sanitization.redacted_value', '[redacted]'),
                enabled: (bool) config('activities.sanitization.enabled', true),
                caseSensitive: (bool) config('activities.sanitization.case_sensitive', false),
                patterns: $patterns,
                valuePatterns: $valuePatterns,
            );
        });

        $this->app->singleton(ActivityPayloadLimiter::class, function (): ActivityPayloadLimiter {
            /** @var array<string, mixed> $config */
            $config = (array) config('activities.limits', []);

            return new ActivityPayloadLimiter($config);
        });

        $this->app->singleton(ActivityPayloadPreparer::class);

        $this->app->singleton(ActivityFilterGuard::class, function (): ActivityFilterGuard {
            /** @var list<string> $allow */
            $allow = array_values((array) config('activities.context_keys.allow', []));

            return new ActivityFilterGuard(
                allow: $allow,
                maxLimit: max(1, $this->intConfig('activities.max_limit', 100)),
            );
        });

        $this->app->singleton(
            ActivityRepository::class,
            static fn(): ActivityRepository => new ActivityRepository(new Activity()),
        );

        $this->app->singleton(ArrayActivityStore::class);
        $this->app->singleton(ActivityRecorder::class);
        $this->app->singleton(ActivityQuery::class);

        $this->app->singleton(ActivityRecorderInterface::class, function (Application $app): ActivityRecorderInterface {
            if (config('activities.store') === 'array') {
                return $app->make(ArrayActivityStore::class);
            }

            return $app->make(ActivityRecorder::class);
        });

        $this->app->singleton(ActivityQueryInterface::class, function (Application $app): ActivityQueryInterface {
            if (config('activities.store') === 'array') {
                return $app->make(ArrayActivityStore::class);
            }

            return $app->make(ActivityQuery::class);
        });

        $this->app->singleton(ActivityManager::class, function (Application $app): ActivityManager {
            return new ActivityManager(
                $app->make(ActivityRecorderInterface::class),
                $app->make(ActivityQueryInterface::class),
            );
        });

        $this->app->alias(ActivityRecorderInterface::class, 'activities.recorder');
        $this->app->alias(ActivityQueryInterface::class, 'activities.query');
    }

    public function boot(): void
    {
        $this->failClosedStore();

        if ($this->app->runningInConsole()) {
            $this->commands([
                EnsureActivityIndexesCommand::class,
                ActivitiesDoctorCommand::class,
                PruneActivitiesCommand::class,
                ExportActivitiesCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/activities.php' => config_path('activities.php'),
            ], 'activities-config');
        }
    }

    private function failClosedStore(): void
    {
        $store = $this->stringConfig('activities.store', 'mongodb');

        if (! in_array($store, ['mongodb', 'array'], true)) {
            throw InvalidActivityConfigurationException::unknownStore($store);
        }

        if ($this->app->environment('production') && $store === 'array') {
            throw InvalidActivityConfigurationException::arrayStoreInProduction();
        }
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function intConfig(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_int($value) ? $value : $default;
    }
}

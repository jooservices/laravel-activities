<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Contracts\ActivitySanitizerInterface;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use MongoDB\Laravel\Connection;
use Throwable;

final class ActivitiesDoctorCommand extends Command
{
    protected $signature = 'activities:doctor
        {--json : Output machine-readable JSON}
        {--check-indexes : Verify expected MongoDB indexes exist}
        {--strict : Treat warnings as failures}';

    protected $description = 'Inspect activities configuration, bindings, and MongoDB readiness.';

    public function handle(): int
    {
        $checks = [
            $this->checkConfigLoaded(),
            $this->checkStore(),
            $this->checkMongoConnection(),
            $this->checkBindings(),
            $this->checkSanitizerConfig(),
            $this->checkRetentionConfig(),
            $this->checkIndexesStatus(),
        ];

        $this->renderChecks($checks);

        return $this->exitCode($checks);
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkConfigLoaded(): array
    {
        $config = config('activities');

        if (! is_array($config)) {
            return $this->failed('config.loaded', 'activities config is not loaded as an array.');
        }

        $connection = config('activities.connection');
        $collection = config('activities.collection');

        if (! is_string($connection) || $connection === '' || ! is_string($collection) || $collection === '') {
            return $this->failed('config.storage', 'Connection and collection must be non-empty strings.');
        }

        return $this->passed('config.loaded', "Loaded [{$connection}.{$collection}] activities config.");
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkStore(): array
    {
        $store = (string) config('activities.store', 'mongodb');

        if (! in_array($store, ['mongodb', 'array'], true)) {
            return $this->failed('store', "Unknown store [{$store}].");
        }

        if ($store === 'array' && $this->laravel->environment('production')) {
            return $this->failed('store', 'Array store is not allowed in production.');
        }

        if ($store === 'array') {
            return $this->warning('store', 'Array store is enabled; records will not persist.');
        }

        return $this->passed('store', 'MongoDB store is configured.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkMongoConnection(): array
    {
        if (config('activities.store') === 'array') {
            return $this->warning('mongodb.connection', 'Array store enabled; MongoDB connection not required.');
        }

        try {
            $connectionName = (string) config('activities.connection', 'mongodb');
            $collectionName = (string) config('activities.collection', 'activities');
            $connection = DB::connection($connectionName);

            if (! $connection instanceof Connection) {
                return $this->failed(
                    'mongodb.connection',
                    "Configured connection [{$connectionName}] is not a MongoDB Laravel connection.",
                );
            }

            $connection->getCollection($collectionName)->countDocuments([], ['limit' => 1]);

            return $this->passed(
                'mongodb.connection',
                "MongoDB collection [{$connectionName}.{$collectionName}] is reachable.",
            );
        } catch (Throwable $exception) {
            return $this->failed('mongodb.connection', 'MongoDB reachability check failed: ' . $exception->getMessage());
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkBindings(): array
    {
        try {
            $this->laravel->make(ActivityRecorderInterface::class);
            $this->laravel->make(ActivityQueryInterface::class);
            $this->laravel->make(ActivitySanitizerInterface::class);
        } catch (Throwable $exception) {
            return $this->failed('bindings', $exception->getMessage());
        }

        return $this->passed('bindings', 'Recorder, query, and sanitizer bindings are valid.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkSanitizerConfig(): array
    {
        $keys = config('activities.sanitization.sensitive_keys', []);
        $replacement = config('activities.sanitization.redacted_value');

        if (! is_array($keys)) {
            return $this->failed('sanitize', 'Sanitizer keys must be configured as an array.');
        }

        if (! is_string($replacement) || $replacement === '') {
            return $this->failed('sanitize', 'Sanitizer replacement must be a non-empty string.');
        }

        return $this->passed('sanitize', 'Sanitizer config is valid.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkRetentionConfig(): array
    {
        $days = config('activities.retention.default_days');

        if (! is_int($days) || $days < 1) {
            return $this->failed('retention', 'Retention default_days must be a positive integer.');
        }

        if ((bool) config('activities.retention.enabled', true) === false) {
            return $this->warning('retention', 'Retention is disabled.');
        }

        return $this->passed('retention', 'Retention config is valid.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkIndexesStatus(): array
    {
        $command = Artisan::all()['activities:ensure-indexes'] ?? null;

        if (! $command) {
            return $this->failed('indexes', 'activities:ensure-indexes command is not registered.');
        }

        if (! $this->option('check-indexes') || config('activities.store') === 'array') {
            return $this->passed('indexes', 'activities:ensure-indexes command is registered.');
        }

        try {
            $connectionName = (string) config('activities.connection', 'mongodb');
            $collectionName = (string) config('activities.collection', 'activities');
            $connection = DB::connection($connectionName);

            if (! $connection instanceof Connection) {
                return $this->failed(
                    'indexes',
                    "Configured connection [{$connectionName}] is not a MongoDB Laravel connection.",
                );
            }

            $indexes = iterator_to_array($connection->getCollection($collectionName)->listIndexes());
            $existing = [];

            foreach ($indexes as $index) {
                $existing[] = $index->getName();
            }

            $missing = [];

            foreach (ActivityRepository::expectedIndexes() as $index) {
                $name = (string) ($index['options']['name'] ?? '');
                if ($name !== '' && ! in_array($name, $existing, true)) {
                    $missing[] = $name;
                }
            }

            if ($missing !== []) {
                return $this->warning(
                    'indexes',
                    'Missing expected indexes: ' . implode(', ', $missing) . '. Run activities:ensure-indexes.',
                );
            }

            return $this->passed('indexes', 'All expected activity indexes are present.');
        } catch (Throwable $exception) {
            return $this->warning(
                'indexes',
                'Index status check could not inspect the collection: ' . $exception->getMessage(),
            );
        }
    }

    /**
     * @param  list<array{name: string, status: string, message: string}>  $checks
     */
    private function renderChecks(array $checks): void
    {
        if ($this->option('json')) {
            $this->line((string) json_encode([
                'status' => $this->summaryStatus($checks),
                'checks' => $checks,
            ], JSON_THROW_ON_ERROR));

            return;
        }

        $this->table(
            ['Check', 'Status', 'Message'],
            array_map(
                static fn(array $check): array => [$check['name'], strtoupper($check['status']), $check['message']],
                $checks,
            ),
        );
    }

    /**
     * @param  list<array{name: string, status: string, message: string}>  $checks
     */
    private function exitCode(array $checks): int
    {
        foreach ($checks as $check) {
            if ($check['status'] === 'fail') {
                return self::FAILURE;
            }

            if ($this->option('strict') && $check['status'] === 'warn') {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<array{name: string, status: string, message: string}>  $checks
     */
    private function summaryStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array('fail', $statuses, true)) {
            return 'fail';
        }

        if (in_array('warn', $statuses, true)) {
            return 'warn';
        }

        return 'pass';
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function passed(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'pass', 'message' => $message];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function warning(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'warn', 'message' => $message];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function failed(string $name, string $message): array
    {
        return ['name' => $name, 'status' => 'fail', 'message' => $message];
    }
}

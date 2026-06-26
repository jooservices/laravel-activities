<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Console\Commands;

use Illuminate\Console\Command;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;

final class EnsureActivityIndexesCommand extends Command
{
    protected $signature = 'activities:ensure-indexes';

    protected $description = 'Ensure MongoDB indexes exist for the activities collection.';

    public function handle(ActivityRepository $activities): int
    {
        try {
            $indexes = $activities->ensureIndexes();
        } catch (\Throwable $exception) {
            $this->error('Failed to ensure MongoDB indexes: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Ensured MongoDB indexes [{$indexes}].");

        return self::SUCCESS;
    }
}

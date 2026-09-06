<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests;

use Faker\Factory;
use Faker\Generator;
use JOOservices\LaravelActivities\ActivitiesServiceProvider;
use JOOservices\LaravelActivities\Facades\Activity;
use JOOservices\LaravelActivities\Repositories\ActivityRepository;
use MongoDB\Laravel\MongoDBServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearActivities();
    }

    protected function faker(): Generator
    {
        return Factory::create();
    }

    protected function getPackageProviders($app): array
    {
        return [
            MongoDBServiceProvider::class,
            ActivitiesServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Activity' => Activity::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.connections.mongodb', [
            'driver' => 'mongodb',
            'dsn' => env('MONGODB_URI', 'mongodb://localhost:27017'),
            'database' => env('MONGODB_DATABASE', 'jooservices_activities_testing'),
        ]);

        $app['config']->set('activities.connection', 'mongodb');
        $app['config']->set('activities.collection', 'activities_testing');
        $app['config']->set('activities.store', 'mongodb');
    }

    protected function clearActivities(): void
    {
        $this->app->make(ActivityRepository::class)->flushAll();
    }
}

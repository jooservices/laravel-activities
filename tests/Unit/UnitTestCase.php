<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;

abstract class UnitTestCase extends TestCase
{
    protected function faker(): Generator
    {
        return Factory::create();
    }
}

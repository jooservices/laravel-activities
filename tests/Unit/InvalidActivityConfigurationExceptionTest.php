<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use JOOservices\LaravelActivities\Exceptions\InvalidActivityConfigurationException;
use JOOservices\LaravelActivities\Exceptions\InvalidActivityCursorException;
use JOOservices\LaravelActivities\Exceptions\InvalidActivityFilterException;
use JOOservices\LaravelActivities\Exceptions\InvalidActivitySubjectException;

final class InvalidActivityConfigurationExceptionTest extends UnitTestCase
{
    public function test_store_factories_and_context_copy(): void
    {
        $unknown = InvalidActivityConfigurationException::unknownStore($this->faker()->word());
        $copied = $unknown->withContext(['store' => 'x']);
        $production = InvalidActivityConfigurationException::arrayStoreInProduction();

        $this->assertSame('activities.config.store', $unknown->errorCode());
        $this->assertSame('activities.config.store', $copied->errorCode());
        $this->assertSame('activities.config.store', $production->errorCode());
    }

    public function test_subject_cursor_and_filter_context_copy(): void
    {
        $subject = InvalidActivitySubjectException::emptyKey()->withContext(['id' => '']);
        $cursor = InvalidActivityCursorException::malformed()->withContext(['cursor' => 'x']);
        $filter = InvalidActivityFilterException::cursorWithOffset()->withContext(['pagination' => 'offset']);

        $this->assertSame('activities.subject.invalid', $subject->errorCode());
        $this->assertSame('activities.cursor.invalid', $cursor->errorCode());
        $this->assertSame('activities.filter.pagination', $filter->errorCode());
        $this->assertSame(
            'activities.filter.limit',
            InvalidActivityFilterException::limitOutOfRange(0, 10)->errorCode(),
        );
    }
}

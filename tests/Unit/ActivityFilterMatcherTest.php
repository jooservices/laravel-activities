<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Support\ActivityFilterMatcher;
use JOOservices\LaravelActivities\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ActivityFilterMatcher::class)]
final class ActivityFilterMatcherTest extends TestCase
{
    public function test_matches_subject_context_and_correlation_filters(): void
    {
        $row = [
            'subject_type' => 'App\\Models\\Plugin',
            'subject_id' => '1',
            'activity' => 'plugin.updated',
            'context' => ['plugin_slug' => 'onejav'],
            'correlation_id' => 'corr-1',
            'batch_id' => 'batch-1',
        ];

        $this->assertTrue(ActivityFilterMatcher::matches($row, new ActivityFilterDto(
            subjectType: 'App\\Models\\Plugin',
            subjectId: '1',
            contextKey: 'plugin_slug',
            contextValue: 'onejav',
            correlationId: 'corr-1',
            batchId: 'batch-1',
            activities: ['plugin.updated'],
        )));

        $this->assertFalse(ActivityFilterMatcher::matches($row, new ActivityFilterDto(
            subjectId: '2',
            activities: ['plugin.deleted'],
        )));
    }
}

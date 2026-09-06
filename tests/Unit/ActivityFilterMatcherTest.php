<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Support\ActivityFilterMatcher;

final class ActivityFilterMatcherTest extends UnitTestCase
{
    public function test_plugin_slug_matches_denormalized_field(): void
    {
        $slug = $this->faker()->slug(1);
        $row = [
            'subject_type' => 'Demo',
            'subject_id' => '1',
            'activity' => 'demo.created',
            'context' => [],
            'plugin_slug' => $slug,
            'created_at' => '2026-01-01T00:00:00+00:00',
        ];

        $this->assertTrue(ActivityFilterMatcher::matches($row, new ActivityFilterDto(
            contextKey: 'plugin_slug',
            contextValue: $slug,
        )));
    }

    public function test_activity_prefix_and_tenant_match(): void
    {
        $tenant = (string) $this->faker()->randomNumber(3, true);
        $row = [
            'subject_type' => 'Demo',
            'subject_id' => '1',
            'activity' => 'crawl_target.created',
            'context' => [],
            'tenant_id' => $tenant,
            'created_at' => '2026-01-01T00:00:00+00:00',
        ];

        $this->assertTrue(ActivityFilterMatcher::matches($row, new ActivityFilterDto(
            activityPrefix: 'crawl_target.',
            tenantId: $tenant,
        )));
        $this->assertFalse(ActivityFilterMatcher::matches($row, new ActivityFilterDto(
            activityPrefix: 'user.',
        )));
    }

    public function test_actor_range_and_batch_filters(): void
    {
        $row = [
            'subject_type' => 'Demo',
            'subject_id' => '1',
            'actor_type' => 'User',
            'actor_id' => '9',
            'activity' => 'demo.created',
            'context' => ['site' => 'a'],
            'batch_id' => 'b1',
            'correlation_id' => 'c1',
            'created_at' => '2026-01-02T00:00:00+00:00',
        ];

        $this->assertTrue(ActivityFilterMatcher::matches($row, new ActivityFilterDto(
            actorType: 'User',
            actorId: '9',
            from: '2026-01-01T00:00:00+00:00',
            to: '2026-01-03T00:00:00+00:00',
            batchId: 'b1',
            correlationId: 'c1',
            contextKey: 'site',
            contextValue: 'a',
        )));
        $this->assertFalse(ActivityFilterMatcher::matches($row, new ActivityFilterDto(
            from: '2026-01-03T00:00:00+00:00',
        )));
        $this->assertTrue(ActivityFilterMatcher::matches($row, new ActivityFilterDto(
            activities: ['demo.created'],
        )));
        $this->assertFalse(ActivityFilterMatcher::matches($row, new ActivityFilterDto(
            activities: ['other.event'],
        )));
    }

    public function test_context_key_without_value_does_not_filter(): void
    {
        $row = [
            'subject_type' => 'Demo',
            'subject_id' => (string) $this->faker()->randomNumber(2, true),
            'activity' => $this->faker()->slug(2),
            'context' => ['site' => $this->faker()->slug(1)],
            'created_at' => '2026-01-01T00:00:00+00:00',
        ];

        $this->assertTrue(ActivityFilterMatcher::matches($row, new ActivityFilterDto(
            contextKey: 'site',
            contextValue: null,
        )));
        $this->assertTrue(ActivityFilterMatcher::matches($row, new ActivityFilterDto(
            contextKey: 'missing',
            contextValue: null,
        )));
    }
}

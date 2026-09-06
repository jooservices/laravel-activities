<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use JOOservices\LaravelActivities\Services\ActivityPayloadLimiter;

final class ActivityPayloadLimiterTest extends UnitTestCase
{
    public function test_limit_document_keeps_identity_and_replaces_bags(): void
    {
        $limiter = new ActivityPayloadLimiter([
            'enabled' => true,
            'max_document_bytes' => 280,
            'truncate_marker' => '[truncated]',
        ]);

        $activity = $this->faker()->lexify('??.??');
        $result = $limiter->limitDocument([
            'subject_type' => 'T',
            'subject_id' => '1',
            'activity' => $activity,
            'description' => 'd',
            'data' => ['blob' => str_repeat('a', 500)],
            'context' => ['blob' => str_repeat('b', 500)],
            'tenant_id' => '9',
            'created_at' => '2026-01-01T00:00:00+00:00',
        ]);

        $this->assertSame($activity, $result['activity']);
        $this->assertSame(['__truncated' => '[truncated]'], $result['data']);
        $encoded = json_encode($result);
        $this->assertNotFalse($encoded);
        $this->assertLessThanOrEqual(280, strlen($encoded));
    }

    public function test_it_truncates_strings_and_depth(): void
    {
        $limiter = new ActivityPayloadLimiter([
            'enabled' => true,
            'max_string_length' => 4,
            'max_depth' => 1,
            'max_array_items' => 2,
            'truncate_marker' => '[truncated]',
        ]);

        $limited = $limiter->limitBag([
            'nested' => ['deep' => ['x' => 1]],
            'a' => 'abcdef',
            'c' => 2,
        ]);

        $this->assertSame('abcd[truncated]', $limited['a']);
        $this->assertSame('[truncated]', $limited['nested']);
        $this->assertArrayHasKey('__truncated_items', $limited);
    }

    public function test_identity_only_when_budget_is_tiny(): void
    {
        $limiter = new ActivityPayloadLimiter([
            'enabled' => true,
            'max_document_bytes' => 10,
            'truncate_marker' => '[truncated]',
        ]);

        $result = $limiter->limitDocument([
            'activity' => 'x',
            'data' => ['blob' => str_repeat('a', 40)],
            'context' => ['blob' => str_repeat('b', 40)],
        ]);

        $this->assertSame('x', $result['activity']);
        $this->assertSame(['__truncated' => '[truncated]'], $result['data']);
        $this->assertSame(['__truncated' => '[truncated]'], $result['context']);
    }

    public function test_it_does_not_truncate_redacted_markers(): void
    {
        $limiter = new ActivityPayloadLimiter([
            'max_string_length' => 3,
            'truncate_marker' => '[truncated]',
        ]);

        $limited = $limiter->limitBag(['secret' => '[redacted]']);

        $this->assertSame('[redacted]', $limited['secret']);
    }

    public function test_disabled_limiter_is_a_noop(): void
    {
        $limiter = new ActivityPayloadLimiter(['enabled' => false, 'max_depth' => 1]);
        $payload = ['nested' => ['deep' => ['ok' => true]]];

        $this->assertSame($payload, $limiter->limitBag($payload));
        $this->assertSame($payload, $limiter->limitDocument($payload));
    }
}

<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use DateTimeImmutable;
use JOOservices\LaravelActivities\Support\ActivityCursor;
use JOOservices\LaravelActivities\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ActivityCursor::class)]
final class ActivityCursorTest extends TestCase
{
    public function test_cursor_round_trip_encodes_and_decodes(): void
    {
        $encoded = ActivityCursor::encode(new DateTimeImmutable('2026-01-01T00:00:00+00:00'), 'abc123');
        [$createdAt, $id] = ActivityCursor::decode($encoded);

        $this->assertSame('2026-01-01T00:00:00+00:00', $createdAt);
        $this->assertSame('abc123', $id);
    }
}

<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use DateTimeImmutable;
use JOOservices\LaravelActivities\Exceptions\InvalidActivityCursorException;
use JOOservices\LaravelActivities\Support\ActivityCursor;

final class ActivityCursorTest extends UnitTestCase
{
    public function test_it_round_trips_created_at_and_id(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-02T03:04:05+00:00');
        $id = $this->faker()->uuid();

        [$decodedAt, $decodedId] = ActivityCursor::decode(ActivityCursor::encode($createdAt, $id));

        $this->assertSame($createdAt->format(DATE_ATOM), $decodedAt);
        $this->assertSame($id, $decodedId);
    }

    public function test_it_rejects_malformed_cursors(): void
    {
        $this->expectException(InvalidActivityCursorException::class);

        ActivityCursor::decode('not-a-cursor');
    }

    public function test_it_rejects_empty_parts(): void
    {
        $this->expectException(InvalidActivityCursorException::class);

        ActivityCursor::decode(base64_encode('|id'));
    }
}

<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use InvalidArgumentException;
use JOOservices\LaravelActivities\Support\ActivityCursor;
use JOOservices\LaravelActivities\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ActivityCursor::class)]
final class ActivityCursorInvalidTest extends TestCase
{
    public function test_decode_rejects_invalid_cursor(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ActivityCursor::decode('not-a-valid-cursor');
    }
}

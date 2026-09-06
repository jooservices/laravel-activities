<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Exceptions\InvalidActivityFilterException;
use JOOservices\LaravelActivities\Support\ActivityFilterGuard;

final class ActivityFilterGuardTest extends UnitTestCase
{
    public function test_it_rejects_operator_style_context_keys(): void
    {
        $guard = new ActivityFilterGuard(allow: [], maxLimit: 100);

        $this->expectException(InvalidActivityFilterException::class);

        $guard->assert(new ActivityFilterDto(
            contextKey: '$ne',
            contextValue: '1',
        ));
    }

    public function test_it_rejects_dotted_context_keys(): void
    {
        $guard = new ActivityFilterGuard(allow: [], maxLimit: 100);

        $this->expectException(InvalidActivityFilterException::class);

        $guard->assert(new ActivityFilterDto(
            contextKey: 'a.b',
            contextValue: '1',
        ));
    }

    public function test_page_two_without_offset_throws(): void
    {
        $guard = new ActivityFilterGuard(allow: [], maxLimit: 100);

        $this->expectException(InvalidActivityFilterException::class);

        $guard->assert(new ActivityFilterDto(page: 2));
    }

    public function test_allowlist_rejects_unknown_keys(): void
    {
        $guard = new ActivityFilterGuard(allow: ['plugin_slug'], maxLimit: 100);

        $this->expectException(InvalidActivityFilterException::class);

        $guard->assert(new ActivityFilterDto(
            contextKey: 'site_id',
            contextValue: '1',
        ));
    }

    public function test_offset_cannot_combine_with_cursor(): void
    {
        $guard = new ActivityFilterGuard(allow: [], maxLimit: 10);

        $this->expectException(InvalidActivityFilterException::class);

        $guard->assert(new ActivityFilterDto(
            pagination: 'offset',
            cursor: 'abc',
        ));
    }

    public function test_invalid_pagination_and_limit(): void
    {
        $guard = new ActivityFilterGuard(allow: [], maxLimit: 10);

        $this->expectException(InvalidActivityFilterException::class);

        $guard->assert(new ActivityFilterDto(pagination: 'foo'));
    }

    public function test_capped_limit_returns_configured_max(): void
    {
        $guard = new ActivityFilterGuard(allow: [], maxLimit: 10);

        $this->assertSame(5, $guard->cappedLimit(new ActivityFilterDto(limit: 5)));
    }
}

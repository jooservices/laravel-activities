<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Support;

use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Exceptions\InvalidActivityFilterException;

final class ActivityFilterGuard
{
    /**
     * @param  list<string>  $allow
     */
    public function __construct(
        private readonly array $allow,
        private readonly int $maxLimit,
    ) {
    }

    public function assert(ActivityFilterDto $filter): void
    {
        $this->assertLimit($filter);
        $this->assertContextKey($filter);
        $this->assertPagination($filter);
    }

    public function cappedLimit(ActivityFilterDto $filter): int
    {
        $this->assertLimit($filter);

        return min($filter->limit, $this->maxLimit);
    }

    private function assertLimit(ActivityFilterDto $filter): void
    {
        if ($filter->limit < 1 || $filter->limit > $this->maxLimit) {
            throw InvalidActivityFilterException::limitOutOfRange($filter->limit, $this->maxLimit);
        }
    }

    private function assertContextKey(ActivityFilterDto $filter): void
    {
        $key = $filter->contextKey;
        if ($key === null || $key === '') {
            return;
        }

        if (preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $key) !== 1) {
            throw InvalidActivityFilterException::illegalContextKey($key);
        }

        if ($this->allow !== [] && ! in_array($key, $this->allow, true)) {
            throw InvalidActivityFilterException::disallowedContextKey($key);
        }
    }

    private function assertPagination(ActivityFilterDto $filter): void
    {
        if (! in_array($filter->pagination, ['cursor', 'offset'], true)) {
            throw InvalidActivityFilterException::invalidPagination($filter->pagination);
        }

        $hasCursor = $filter->cursor !== null && $filter->cursor !== '';

        if ($filter->pagination === 'offset' && $hasCursor) {
            throw InvalidActivityFilterException::cursorWithOffset();
        }

        if ($filter->pagination !== 'offset' && $filter->page > 1 && ! $hasCursor) {
            throw InvalidActivityFilterException::pageRequiresOffset();
        }
    }
}

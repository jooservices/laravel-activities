<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Exceptions;

use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\LogLevel;
use Throwable;

final class InvalidActivityFilterException extends LaravelActivitiesException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ExceptionContext $context = null,
        private readonly string $filterErrorCode = 'activities.filter.invalid',
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public static function illegalContextKey(string $key): self
    {
        return (new self(
            'Activity context key is not a valid field name.',
            filterErrorCode: 'activities.filter.context_key',
        ))->withContext(['context_key' => $key]);
    }

    public static function disallowedContextKey(string $key): self
    {
        return (new self(
            'Activity context key is not allowed.',
            filterErrorCode: 'activities.filter.context_key',
        ))->withContext(['context_key' => $key]);
    }

    public static function limitOutOfRange(int $limit, int $max): self
    {
        return (new self(
            'Activity list limit is out of range.',
            filterErrorCode: 'activities.filter.limit',
        ))->withContext(['limit' => $limit, 'min' => 1, 'max' => $max]);
    }

    public static function pageRequiresOffset(): self
    {
        return new self(
            'Offset pagination requires pagination=offset when page is greater than 1.',
            filterErrorCode: 'activities.filter.pagination',
        );
    }

    public static function cursorWithOffset(): self
    {
        return new self(
            'Cursor cannot be combined with offset pagination.',
            filterErrorCode: 'activities.filter.pagination',
        );
    }

    public static function invalidPagination(string $pagination): self
    {
        return (new self(
            'Pagination must be cursor or offset.',
            filterErrorCode: 'activities.filter.pagination',
        ))->withContext(['pagination' => $pagination]);
    }

    public function errorCode(): string
    {
        return $this->filterErrorCode;
    }

    public function logLevel(): string
    {
        return LogLevel::WARNING->value;
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self(
            $this->getMessage(),
            $this->getCode(),
            $this->getPrevious(),
            $context,
            $this->filterErrorCode,
        );
    }
}

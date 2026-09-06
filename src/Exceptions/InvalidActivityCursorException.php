<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Exceptions;

use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\LogLevel;
use Throwable;

final class InvalidActivityCursorException extends LaravelActivitiesException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ExceptionContext $context = null,
        private readonly string $cursorErrorCode = 'activities.cursor.invalid',
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public static function malformed(): self
    {
        return new self('Invalid activity cursor.');
    }

    public function errorCode(): string
    {
        return $this->cursorErrorCode;
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
            $this->cursorErrorCode,
        );
    }
}

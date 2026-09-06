<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Exceptions;

use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\LogLevel;
use Throwable;

final class InvalidActivitySubjectException extends LaravelActivitiesException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ExceptionContext $context = null,
        private readonly string $subjectErrorCode = 'activities.subject.invalid',
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public static function missingKey(): self
    {
        return new self('Activity subject must expose getKey().');
    }

    public static function emptyKey(): self
    {
        return new self('Activity subject must have a persisted key.');
    }

    public static function emptyExternal(): self
    {
        return new self('External activity identity requires a non-empty type and id.');
    }

    public function errorCode(): string
    {
        return $this->subjectErrorCode;
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
            $this->subjectErrorCode,
        );
    }
}

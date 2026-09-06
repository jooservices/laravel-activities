<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Exceptions;

use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\LogLevel;
use Throwable;

final class InvalidActivityConfigurationException extends LaravelActivitiesException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ExceptionContext $context = null,
        private readonly string $configErrorCode = 'activities.config.invalid',
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public static function unknownStore(string $store): self
    {
        return (new self(
            'Unknown activities store driver.',
            configErrorCode: 'activities.config.store',
        ))->withContext(['store' => $store]);
    }

    public static function arrayStoreInProduction(): self
    {
        return new self(
            'The array activities store is not allowed in production.',
            configErrorCode: 'activities.config.store',
        );
    }

    public function errorCode(): string
    {
        return $this->configErrorCode;
    }

    public function logLevel(): string
    {
        return LogLevel::ERROR->value;
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self(
            $this->getMessage(),
            $this->getCode(),
            $this->getPrevious(),
            $context,
            $this->configErrorCode,
        );
    }
}

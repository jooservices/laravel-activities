<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Support;

use InvalidArgumentException;

final class SubjectReference
{
    public function __construct(
        public readonly string $type,
        public readonly string $id,
    ) {}

    public static function fromObject(object $subject): self
    {
        if (! method_exists($subject, 'getKey')) {
            throw new InvalidArgumentException('Activity subject must expose getKey().');
        }

        $key = $subject->getKey();
        if ($key === null || $key === '') {
            throw new InvalidArgumentException('Activity subject must have a persisted key.');
        }

        return new self($subject::class, (string) $key);
    }
}

<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Support;

use JOOservices\LaravelActivities\Exceptions\InvalidActivitySubjectException;

final class SubjectReference
{
    public function __construct(
        public readonly string $type,
        public readonly string $id,
    ) {
    }

    public static function fromObject(object $subject): self
    {
        if (! method_exists($subject, 'getKey')) {
            throw InvalidActivitySubjectException::missingKey();
        }

        $key = $subject->getKey();
        if ($key === null || $key === '') {
            throw InvalidActivitySubjectException::emptyKey();
        }

        return new self($subject::class, (string) $key);
    }

    public static function fromExternal(string $type, string $id): self
    {
        if ($type === '' || $id === '') {
            throw InvalidActivitySubjectException::emptyExternal();
        }

        return new self($type, $id);
    }
}

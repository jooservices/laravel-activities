<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests;

final class TestSubject
{
    public function __construct(private readonly int $id)
    {
    }

    public function getKey(): int
    {
        return $this->id;
    }
}

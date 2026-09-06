<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Contracts;

interface ActivitySanitizerInterface
{
    /**
     * @param  array<array-key, mixed>|null  $payload
     * @return array<array-key, mixed>|null
     */
    public function sanitize(?array $payload): ?array;
}

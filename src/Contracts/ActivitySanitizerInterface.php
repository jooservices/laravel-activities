<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Contracts;

interface ActivitySanitizerInterface
{
    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    public function sanitize(?array $payload): ?array;
}

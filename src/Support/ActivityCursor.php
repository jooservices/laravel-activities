<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Support;

use DateTimeInterface;
use JOOservices\LaravelActivities\Exceptions\InvalidActivityCursorException;

final class ActivityCursor
{
    public static function encode(DateTimeInterface $createdAt, string $id): string
    {
        return base64_encode($createdAt->format(DateTimeInterface::ATOM) . '|' . $id);
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function decode(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);

        if (! is_string($decoded) || ! str_contains($decoded, '|')) {
            throw InvalidActivityCursorException::malformed();
        }

        [$createdAt, $id] = explode('|', $decoded, 2);

        if ($createdAt === '' || $id === '') {
            throw InvalidActivityCursorException::malformed();
        }

        return [$createdAt, $id];
    }
}

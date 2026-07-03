<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Support;

use DateTimeInterface;
use InvalidArgumentException;

final class ActivityCursor
{
    public static function encode(DateTimeInterface $createdAt, string $id): string
    {
        return base64_encode($createdAt->format(DateTimeInterface::ATOM).'|'.$id);
    }

    /**
     * @return array{0: string, 1: string}
     */
    public static function decode(string $cursor): array
    {
        $decoded = base64_decode($cursor, true);

        if (! is_string($decoded) || ! str_contains($decoded, '|')) {
            throw new InvalidArgumentException('Invalid activity cursor.');
        }

        [$createdAt, $id] = explode('|', $decoded, 2);

        if ($createdAt === '' || $id === '') {
            throw new InvalidArgumentException('Invalid activity cursor.');
        }

        return [$createdAt, $id];
    }
}

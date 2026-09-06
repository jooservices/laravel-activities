<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Support;

use Illuminate\Support\Carbon;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;

final class ActivityFilterMatcher
{
    /**
     * @param  array<string, mixed>  $row
     */
    public static function matches(array $row, ActivityFilterDto $filter): bool
    {
        return self::matchesSubject($row, $filter)
            && self::matchesActor($row, $filter)
            && self::matchesContext($row, $filter)
            && self::matchesActivities($row, $filter)
            && self::matchesActivityPrefix($row, $filter)
            && self::matchesRange($row, $filter)
            && self::matchesCorrelation($row, $filter)
            && self::matchesBatch($row, $filter)
            && self::matchesTenant($row, $filter);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function matchesSubject(array $row, ActivityFilterDto $filter): bool
    {
        if ($filter->subjectType !== null && $row['subject_type'] !== $filter->subjectType) {
            return false;
        }

        if ($filter->subjectId !== null && $row['subject_id'] !== $filter->subjectId) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function matchesActor(array $row, ActivityFilterDto $filter): bool
    {
        if ($filter->actorType !== null && ($row['actor_type'] ?? null) !== $filter->actorType) {
            return false;
        }

        if ($filter->actorId !== null && ($row['actor_id'] ?? null) !== $filter->actorId) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function matchesContext(array $row, ActivityFilterDto $filter): bool
    {
        if ($filter->contextKey === null || $filter->contextKey === '') {
            return true;
        }

        if ($filter->contextKey === 'plugin_slug') {
            $top = $row['plugin_slug'] ?? null;
            if ($top !== null) {
                return (string) $top === (string) $filter->contextValue;
            }
        }

        $context = is_array($row['context'] ?? null) ? $row['context'] : [];
        $contextValue = $context[$filter->contextKey] ?? null;

        return (string) $contextValue === (string) $filter->contextValue;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function matchesActivities(array $row, ActivityFilterDto $filter): bool
    {
        if ($filter->activities === null) {
            return true;
        }

        return in_array($row['activity'], $filter->activities, true);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function matchesActivityPrefix(array $row, ActivityFilterDto $filter): bool
    {
        if ($filter->activityPrefix === null || $filter->activityPrefix === '') {
            return true;
        }

        return str_starts_with((string) $row['activity'], $filter->activityPrefix);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function matchesRange(array $row, ActivityFilterDto $filter): bool
    {
        $createdAt = Carbon::parse((string) $row['created_at']);

        if ($filter->from !== null && $filter->from !== '' && $createdAt->lt(Carbon::parse($filter->from))) {
            return false;
        }

        if ($filter->to !== null && $filter->to !== '' && ! $createdAt->lt(Carbon::parse($filter->to))) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function matchesCorrelation(array $row, ActivityFilterDto $filter): bool
    {
        if ($filter->correlationId === null) {
            return true;
        }

        return ($row['correlation_id'] ?? null) === $filter->correlationId;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function matchesBatch(array $row, ActivityFilterDto $filter): bool
    {
        if ($filter->batchId === null) {
            return true;
        }

        return ($row['batch_id'] ?? null) === $filter->batchId;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function matchesTenant(array $row, ActivityFilterDto $filter): bool
    {
        if ($filter->tenantId === null) {
            return true;
        }

        return ($row['tenant_id'] ?? null) === $filter->tenantId;
    }
}

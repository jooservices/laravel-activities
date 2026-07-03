<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Support;

use JOOservices\LaravelActivities\Dto\ActivityFilterDto;

final class ActivityFilterMatcher
{
    /**
     * @param  array<string, mixed>  $row
     */
    public static function matches(array $row, ActivityFilterDto $filter): bool
    {
        return self::matchesSubject($row, $filter)
            && self::matchesContext($row, $filter)
            && self::matchesActivities($row, $filter)
            && self::matchesCorrelation($row, $filter)
            && self::matchesBatch($row, $filter);
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
    private static function matchesContext(array $row, ActivityFilterDto $filter): bool
    {
        if ($filter->contextKey === null) {
            return true;
        }

        $context = is_array($row['context']) ? $row['context'] : [];
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
    private static function matchesCorrelation(array $row, ActivityFilterDto $filter): bool
    {
        if ($filter->correlationId === null) {
            return true;
        }

        return $row['correlation_id'] === $filter->correlationId;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function matchesBatch(array $row, ActivityFilterDto $filter): bool
    {
        if ($filter->batchId === null) {
            return true;
        }

        return $row['batch_id'] === $filter->batchId;
    }
}

<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Testing;

use Illuminate\Support\Carbon;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Contracts\ActivitySanitizerInterface;
use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityListDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Support\ActivityCursor;
use JOOservices\LaravelActivities\Support\ActivityFilterMatcher;
use JOOservices\LaravelActivities\Support\SubjectReference;

final class ArrayActivityStore implements ActivityQueryInterface, ActivityRecorderInterface
{
    /** @var list<array<string, mixed>> */
    private array $records = [];

    private int $sequence = 0;

    public function __construct(
        private readonly ActivitySanitizerInterface $sanitizer,
    ) {}

    public function record(ActivityRecordDto $record): ActivityDto
    {
        $this->sequence++;
        $context = $this->sanitizer->sanitize($record->context);
        $createdAt = Carbon::now()->toIso8601String();

        $row = [
            'id' => 'memory:'.$this->sequence,
            'subject_type' => $record->subjectType,
            'subject_id' => $record->subjectId,
            'activity' => $record->activity,
            'description' => $record->description,
            'data' => $this->sanitizer->sanitize($record->data),
            'actor_type' => $record->actorType,
            'actor_id' => $record->actorId,
            'context' => $context,
            'correlation_id' => $record->correlationId,
            'batch_id' => $record->batchId,
            'created_at' => $createdAt,
        ];

        $this->records[] = $row;

        return $this->toDto($row);
    }

    public function recordFor(
        object $subject,
        string $activity,
        ?object $actor = null,
        ?string $description = null,
        ?array $data = null,
        ?array $context = null,
    ): ActivityDto {
        $subjectRef = SubjectReference::fromObject($subject);
        $actorRef = $actor !== null ? SubjectReference::fromObject($actor) : null;

        return $this->record(new ActivityRecordDto(
            subjectType: $subjectRef->type,
            subjectId: $subjectRef->id,
            activity: $activity,
            description: $description,
            data: $data,
            actorType: $actorRef?->type,
            actorId: $actorRef?->id,
            context: $context,
        ));
    }

    public function list(ActivityFilterDto $filter): ActivityListDto
    {
        $items = $this->matching($filter);

        if ($filter->cursor !== null && $filter->cursor !== '') {
            [$createdAt, $id] = ActivityCursor::decode($filter->cursor);
            $parsed = Carbon::parse($createdAt);
            $items = array_values(array_filter(
                $items,
                static fn (array $row): bool => Carbon::parse($row['created_at'])->lt($parsed)
                    || (Carbon::parse($row['created_at'])->equalTo($parsed) && $row['id'] < $id),
            ));
        }

        $limit = max(1, $filter->limit);
        $nextCursor = null;
        $slice = array_slice($items, 0, $limit + 1);

        if (count($slice) > $limit) {
            $last = $slice[$limit - 1];
            $nextCursor = ActivityCursor::encode(Carbon::parse($last['created_at']), $last['id']);
            $slice = array_slice($slice, 0, $limit);
        }

        if ($this->usesCursorPagination($filter)) {
            $mapped = array_map(fn (array $row): ActivityDto => $this->toDto($row), $slice);

            return new ActivityListDto(
                items: $mapped,
                total: count($mapped),
                page: 1,
                perPage: $limit,
                lastPage: $nextCursor === null ? 1 : 2,
                nextCursor: $nextCursor,
            );
        }

        $page = max(1, $filter->page);
        $total = count($items);
        $offset = ($page - 1) * $limit;
        $pageItems = array_slice($items, $offset, $limit);

        return new ActivityListDto(
            items: array_map(fn (array $row): ActivityDto => $this->toDto($row), $pageItems),
            total: $total,
            page: $page,
            perPage: $limit,
            lastPage: max(1, (int) ceil($total / $limit)),
        );
    }

    private function usesCursorPagination(ActivityFilterDto $filter): bool
    {
        return $filter->page <= 1
            || ($filter->cursor !== null && $filter->cursor !== '');
    }

    public function forSubject(object $subject, ?ActivityFilterDto $filter = null): ActivityListDto
    {
        $subjectRef = SubjectReference::fromObject($subject);
        $base = $filter ?? new ActivityFilterDto();

        return $this->list(new ActivityFilterDto(
            subjectType: $subjectRef->type,
            subjectId: $subjectRef->id,
            contextKey: $base->contextKey,
            contextValue: $base->contextValue,
            activities: $base->activities,
            limit: $base->limit,
            page: $base->page,
            cursor: $base->cursor,
            correlationId: $base->correlationId,
            batchId: $base->batchId,
        ));
    }

    public function flush(): void
    {
        $this->records = [];
        $this->sequence = 0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function matching(ActivityFilterDto $filter): array
    {
        $items = array_values(array_filter(
            $this->records,
            static fn (array $row): bool => ActivityFilterMatcher::matches($row, $filter),
        ));

        usort(
            $items,
            static fn (array $left, array $right): int => [
                $right['created_at'],
                $right['id'],
            ] <=> [
                $left['created_at'],
                $left['id'],
            ],
        );

        return $items;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function toDto(array $row): ActivityDto
    {
        return new ActivityDto(
            id: (string) $row['id'],
            subjectType: (string) $row['subject_type'],
            subjectId: (string) $row['subject_id'],
            activity: (string) $row['activity'],
            description: isset($row['description']) ? (string) $row['description'] : null,
            data: is_array($row['data']) ? $row['data'] : null,
            actorType: isset($row['actor_type']) ? (string) $row['actor_type'] : null,
            actorId: isset($row['actor_id']) ? (string) $row['actor_id'] : null,
            context: is_array($row['context']) ? $row['context'] : null,
            createdAt: (string) $row['created_at'],
            correlationId: isset($row['correlation_id']) ? (string) $row['correlation_id'] : null,
            batchId: isset($row['batch_id']) ? (string) $row['batch_id'] : null,
        );
    }
}

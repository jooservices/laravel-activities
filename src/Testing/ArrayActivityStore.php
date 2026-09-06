<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Testing;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityListDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Services\ActivityPayloadPreparer;
use JOOservices\LaravelActivities\Support\ActivityCursor;
use JOOservices\LaravelActivities\Support\ActivityFilterGuard;
use JOOservices\LaravelActivities\Support\ActivityFilterMatcher;
use JOOservices\LaravelActivities\Support\SubjectReference;

final class ArrayActivityStore implements ActivityQueryInterface, ActivityRecorderInterface
{
    /** @var list<array<string, mixed>> */
    private array $records = [];

    private int $sequence = 0;

    public function __construct(
        private readonly ActivityPayloadPreparer $preparer,
        private readonly ActivityFilterGuard $guard,
    ) {
    }

    public function record(ActivityRecordDto $record): ActivityDto
    {
        $this->sequence++;
        $persist = $this->preparer->prepare($record);
        $createdAt = $persist['created_at'];
        $createdAtString = $createdAt instanceof DateTimeInterface
            ? $createdAt->format(DateTimeInterface::ATOM)
            : (string) $createdAt;

        $row = [
            'id' => 'memory:' . $this->sequence,
            'subject_type' => $persist['subject_type'],
            'subject_id' => $persist['subject_id'],
            'activity' => $persist['activity'],
            'description' => $persist['description'] ?? null,
            'data' => is_array($persist['data'] ?? null) ? $persist['data'] : null,
            'actor_type' => $persist['actor_type'] ?? null,
            'actor_id' => $persist['actor_id'] ?? null,
            'context' => is_array($persist['context'] ?? null) ? $persist['context'] : null,
            'plugin_slug' => $persist['plugin_slug'] ?? null,
            'correlation_id' => $persist['correlation_id'] ?? null,
            'batch_id' => $persist['batch_id'] ?? null,
            'tenant_id' => $persist['tenant_id'] ?? null,
            'created_at' => $createdAtString,
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
        ?string $correlationId = null,
        ?string $batchId = null,
        ?string $tenantId = null,
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
            correlationId: $correlationId,
            batchId: $batchId,
            tenantId: $tenantId,
        ));
    }

    public function list(ActivityFilterDto $filter): ActivityListDto
    {
        $this->guard->assert($filter);
        $items = $this->matching($filter);
        $limit = $this->guard->cappedLimit($filter);

        if ($filter->cursor !== null && $filter->cursor !== '') {
            [$createdAt, $id] = ActivityCursor::decode($filter->cursor);
            $parsed = Carbon::parse($createdAt);
            $items = array_values(array_filter(
                $items,
                static fn(array $row): bool => Carbon::parse((string) $row['created_at'])->lt($parsed)
                    || (Carbon::parse((string) $row['created_at'])->equalTo($parsed) && (string) $row['id'] < $id),
            ));
        }

        if ($filter->pagination !== 'offset') {
            $nextCursor = null;
            $slice = array_slice($items, 0, $limit + 1);

            if (count($slice) > $limit) {
                $last = $slice[$limit - 1];
                $nextCursor = ActivityCursor::encode(Carbon::parse((string) $last['created_at']), (string) $last['id']);
                $slice = array_slice($slice, 0, $limit);
            }

            $mapped = array_map(fn(array $row): ActivityDto => $this->toDto($row), $slice);

            return new ActivityListDto(
                items: $mapped,
                perPage: $limit,
                nextCursor: $nextCursor,
                hasMore: $nextCursor !== null,
            );
        }

        $page = max(1, $filter->page);
        $total = count($items);
        $offset = ($page - 1) * $limit;
        $pageItems = array_slice($items, $offset, $limit);

        return new ActivityListDto(
            items: array_map(fn(array $row): ActivityDto => $this->toDto($row), $pageItems),
            perPage: $limit,
            total: $total,
            page: $page,
            lastPage: max(1, (int) ceil($total / $limit)),
            hasMore: $page * $limit < $total,
        );
    }

    public function forSubject(object $subject, ?ActivityFilterDto $filter = null): ActivityListDto
    {
        $subjectRef = SubjectReference::fromObject($subject);
        $base = $filter ?? new ActivityFilterDto();

        return $this->list($base->with(
            subjectType: $subjectRef->type,
            subjectId: $subjectRef->id,
        ));
    }

    public function forActor(object $actor, ?ActivityFilterDto $filter = null): ActivityListDto
    {
        $actorRef = SubjectReference::fromObject($actor);
        $base = $filter ?? new ActivityFilterDto();

        return $this->list($base->with(
            actorType: $actorRef->type,
            actorId: $actorRef->id,
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
            static fn(array $row): bool => ActivityFilterMatcher::matches($row, $filter),
        ));

        usort(
            $items,
            static fn(array $left, array $right): int => [
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
        return ActivityDto::from([
            'id' => (string) $row['id'],
            'subject_type' => (string) $row['subject_type'],
            'subject_id' => (string) $row['subject_id'],
            'activity' => (string) $row['activity'],
            'description' => isset($row['description']) ? (string) $row['description'] : null,
            'data' => is_array($row['data'] ?? null) ? $row['data'] : null,
            'actor_type' => isset($row['actor_type']) ? (string) $row['actor_type'] : null,
            'actor_id' => isset($row['actor_id']) ? (string) $row['actor_id'] : null,
            'context' => is_array($row['context'] ?? null) ? $row['context'] : null,
            'created_at' => (string) $row['created_at'],
            'correlation_id' => isset($row['correlation_id']) ? (string) $row['correlation_id'] : null,
            'batch_id' => isset($row['batch_id']) ? (string) $row['batch_id'] : null,
            'tenant_id' => isset($row['tenant_id']) ? (string) $row['tenant_id'] : null,
            'plugin_slug' => isset($row['plugin_slug']) ? (string) $row['plugin_slug'] : null,
        ]);
    }
}

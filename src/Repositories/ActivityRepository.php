<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Repositories;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Models\Activity;
use JOOservices\LaravelActivities\Support\ActivityCursor;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Traits\HasCrud;
use MongoDB\BSON\ObjectId;
use MongoDB\Laravel\Connection;

final class ActivityRepository extends EloquentRepository
{
    use HasCrud;

    public function __construct(Activity $model)
    {
        parent::__construct($model);
    }

    public function createActivity(array $data): Activity
    {
        /** @var Activity $activity */
        $activity = $this->create($data);

        return $activity;
    }

    /**
     * @return LengthAwarePaginator<int, Activity>
     */
    public function paginateByFilter(ActivityFilterDto $filter): LengthAwarePaginator
    {
        $this->resetQueryState();

        $perPage = min($filter->limit, (int) config('activities.max_limit', 100));
        $page = max(1, $filter->page);

        $query = $this->filterQuery($filter)
            ->orderByDesc('created_at')
            ->orderByDesc('_id');

        $total = $query->count();
        /** @var Collection<int, Activity> $items */
        $items = $query->forPage($page, $perPage)->get();

        return new Paginator(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $page,
        );
    }

    /**
     * @return array{0: Collection<int, Activity>, 1: ?string}
     */
    public function cursorPaginateByFilter(ActivityFilterDto $filter): array
    {
        $this->resetQueryState();

        $limit = min($filter->limit, (int) config('activities.max_limit', 100));
        $query = $this->filterQuery($filter);
        $this->applyCursorFilter($query->getQuery(), $filter);

        /** @var Collection<int, Activity> $items */
        $items = $query
            ->orderByDesc('created_at')
            ->orderByDesc('_id')
            ->limit($limit + 1)
            ->get();

        $nextCursor = null;

        if ($items->count() > $limit) {
            $items = $items->take($limit);
            $last = $items->last();

            if ($last instanceof Activity) {
                $nextCursor = ActivityCursor::encode($last->created_at, (string) $last->getKey());
            }
        }

        return [$items, $nextCursor];
    }

    /**
     * @return Collection<int, Activity>
     */
    public function latestByFilter(ActivityFilterDto $filter, int $limit): Collection
    {
        $this->resetQueryState();

        return $this->filterQuery($filter)
            ->orderByDesc('created_at')
            ->orderByDesc('_id')
            ->limit($limit)
            ->get();
    }

    public function deleteOlderThan(DateTimeInterface $cutoff, ?ActivityFilterDto $filter = null): int
    {
        $this->resetQueryState();

        $query = $this->filterQuery($filter ?? new ActivityFilterDto())
            ->where('created_at', '<', CarbonImmutable::instance($cutoff));

        $deleted = 0;

        foreach ($query->cursor() as $activity) {
            if ($activity->delete() === true) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public function countOlderThan(DateTimeInterface $cutoff, ?ActivityFilterDto $filter = null): int
    {
        $this->resetQueryState();

        return $this->filterQuery($filter ?? new ActivityFilterDto())
            ->where('created_at', '<', CarbonImmutable::instance($cutoff))
            ->count();
    }

    /**
     * @return list<array{keys: array<string, int>, options: array<string, mixed>}>
     */
    public static function expectedIndexes(): array
    {
        return [
            [
                'keys' => ['subject_type' => 1, 'subject_id' => 1, 'created_at' => -1],
                'options' => ['name' => 'activities_subject_created_at'],
            ],
            [
                'keys' => ['plugin_slug' => 1, 'created_at' => -1],
                'options' => ['name' => 'activities_plugin_slug_created_at'],
            ],
            [
                'keys' => ['correlation_id' => 1, 'created_at' => -1],
                'options' => ['name' => 'activities_correlation_created_at'],
            ],
            [
                'keys' => ['batch_id' => 1, 'created_at' => -1],
                'options' => ['name' => 'activities_batch_created_at'],
            ],
        ];
    }

    public function ensureIndexes(): string
    {
        $model = $this->activityModel();
        /** @var Connection $connection */
        $connection = $model->getConnection();
        $collection = $connection->getCollection($model->getTable());

        $names = [];

        foreach (self::expectedIndexes() as $index) {
            $collection->createIndex($index['keys'], $index['options']);
            $names[] = $index['options']['name'] ?? json_encode($index['keys'], JSON_THROW_ON_ERROR);
        }

        return implode(', ', $names);
    }

    public function flushAll(): void
    {
        $this->resetQueryState();
        $this->getModel()->newQuery()->delete();
    }

    private function resetQueryState(): void
    {
        $this->query = null;
    }

    /**
     * @return Builder<Activity>
     */
    private function filterQuery(ActivityFilterDto $filter): Builder
    {
        $this->resetQueryState();

        /** @var Builder<Activity> $query */
        $query = $this->getModel()->newQuery();
        $mongoQuery = $query->getQuery();

        $this->applySubjectFilter($mongoQuery, $filter);
        $this->applyContextFilter($mongoQuery, $filter);
        $this->applyActivitiesFilter($mongoQuery, $filter);
        $this->applyCorrelationFilter($mongoQuery, $filter);
        $this->applyBatchFilter($mongoQuery, $filter);

        return $query;
    }

    private function applySubjectFilter(QueryBuilder $mongoQuery, ActivityFilterDto $filter): void
    {
        if ($filter->subjectType !== null && $filter->subjectType !== '') {
            $mongoQuery->where('subject_type', '=', $filter->subjectType);
        }

        if ($filter->subjectId !== null && $filter->subjectId !== '') {
            $mongoQuery->where('subject_id', '=', $filter->subjectId);
        }
    }

    private function applyContextFilter(QueryBuilder $mongoQuery, ActivityFilterDto $filter): void
    {
        if ($filter->contextKey === null || $filter->contextKey === '' || $filter->contextValue === null) {
            return;
        }

        $contextField = $filter->contextKey === 'plugin_slug'
            ? 'plugin_slug'
            : 'context.'.$filter->contextKey;
        $mongoQuery->where($contextField, '=', $filter->contextValue);
    }

    private function applyActivitiesFilter(QueryBuilder $mongoQuery, ActivityFilterDto $filter): void
    {
        if ($filter->activities === null || $filter->activities === []) {
            return;
        }

        $mongoQuery->whereIn('activity', $filter->activities);
    }

    private function applyCorrelationFilter(QueryBuilder $mongoQuery, ActivityFilterDto $filter): void
    {
        if ($filter->correlationId === null || $filter->correlationId === '') {
            return;
        }

        $mongoQuery->where('correlation_id', '=', $filter->correlationId);
    }

    private function applyBatchFilter(QueryBuilder $mongoQuery, ActivityFilterDto $filter): void
    {
        if ($filter->batchId === null || $filter->batchId === '') {
            return;
        }

        $mongoQuery->where('batch_id', '=', $filter->batchId);
    }

    private function applyCursorFilter(QueryBuilder $mongoQuery, ActivityFilterDto $filter): void
    {
        if ($filter->cursor === null || $filter->cursor === '') {
            return;
        }

        [$createdAt, $id] = ActivityCursor::decode($filter->cursor);
        $parsed = Carbon::parse($createdAt);
        $idOperand = preg_match('/^[a-f0-9]{24}$/i', $id) === 1 ? new ObjectId($id) : $id;

        $mongoQuery->where(function (QueryBuilder $builder) use ($parsed, $idOperand): void {
            $builder->where('created_at', '<', $parsed)
                ->orWhere(function (QueryBuilder $nested) use ($parsed, $idOperand): void {
                    $nested->where('created_at', '=', $parsed)
                        ->where('_id', '<', $idOperand);
                });
        });
    }

    private function activityModel(): Activity
    {
        $model = $this->getModel();
        assert($model instanceof Activity);

        return $model;
    }
}

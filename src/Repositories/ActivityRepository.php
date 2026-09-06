<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Repositories;

use Carbon\CarbonImmutable;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as BaseCollection;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Models\Activity;
use JOOservices\LaravelActivities\Support\ActivityCursor;
use JOOservices\LaravelRepository\Contracts\CrudRepositoryInterface;
use JOOservices\LaravelRepository\Repositories\EloquentRepository;
use JOOservices\LaravelRepository\Support\Filter;
use JOOservices\LaravelRepository\Traits\HasCrud;
use JOOservices\LaravelRepository\Traits\HasFilter;
use JOOservices\LaravelRepository\Traits\HasIteration;
use JOOservices\LaravelRepository\Traits\HasOrder;
use JOOservices\LaravelRepository\Traits\HasRead;
use MongoDB\BSON\ObjectId;
use MongoDB\Laravel\Connection;

final class ActivityRepository extends EloquentRepository implements CrudRepositoryInterface
{
    use HasCrud;
    use HasFilter;
    use HasIteration;
    use HasOrder;
    use HasRead;

    public function __construct(Activity $model)
    {
        parent::__construct($model);
    }

    public function fresh(): self
    {
        $model = $this->getModel();

        return new self($model instanceof Activity ? $model : new Activity());
    }

    /**
     * @param  array<string, mixed>  $data
     */
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
        $perPage = min($filter->limit, (int) config('activities.max_limit', 100));
        $page = max(1, $filter->page);
        $repo = $this->applyFilter($filter);
        $query = $repo->getQuery()
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
        $limit = min($filter->limit, (int) config('activities.max_limit', 100));
        $repo = $this->applyFilter($filter);
        $query = $repo->getQuery();
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
     * @return array{matched: int, deleted: int}
     */
    public function pruneMatching(DateTimeInterface $cutoff, ActivityFilterDto $filter, bool $delete): array
    {
        $repo = $this->applyFilter($filter);
        $query = $repo->getQuery()->where('created_at', '<', CarbonImmutable::instance($cutoff));
        $matched = $query->count();

        if ($delete === false || $matched === 0) {
            return ['matched' => $matched, 'deleted' => 0];
        }

        $rawDeleted = $query->delete();
        $deleted = is_int($rawDeleted)
            ? $rawDeleted
            : (is_numeric($rawDeleted) ? (int) $rawDeleted : 0);

        return ['matched' => $matched, 'deleted' => max(0, $deleted)];
    }

    /**
     * @param  Closure(BaseCollection<int, Activity>, int): mixed  $callback
     */
    public function exportChunk(ActivityFilterDto $filter, int $chunkSize, Closure $callback): void
    {
        $chunkSize = max(1, $chunkSize);
        $repo = $this->applyFilter($filter);
        $query = $repo->getQuery()
            ->orderByDesc('created_at')
            ->orderByDesc('_id');

        $batch = new BaseCollection();
        $page = 0;

        foreach ($query->cursor() as $record) {
            if (! $record instanceof Activity) {
                continue;
            }

            $batch->push($record);

            if ($batch->count() >= $chunkSize) {
                $callback($batch, ++$page);
                $batch = new BaseCollection();
            }
        }

        if ($batch->count() > 0) {
            $callback($batch, ++$page);
        }
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
            [
                'keys' => ['activity' => 1, 'created_at' => -1],
                'options' => ['name' => 'activities_activity_created_at'],
            ],
            [
                'keys' => ['created_at' => -1],
                'options' => ['name' => 'activities_created_at'],
            ],
            [
                'keys' => ['tenant_id' => 1, 'created_at' => -1],
                'options' => ['name' => 'activities_tenant_created_at'],
            ],
            [
                'keys' => ['actor_type' => 1, 'actor_id' => 1, 'created_at' => -1],
                'options' => ['name' => 'activities_actor_created_at'],
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
        $this->fresh()->getModel()->newQuery()->delete();
    }

    private function applyFilter(ActivityFilterDto $filter): self
    {
        $repo = $this->fresh();
        $filters = [];

        $this->pushEquality($filters, 'subject_type', $filter->subjectType);
        $this->pushEquality($filters, 'subject_id', $filter->subjectId);
        $this->pushEquality($filters, 'actor_type', $filter->actorType);
        $this->pushEquality($filters, 'actor_id', $filter->actorId);
        $this->pushEquality($filters, 'correlation_id', $filter->correlationId);
        $this->pushEquality($filters, 'batch_id', $filter->batchId);
        $this->pushEquality($filters, 'tenant_id', $filter->tenantId);

        if ($filter->activityPrefix !== null && $filter->activityPrefix !== '') {
            $filters[] = new Filter('activity', $filter->activityPrefix, 'beginsWith');
        }

        if ($filter->from !== null && $filter->from !== '') {
            $filters[] = new Filter('created_at', Carbon::parse($filter->from), '>=');
        }

        if ($filter->to !== null && $filter->to !== '') {
            $filters[] = new Filter('created_at', Carbon::parse($filter->to), '<');
        }

        if ($filter->contextKey !== null && $filter->contextKey !== '' && $filter->contextValue !== null) {
            $contextField = $filter->contextKey === 'plugin_slug'
                ? 'plugin_slug'
                : 'context.' . $filter->contextKey;
            $filters[] = new Filter($contextField, $filter->contextValue);
        }

        if ($filters !== []) {
            $repo->filter($filters);
        }

        if ($filter->activities !== null && $filter->activities !== []) {
            $repo->getQuery()->whereIn('activity', $filter->activities);
        }

        return $repo;
    }

    /**
     * @param  list<Filter>  $filters
     */
    private function pushEquality(array &$filters, string $field, ?string $value): void
    {
        if ($value !== null && $value !== '') {
            $filters[] = new Filter($field, $value);
        }
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

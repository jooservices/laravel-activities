<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Models\Activity;
use Jooservices\LaravelRepository\Repositories\EloquentRepository;
use Jooservices\LaravelRepository\Traits\HasCrud;
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

    public function ensureIndexes(): string
    {
        $model = $this->activityModel();
        /** @var Connection $connection */
        $connection = $model->getConnection();
        $collection = $connection->getCollection($model->getTable());

        $collection->createIndex(
            ['subject_type' => 1, 'subject_id' => 1, 'created_at' => -1],
            ['name' => 'activities_subject_created_at'],
        );

        $collection->createIndex(
            ['plugin_slug' => 1, 'created_at' => -1],
            ['name' => 'activities_plugin_slug_created_at'],
        );

        return 'activities_subject_created_at, activities_plugin_slug_created_at';
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

        if ($filter->subjectType !== null && $filter->subjectType !== '') {
            $mongoQuery->where('subject_type', '=', $filter->subjectType);
        }

        if ($filter->subjectId !== null && $filter->subjectId !== '') {
            $mongoQuery->where('subject_id', '=', $filter->subjectId);
        }

        if ($filter->contextKey !== null && $filter->contextKey !== '' && $filter->contextValue !== null) {
            $contextField = $filter->contextKey === 'plugin_slug'
                ? 'plugin_slug'
                : 'context.'.$filter->contextKey;
            $mongoQuery->where($contextField, '=', $filter->contextValue);
        }

        if ($filter->activities !== null && $filter->activities !== []) {
            $mongoQuery->whereIn('activity', $filter->activities);
        }

        return $query;
    }

    private function activityModel(): Activity
    {
        $model = $this->getModel();
        assert($model instanceof Activity);

        return $model;
    }
}

<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Models;

use Illuminate\Support\Carbon;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property string|null $_id
 * @property string $subject_id
 * @property string $subject_type
 * @property string $activity
 * @property string|null $description
 * @property array<string, mixed>|null $data
 * @property string|null $actor_id
 * @property string|null $actor_type
 * @property array<string, mixed>|null $context
 * @property string|null $plugin_slug
 * @property Carbon $created_at
 */
final class Activity extends Model
{
    public const UPDATED_AT = null;

    protected $connection = 'mongodb';

    protected $collection = 'activities';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $connection = config('activities.connection');
        if (is_string($connection) && $connection !== '') {
            $this->connection = $connection;
        }

        $configuredCollection = config('activities.collection');
        if (is_string($configuredCollection) && $configuredCollection !== '') {
            $this->collection = $configuredCollection;
        }
    }

    protected $fillable = [
        'subject_id',
        'subject_type',
        'activity',
        'description',
        'data',
        'actor_id',
        'actor_type',
        'context',
        'plugin_slug',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function getCollectionName(): string
    {
        return $this->collection;
    }
}

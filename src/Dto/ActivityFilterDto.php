<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Dto;

use JOOservices\Dto\Attributes\MapFrom;
use JOOservices\Dto\Attributes\MapTo;
use JOOservices\Dto\Core\Dto;

final class ActivityFilterDto extends Dto
{
    /**
     * @param  list<string>|null  $activities
     */
    public function __construct(
        #[MapFrom('subject_type')]
        #[MapTo('subject_type')]
        public readonly ?string $subjectType = null,
        #[MapFrom('subject_id')]
        #[MapTo('subject_id')]
        public readonly ?string $subjectId = null,
        #[MapFrom('actor_type')]
        #[MapTo('actor_type')]
        public readonly ?string $actorType = null,
        #[MapFrom('actor_id')]
        #[MapTo('actor_id')]
        public readonly ?string $actorId = null,
        #[MapFrom('context_key')]
        #[MapTo('context_key')]
        public readonly ?string $contextKey = null,
        #[MapFrom('context_value')]
        #[MapTo('context_value')]
        public readonly ?string $contextValue = null,
        public readonly ?array $activities = null,
        #[MapFrom('activity_prefix')]
        #[MapTo('activity_prefix')]
        public readonly ?string $activityPrefix = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        #[MapFrom('tenant_id')]
        #[MapTo('tenant_id')]
        public readonly ?string $tenantId = null,
        public readonly string $pagination = 'cursor',
        public readonly int $limit = 50,
        public readonly int $page = 1,
        public readonly ?string $cursor = null,
        #[MapFrom('correlation_id')]
        #[MapTo('correlation_id')]
        public readonly ?string $correlationId = null,
        #[MapFrom('batch_id')]
        #[MapTo('batch_id')]
        public readonly ?string $batchId = null,
    ) {
    }
}

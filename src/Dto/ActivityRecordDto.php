<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Dto;

use JOOservices\Dto\Attributes\MapFrom;
use JOOservices\Dto\Attributes\MapTo;
use JOOservices\Dto\Attributes\Validation\Required;
use JOOservices\Dto\Core\Dto;

final class ActivityRecordDto extends Dto
{
    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        #[Required]
        #[MapFrom('subject_type')]
        #[MapTo('subject_type')]
        public readonly string $subjectType,
        #[Required]
        #[MapFrom('subject_id')]
        #[MapTo('subject_id')]
        public readonly string $subjectId,
        #[Required]
        public readonly string $activity,
        public readonly ?string $description = null,
        public readonly ?array $data = null,
        #[MapFrom('actor_type')]
        #[MapTo('actor_type')]
        public readonly ?string $actorType = null,
        #[MapFrom('actor_id')]
        #[MapTo('actor_id')]
        public readonly ?string $actorId = null,
        public readonly ?array $context = null,
        #[MapFrom('correlation_id')]
        #[MapTo('correlation_id')]
        public readonly ?string $correlationId = null,
        #[MapFrom('batch_id')]
        #[MapTo('batch_id')]
        public readonly ?string $batchId = null,
        #[MapFrom('tenant_id')]
        #[MapTo('tenant_id')]
        public readonly ?string $tenantId = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Dto;

use DateTimeInterface;
use JOOservices\Dto\Attributes\MapFrom;
use JOOservices\Dto\Attributes\MapTo;
use JOOservices\Dto\Attributes\Validation\Required;
use JOOservices\Dto\Core\Dto;

final class ActivityDto extends Dto
{
    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>|null  $context
     */
    public function __construct(
        #[Required]
        public readonly string $id,
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
        public readonly ?string $description,
        public readonly ?array $data,
        #[MapFrom('actor_type')]
        #[MapTo('actor_type')]
        public readonly ?string $actorType,
        #[MapFrom('actor_id')]
        #[MapTo('actor_id')]
        public readonly ?string $actorId,
        public readonly ?array $context,
        #[Required]
        #[MapFrom('created_at')]
        #[MapTo('created_at')]
        public readonly DateTimeInterface | string $createdAt,
        #[MapFrom('correlation_id')]
        #[MapTo('correlation_id')]
        public readonly ?string $correlationId = null,
        #[MapFrom('batch_id')]
        #[MapTo('batch_id')]
        public readonly ?string $batchId = null,
        #[MapFrom('tenant_id')]
        #[MapTo('tenant_id')]
        public readonly ?string $tenantId = null,
        #[MapFrom('plugin_slug')]
        #[MapTo('plugin_slug')]
        public readonly ?string $pluginSlug = null,
    ) {
    }
}

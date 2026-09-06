<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Services;

use Carbon\CarbonImmutable;
use JOOservices\LaravelActivities\Contracts\ActivitySanitizerInterface;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;

final class ActivityPayloadPreparer
{
    public function __construct(
        private readonly ActivitySanitizerInterface $sanitizer,
        private readonly ActivityPayloadLimiter $limiter,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function prepare(ActivityRecordDto $record): array
    {
        $context = $this->limiter->limitBag($this->sanitizer->sanitize($record->context));
        $data = $this->limiter->limitBag($this->sanitizer->sanitize($record->data));

        $persist = [
            'subject_type' => $record->subjectType,
            'subject_id' => $record->subjectId,
            'activity' => $record->activity,
            'description' => $record->description,
            'data' => $data,
            'actor_type' => $record->actorType,
            'actor_id' => $record->actorId,
            'context' => $context,
            'plugin_slug' => is_array($context) && is_string($context['plugin_slug'] ?? null)
                ? $context['plugin_slug']
                : null,
            'correlation_id' => $record->correlationId,
            'batch_id' => $record->batchId,
            'tenant_id' => $record->tenantId,
            'created_at' => CarbonImmutable::now('UTC'),
        ];

        return $this->limiter->limitDocument($persist);
    }
}

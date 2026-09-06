<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Services;

final class ActivityPayloadLimiter
{
    /**
     * @var list<string>
     */
    private const IDENTITY_KEYS = [
        'subject_type',
        'subject_id',
        'activity',
        'description',
        'actor_type',
        'actor_id',
        'plugin_slug',
        'correlation_id',
        'batch_id',
        'tenant_id',
        'created_at',
    ];

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * @param  array<array-key, mixed>|null  $payload
     * @return array<array-key, mixed>|null
     */
    public function limitBag(?array $payload): ?array
    {
        if ($payload === null || $this->enabled() === false) {
            return $payload;
        }

        $limited = $this->limitValue($payload, 0);

        return is_array($limited) ? $limited : ['__truncated' => $this->marker()];
    }

    /**
     * @param  array<string, mixed>  $persist
     * @return array<string, mixed>
     */
    public function limitDocument(array $persist): array
    {
        if ($this->enabled() === false) {
            return $persist;
        }

        $encoded = json_encode($persist);
        if ($encoded === false) {
            return $this->identityOnly($persist);
        }

        if (strlen($encoded) <= $this->maxDocumentBytes()) {
            return $persist;
        }

        foreach (['data', 'context'] as $field) {
            if (array_key_exists($field, $persist)) {
                $persist[$field] = ['__truncated' => $this->marker()];
            }

            $encoded = json_encode($persist);
            if ($encoded === false) {
                return $this->identityOnly($persist);
            }

            if (strlen($encoded) <= $this->maxDocumentBytes()) {
                return $persist;
            }
        }

        return $this->identityOnly($persist);
    }

    private function limitValue(mixed $value, int $depth): mixed
    {
        if (is_string($value)) {
            return $this->limitString($value);
        }

        if (! is_array($value)) {
            return $value;
        }

        if ($depth >= $this->maxDepth()) {
            return $this->marker();
        }

        $limited = [];
        $count = 0;

        foreach ($value as $key => $item) {
            if ($count >= $this->maxArrayItems()) {
                $limited['__truncated_items'] = $this->marker();

                break;
            }

            $limited[$key] = $this->limitValue($item, $depth + 1);
            $count++;
        }

        return $limited;
    }

    private function limitString(string $value): string
    {
        $maxLength = $this->maxStringLength();

        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        if (in_array($value, ['[redacted]', '[REDACTED]'], true)) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength) . $this->marker();
    }

    /**
     * @param  array<string, mixed>  $persist
     * @return array<string, mixed>
     */
    private function identityOnly(array $persist): array
    {
        $kept = [];

        foreach (self::IDENTITY_KEYS as $key) {
            if (array_key_exists($key, $persist)) {
                $kept[$key] = $persist[$key];
            }
        }

        $kept['data'] = ['__truncated' => $this->marker()];
        $kept['context'] = ['__truncated' => $this->marker()];

        return $kept;
    }

    private function enabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? true);
    }

    private function maxStringLength(): int
    {
        return max(1, (int) ($this->config['max_string_length'] ?? 5000));
    }

    private function maxArrayItems(): int
    {
        return max(1, (int) ($this->config['max_array_items'] ?? 200));
    }

    private function maxDepth(): int
    {
        return max(1, (int) ($this->config['max_depth'] ?? 8));
    }

    private function maxDocumentBytes(): int
    {
        return max(1, (int) ($this->config['max_document_bytes'] ?? 262144));
    }

    private function marker(): string
    {
        return (string) ($this->config['truncate_marker'] ?? '[truncated]');
    }
}

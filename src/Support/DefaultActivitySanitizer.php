<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Support;

use JOOservices\LaravelActivities\Contracts\ActivitySanitizerInterface;

final class DefaultActivitySanitizer implements ActivitySanitizerInterface
{
    public function sanitize(?array $payload): ?array
    {
        if ($payload === null || (bool) config('activities.sanitization.enabled', true) === false) {
            return $payload;
        }

        /** @var array<string, mixed> $sanitized */
        $sanitized = $payload;
        $replacement = (string) config('activities.sanitization.redacted_value', '[redacted]');
        $caseSensitive = (bool) config('activities.sanitization.case_sensitive', false);
        /** @var list<string> $keys */
        $keys = array_values((array) config('activities.sanitization.sensitive_keys', []));

        return $this->sanitizeArray($sanitized, $keys, $replacement, $caseSensitive);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $payload, array $keys, string $replacement, bool $caseSensitive): array
    {
        foreach ($payload as $key => $value) {
            if ($this->isSensitiveKey($key, $keys, $caseSensitive)) {
                $payload[$key] = $replacement;

                continue;
            }

            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $payload[$key] = $this->sanitizeArray($value, $keys, $replacement, $caseSensitive);
            }
        }

        return $payload;
    }

    /**
     * @param  list<string>  $keys
     */
    private function isSensitiveKey(string $key, array $keys, bool $caseSensitive): bool
    {
        foreach ($keys as $sensitive) {
            if ($caseSensitive ? $key === $sensitive : strtolower($key) === strtolower($sensitive)) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use JOOservices\LaravelActivities\Support\DefaultActivitySanitizer;
use JsonSerializable;

final class DefaultActivitySanitizerTest extends UnitTestCase
{
    public function test_it_redacts_exact_and_compact_keys(): void
    {
        $sanitizer = $this->sanitizer();
        $token = $this->faker()->sha256();

        $result = $sanitizer->sanitize([
            'password' => $this->faker()->password(),
            'accessToken' => $token,
            'user_token' => $token,
            'url' => $this->faker()->url(),
        ]);

        $this->assertSame('[redacted]', $result['password']);
        $this->assertSame('[redacted]', $result['accessToken']);
        $this->assertSame('[redacted]', $result['user_token']);
        $this->assertNotSame('[redacted]', $result['url']);
    }

    public function test_it_redacts_bearer_and_jwt_values(): void
    {
        $sanitizer = $this->sanitizer();
        $payload = $sanitizer->sanitize([
            'header' => 'Bearer abcdefghijklmnop',
            'nested' => ['note' => 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxIn0.abc'],
        ]);

        $this->assertSame('[redacted]', $payload['header']);
        $this->assertSame('[redacted]', $payload['nested']['note']);
    }

    public function test_it_returns_null_payloads_unchanged(): void
    {
        $this->assertNull($this->sanitizer()->sanitize(null));
    }

    public function test_case_sensitive_keys(): void
    {
        $sanitizer = new DefaultActivitySanitizer(
            keys: ['token'],
            replacement: '[redacted]',
            caseSensitive: true,
        );

        $result = $sanitizer->sanitize([
            'token' => 'a',
            'other' => 'b',
        ]);

        $this->assertSame('[redacted]', $result['token']);
        $this->assertSame('b', $result['other']);
    }

    public function test_disabled_sanitizer_returns_payload(): void
    {
        $sanitizer = new DefaultActivitySanitizer(
            keys: ['password'],
            replacement: '[redacted]',
            enabled: false,
        );
        $payload = ['password' => $this->faker()->password()];

        $this->assertSame($payload, $sanitizer->sanitize($payload));
    }

    public function test_it_walks_json_serializable_objects(): void
    {
        $payload = $this->sanitizer()->sanitize([
            'wrap' => new class implements JsonSerializable {
                /**
                 * @return array<string, string>
                 */
                public function jsonSerialize(): array
                {
                    return ['password' => 'secret'];
                }
            },
        ]);

        $this->assertSame('[redacted]', $payload['wrap']['password']);
    }

    private function sanitizer(): DefaultActivitySanitizer
    {
        return new DefaultActivitySanitizer(
            keys: ['password', 'token', 'access_token'],
            replacement: '[redacted]',
            valuePatterns: [
                '/(?i)^Bearer\s+[A-Za-z0-9\-._~+\/=]+$/',
                '/(?i)^eyJ[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/',
            ],
        );
    }
}

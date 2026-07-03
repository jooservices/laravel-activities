<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use JOOservices\LaravelActivities\Support\DefaultActivitySanitizer;
use JOOservices\LaravelActivities\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DefaultActivitySanitizer::class)]
final class ActivitySanitizerTest extends TestCase
{
    public function test_sanitizer_redacts_sensitive_nested_keys(): void
    {
        $this->app['config']->set('activities.sanitization.sensitive_keys', ['token']);

        $sanitizer = new DefaultActivitySanitizer();
        $payload = $sanitizer->sanitize([
            'token' => 'secret',
            'nested' => ['token' => 'nested-secret', 'name' => 'ok'],
        ]);

        $this->assertSame('[redacted]', $payload['token']);
        $this->assertSame('[redacted]', $payload['nested']['token']);
        $this->assertSame('ok', $payload['nested']['name']);
    }
}

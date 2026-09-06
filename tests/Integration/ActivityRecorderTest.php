<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Integration;

use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Support\SubjectReference;
use JOOservices\LaravelActivities\Tests\TestCase;
use JOOservices\LaravelActivities\Tests\TestSubject;

final class ActivityRecorderTest extends TestCase
{
    public function test_record_persists_append_only_activity(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $url = $this->faker()->url();
        $slug = $this->faker()->slug(1);
        $description = $this->faker()->sentence();

        $dto = $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: (string) $this->faker()->randomNumber(3, true),
            activity: 'crawl_target.created',
            description: $description,
            data: ['url' => $url],
            actorType: TestSubject::class,
            actorId: (string) $this->faker()->randomNumber(2, true),
            context: ['plugin_slug' => $slug],
            tenantId: (string) $this->faker()->randomNumber(3, true),
        ));

        $this->assertSame('crawl_target.created', $dto->activity);
        $this->assertSame($description, $dto->description);
        $this->assertSame(['url' => $url], $dto->data);
        $this->assertSame($slug, $dto->pluginSlug);
        $this->assertNotSame('', $dto->id);
    }

    public function test_record_for_accepts_correlation_and_tenant(): void
    {
        $correlation = $this->faker()->uuid();
        $tenant = (string) $this->faker()->randomNumber(4, true);
        $subject = new TestSubject($this->faker()->numberBetween(1, 99));
        $actor = new TestSubject($this->faker()->numberBetween(1, 99));

        $dto = $this->app->make(ActivityRecorderInterface::class)->recordFor(
            subject: $subject,
            activity: 'crawl.dispatched',
            actor: $actor,
            description: $this->faker()->sentence(),
            correlationId: $correlation,
            tenantId: $tenant,
        );

        $this->assertSame($correlation, $dto->correlationId);
        $this->assertSame($tenant, $dto->tenantId);
        $this->assertSame((string) $subject->getKey(), $dto->subjectId);
    }

    public function test_from_external_records_without_eloquent(): void
    {
        $type = 'stripe.customer';
        $id = $this->faker()->bothify('cus_########');
        $ref = SubjectReference::fromExternal($type, $id);

        $dto = $this->app->make(ActivityRecorderInterface::class)->record(new ActivityRecordDto(
            subjectType: $ref->type,
            subjectId: $ref->id,
            activity: 'billing.updated',
        ));

        $this->assertSame($type, $dto->subjectType);
        $this->assertSame($id, $dto->subjectId);
    }

    public function test_sanitizer_redacts_nested_tokens_before_persist(): void
    {
        $recorder = $this->app->make(ActivityRecorderInterface::class);
        $query = $this->app->make(ActivityQueryInterface::class);
        $subjectId = (string) $this->faker()->randomNumber(3, true);

        $recorder->record(new ActivityRecordDto(
            subjectType: TestSubject::class,
            subjectId: $subjectId,
            activity: 'user.updated',
            data: ['accessToken' => $this->faker()->sha256()],
        ));

        $list = $query->list(new ActivityFilterDto(subjectId: $subjectId));

        $this->assertSame('[redacted]', $list->items[0]->data['accessToken'] ?? null);
    }

    public function test_model_exposes_collection_name(): void
    {
        $model = new \JOOservices\LaravelActivities\Models\Activity();

        $this->assertSame('activities_testing', $model->getCollectionName());
    }
}

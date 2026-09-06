<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use JOOservices\LaravelActivities\ActivityManager;
use JOOservices\LaravelActivities\Contracts\ActivityQueryInterface;
use JOOservices\LaravelActivities\Contracts\ActivityRecorderInterface;
use JOOservices\LaravelActivities\Dto\ActivityDto;
use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityListDto;
use JOOservices\LaravelActivities\Dto\ActivityRecordDto;
use JOOservices\LaravelActivities\Tests\TestSubject;
use RuntimeException;

final class ActivityManagerDelegationTest extends UnitTestCase
{
    public function test_for_actor_delegates_to_query(): void
    {
        $expected = new ActivityListDto(items: [], perPage: 1);
        $query = new class ($expected) implements ActivityQueryInterface {
            public bool $forActorCalled = false;

            public function __construct(private readonly ActivityListDto $expected)
            {
            }

            public function list(ActivityFilterDto $filter): ActivityListDto
            {
                throw new RuntimeException('list should not be used for forActor');
            }

            public function forSubject(object $subject, ?ActivityFilterDto $filter = null): ActivityListDto
            {
                throw new RuntimeException('forSubject should not be used for forActor');
            }

            public function forActor(object $actor, ?ActivityFilterDto $filter = null): ActivityListDto
            {
                $this->forActorCalled = true;

                return $this->expected;
            }
        };
        $recorder = new class implements ActivityRecorderInterface {
            public function record(ActivityRecordDto $record): ActivityDto
            {
                throw new RuntimeException('not used');
            }

            public function recordFor(
                object $subject,
                string $activity,
                ?object $actor = null,
                ?string $description = null,
                ?array $data = null,
                ?array $context = null,
                ?string $correlationId = null,
                ?string $batchId = null,
                ?string $tenantId = null,
            ): ActivityDto {
                throw new RuntimeException('not used');
            }
        };

        $manager = new ActivityManager($recorder, $query);

        $this->assertSame($expected, $manager->forActor(new TestSubject($this->faker()->numberBetween(1, 99))));
        $this->assertTrue($query->forActorCalled);
    }
}

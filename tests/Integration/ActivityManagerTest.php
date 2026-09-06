<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Integration;

use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Facades\Activity;
use JOOservices\LaravelActivities\Tests\TestCase;
use JOOservices\LaravelActivities\Tests\TestSubject;

final class ActivityManagerTest extends TestCase
{
    public function test_facade_records_and_queries(): void
    {
        $subject = new TestSubject($this->faker()->numberBetween(1, 99));
        $actor = new TestSubject($this->faker()->numberBetween(1, 99));
        $activity = $this->faker()->slug(2);

        Activity::recordFor(
            $subject,
            $activity,
            $actor,
            $this->faker()->sentence(),
            ['url' => $this->faker()->url()],
            ['plugin_slug' => $this->faker()->slug(1)],
            $this->faker()->uuid(),
            $this->faker()->uuid(),
            (string) $this->faker()->randomNumber(4, true),
        );

        $forSubject = Activity::forSubject($subject);
        $forActor = Activity::forActor($actor);
        $list = Activity::list(new ActivityFilterDto(
            subjectType: TestSubject::class,
            subjectId: (string) $subject->getKey(),
        ));

        $this->assertSame($activity, $forSubject->items[0]->activity);
        $this->assertSame($activity, $forActor->items[0]->activity);
        $this->assertCount(1, $list->items);
    }
}

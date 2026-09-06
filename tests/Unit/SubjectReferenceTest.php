<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Tests\Unit;

use JOOservices\LaravelActivities\Exceptions\InvalidActivitySubjectException;
use JOOservices\LaravelActivities\Support\SubjectReference;
use JOOservices\LaravelActivities\Tests\TestSubject;
use stdClass;

final class SubjectReferenceTest extends UnitTestCase
{
    public function test_from_object_and_from_external(): void
    {
        $id = $this->faker()->numberBetween(1, 999);
        $fromObject = SubjectReference::fromObject(new TestSubject($id));
        $type = $this->faker()->slug(1);
        $externalId = (string) $this->faker()->randomNumber(5, true);
        $fromExternal = SubjectReference::fromExternal($type, $externalId);

        $this->assertSame(TestSubject::class, $fromObject->type);
        $this->assertSame((string) $id, $fromObject->id);
        $this->assertSame($type, $fromExternal->type);
        $this->assertSame($externalId, $fromExternal->id);
    }

    public function test_from_object_requires_get_key(): void
    {
        $this->expectException(InvalidActivitySubjectException::class);

        SubjectReference::fromObject(new stdClass());
    }

    public function test_from_object_rejects_empty_key(): void
    {
        $subject = new class {
            public function getKey(): string
            {
                return '';
            }
        };

        $this->expectException(InvalidActivitySubjectException::class);

        SubjectReference::fromObject($subject);
    }

    public function test_from_external_rejects_empty_identity(): void
    {
        $this->expectException(InvalidActivitySubjectException::class);

        SubjectReference::fromExternal('', '1');
    }
}

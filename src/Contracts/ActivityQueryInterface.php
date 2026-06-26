<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Contracts;

use JOOservices\LaravelActivities\Dto\ActivityFilterDto;
use JOOservices\LaravelActivities\Dto\ActivityListDto;

interface ActivityQueryInterface
{
    public function list(ActivityFilterDto $filter): ActivityListDto;

    public function forSubject(object $subject, ?ActivityFilterDto $filter = null): ActivityListDto;
}

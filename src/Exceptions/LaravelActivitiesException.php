<?php

declare(strict_types=1);

namespace JOOservices\LaravelActivities\Exceptions;

use JOOservices\Exceptions\Base\AbstractContextAwareLogicException;

/**
 * Root marker for programmer / config failures in this package.
 */
abstract class LaravelActivitiesException extends AbstractContextAwareLogicException
{
}

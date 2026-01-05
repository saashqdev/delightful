<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\TaskScheduler\Exception;

use Throwable;

class TaskSchedulerBusinessException extends TaskSchedulerException
{
    public function __construct(?string $message = null, int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

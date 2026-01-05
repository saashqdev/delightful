<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\TaskScheduler\Exception;

use Throwable;

class TaskSchedulerParamsSchedulerException extends TaskSchedulerException
{
    public function __construct(?string $message = null, int $code = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

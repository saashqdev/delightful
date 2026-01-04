<?php

declare(strict_types=1);
/**
 * Copyright (c) The Magic , Distributed under the software license
 */

namespace Dtyq\AsyncEvent\Kernel\Constants;

class Status
{
    /**
     * Pending execution.
     */
    public const STATE_WAIT = 0;

    /**
     * Executing.
     */
    public const STATE_IN_EXECUTION = 1;

    /**
     * Execution successful
     */
    public const STATE_COMPLETE = 2;

    /**
     * Exceeded retry limit.
     */
    public const STATE_EXCEEDED = 3;
}

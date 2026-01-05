<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\AsyncEvent\Kernel\Constants;

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

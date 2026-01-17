<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Task Event Enum.
 */
enum TaskEvent: string
{
    /**
     * Task Suspended.
     */
    case SUSPENDED = 'suspended';

    /**
     * Task Terminated.
     */
    case TERMINATED = 'terminated';

    /**
     * Get event description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::SUSPENDED => 'Task Suspended',
            self::TERMINATED => 'Task Terminated',
        };
    }

    /**
     * Is suspended state
     */
    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }

    /**
     * Is terminated state
     */
    public function isTerminated(): bool
    {
        return $this === self::TERMINATED;
    }
}

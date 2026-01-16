<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use App\Infrastructure\Util\IdGenerator\IdGenerator;

abstract class AbstractEvent
{
    private readonly int $eventId;

    public function __construct()
    {
        // Generate unique snowflake ID for each event instance
        $this->eventId = IdGenerator::getSnowId();
    }

    /**
     * Get the unique event ID.
     */
    public function getEventId(): int
    {
        return $this->eventId;
    }
}

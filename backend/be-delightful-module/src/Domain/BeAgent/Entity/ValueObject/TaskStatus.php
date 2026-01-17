<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Task status value object.
 */
enum TaskStatus: string
{
    /**
     * Waiting.
     */
    case WAITING = 'waiting';

    /**
     * Running.
     */
    case RUNNING = 'running';

    /**
     * Finished.
     */
    case FINISHED = 'finished';

    /**
     * Suspended.
     */
    case Suspended = 'suspended';

    /**
     * Stopped.
     */
    case Stopped = 'stopped';

    /**
     * Error.
     */
    case ERROR = 'error';

    /**
     * Get status description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::WAITING => 'Waiting',
            self::RUNNING => 'Running',
            self::FINISHED => 'Finished',
            self::ERROR => 'Error',
            self::Suspended => 'Suspended',
            self::Stopped => 'Stopped',
        };
    }

    /**
     * Get all status list.
     *
     * @return array<string, string> Mapping of status values to descriptions
     */
    public static function getList(): array
    {
        return [
            self::WAITING->value => self::WAITING->getDescription(),
            self::RUNNING->value => self::RUNNING->getDescription(),
            self::FINISHED->value => self::FINISHED->getDescription(),
            self::ERROR->value => self::ERROR->getDescription(),
            self::Suspended->value => self::Suspended->getDescription(),
            self::Stopped->value => self::Stopped->getDescription(),
        ];
    }

    /**
     * Is final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::FINISHED, self::ERROR, self::Stopped, self::Suspended], true);
    }

    /**
     * Is active state.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::WAITING, self::RUNNING], true);
    }
}

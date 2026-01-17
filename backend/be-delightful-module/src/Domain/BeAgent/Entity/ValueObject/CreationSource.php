<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Resource creation source enumeration.
 */
enum CreationSource: int
{
    /**
     * User created.
     */
    case USER_CREATED = 1;

    /**
     * Scheduled task created.
     */
    case SCHEDULED_TASK = 2;

    /**
     * Copy created.
     */
    case COPY = 3;

    /**
     * Get source name.
     */
    public function getName(): string
    {
        return match ($this) {
            self::USER_CREATED => 'User Created',
            self::SCHEDULED_TASK => 'Scheduled Task',
            self::COPY => 'Copy',
        };
    }

    /**
     * Create enum instance from string or integer value.
     */
    public static function fromValue(int|string $value): self
    {
        if (is_string($value)) {
            $value = (int) $value;
        }

        return match ($value) {
            1 => self::USER_CREATED,
            2 => self::SCHEDULED_TASK,
            3 => self::COPY,
            default => self::USER_CREATED, // Default to user created
        };
    }
}

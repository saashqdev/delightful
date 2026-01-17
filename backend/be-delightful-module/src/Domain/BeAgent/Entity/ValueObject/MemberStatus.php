<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Member status value object
 *
 * Encapsulates business logic and validation rules for member status
 */
enum MemberStatus: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;

    /**
     * Whether is active status
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Whether is inactive status
     */
    public function isInactive(): bool
    {
        return $this === self::INACTIVE;
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
        };
    }
}

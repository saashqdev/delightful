<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Project status enum.
 */
enum ProjectStatus: int
{
    /**
     * Active status
     */
    case ACTIVE = 1;

    /**
     * Archived.
     */
    case ARCHIVED = 2;

    /**
     * Deleted.
     */
    case DELETED = 3;

    /**
     * Get status description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::ARCHIVED => 'Archived',
            self::DELETED => 'Deleted',
        };
    }

    /**
     * Whether is active status
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Whether is archived.
     */
    public function isArchived(): bool
    {
        return $this === self::ARCHIVED;
    }

    /**
     * Whether is deleted.
     */
    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }
}

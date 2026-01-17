<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Workspace status value object.
 */
enum WorkspaceStatus: int
{
    /**
     * Normal status.
     */
    case Normal = 0;

    /**
     * Disabled status.
     */
    case Disabled = 1;

    /**
     * Deleted status.
     */
    case Deleted = 2;

    /**
     * Get status description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Disabled => 'Disabled',
            self::Deleted => 'Deleted',
        };
    }

    /**
     * Get all status list.
     *
     * @return array<int, string> Mapping of status values to descriptions
     */
    public static function getList(): array
    {
        return [
            self::Normal->value => self::Normal->getDescription(),
            self::Disabled->value => self::Disabled->getDescription(),
            self::Deleted->value => self::Deleted->getDescription(),
        ];
    }
}

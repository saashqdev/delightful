<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Workspace archive status value object.
 */
enum WorkspaceArchiveStatus: int
{
    /**
     * Not archived.
     */
    case NotArchived = 0;

    /**
     * Archived.
     */
    case Archived = 1;

    /**
     * Get archive status description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::NotArchived => 'Not Archived',
            self::Archived => 'Archived',
        };
    }

    /**
     * Get all archive status list.
     *
     * @return array<int, string> Mapping of status values to descriptions
     */
    public static function getList(): array
    {
        return [
            self::NotArchived->value => self::NotArchived->getDescription(),
            self::Archived->value => self::Archived->getDescription(),
        ];
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Delete data type enum
 * Used to identify the type of deleted data for querying related running tasks
 */
enum DeleteDataType: string
{
    /**
     * Workspace.
     */
    case WORKSPACE = 'workspace';

    /**
     * Project.
     */
    case PROJECT = 'project';

    /**
     * Topic.
     */
    case TOPIC = 'topic';

    /**
     * Get type description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::WORKSPACE => 'Workspace',
            self::PROJECT => 'Project',
            self::TOPIC => 'Topic',
        };
    }

    /**
     * Get all type list.
     *
     * @return array<string, string> Mapping of type values to descriptions
     */
    public static function getList(): array
    {
        return [
            self::WORKSPACE->value => self::WORKSPACE->getDescription(),
            self::PROJECT->value => self::PROJECT->getDescription(),
            self::TOPIC->value => self::TOPIC->getDescription(),
        ];
    }
}

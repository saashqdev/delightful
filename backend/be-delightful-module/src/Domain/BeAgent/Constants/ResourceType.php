<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Constants;

/**
 * Resource type constants.
 */
final class ResourceType
{
    public const PROJECT = 'project';

    public const TOPIC = 'topic';

    public const FILE = 'file';

    public const DIRECTORY = 'directory';

    public const MEMBER = 'member';

    /**
     * Get all resource types.
     */
    public static function getAllTypes(): array
    {
        return [
            self::PROJECT,
            self::TOPIC,
            self::FILE,
            self::DIRECTORY,
            self::MEMBER,
        ];
    }

    /**
     * Validate if resource type is valid.
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::getAllTypes(), true);
    }
}

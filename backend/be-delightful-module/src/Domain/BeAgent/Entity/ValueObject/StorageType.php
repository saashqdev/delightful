<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Storage type enum.
 */
enum StorageType: string
{
    /**
     * Workspace storage.
     */
    case WORKSPACE = 'workspace';

    /**
     * Message storage.
     */
    case TOPIC = 'topic';

    /**
     * Snapshot storage.
     */
    case SNAPSHOT = 'snapshot';

    case OBJECT_STORAGE = 'object_storage';
    case OTHERS = '';

    /**
     * Get storage type name.
     */
    public function getName(): string
    {
        return match ($this) {
            self::WORKSPACE => 'Workspace',
            self::TOPIC => 'Topic',
            self::SNAPSHOT => 'Snapshot',
            self::OBJECT_STORAGE => 'Object storage',
            self::OTHERS => 'Others',
        };
    }

    /**
     * Get storage type description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::WORKSPACE => 'Files stored in workspace',
            self::TOPIC => 'Files stored in messages',
            self::SNAPSHOT => 'Files stored in snapshots',
            self::OBJECT_STORAGE => 'Files stored in object storage',
            self::OTHERS => 'Other storage methods',
        };
    }

    /**
     * Create enum instance from string.
     */
    public static function fromValue(string $value): self
    {
        return match ($value) {
            'workspace' => self::WORKSPACE,
            'topic' => self::TOPIC,
            'snapshot' => self::SNAPSHOT,
            // Fallback: unknown values are converted to OTHERS (handle dirty data)
            default => self::OTHERS,
        };
    }
}

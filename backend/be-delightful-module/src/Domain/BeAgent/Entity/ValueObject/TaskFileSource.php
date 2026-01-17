<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Task file source enumeration.
 */
enum TaskFileSource: int
{
    case DEFAULT = 0;

    // Home page.
    case HOME = 1;

    /**
     * Project directory.
     */
    case PROJECT_DIRECTORY = 2;

    /**
     * Agent.
     */
    case AGENT = 3;

    case COPY = 4;

    /**
     * Move.
     */
    case MOVE = 6;

    /**
     * Get source name.
     */
    public function getName(): string
    {
        return match ($this) {
            self::DEFAULT => 'Default',
            self::HOME => 'Home',
            self::PROJECT_DIRECTORY => 'Project Directory',
            self::AGENT => 'Agent',
            self::COPY => 'Copy',
            self::MOVE => 'Move',
        };
    }

    /**
     * Create enum instance from string or integer.
     */
    public static function fromValue(int|string $value): self
    {
        if (is_string($value)) {
            $value = (int) $value;
        }

        return match ($value) {
            1 => self::HOME,
            2 => self::PROJECT_DIRECTORY,
            3 => self::AGENT,
            4 => self::COPY,
            6 => self::MOVE,
            default => self::DEFAULT,
        };
    }
}

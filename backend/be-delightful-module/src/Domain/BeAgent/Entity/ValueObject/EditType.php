<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Edit type enum.
 */
enum EditType: int
{
    /**
     * Manual edit.
     */
    case MANUAL = 1;

    /**
     * AI edit.
     */
    case AI = 2;

    /**
     * Get edit type name.
     */
    public function getName(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual edit',
            self::AI => 'AI edit',
        };
    }

    /**
     * Get edit type description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MANUAL => 'Version manually edited by humans',
            self::AI => 'Version automatically edited by AI',
        };
    }
}

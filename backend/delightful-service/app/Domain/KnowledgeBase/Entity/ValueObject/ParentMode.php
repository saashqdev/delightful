<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

enum ParentMode: int
{
    case PARAGRAPH = 1;
    case AUTHORITY = 2;

    public function getDescription(): string
    {
        return match ($this) {
            self::PARAGRAPH => 'segmentfall',
            self::AUTHORITY => 'weight',
        };
    }
}

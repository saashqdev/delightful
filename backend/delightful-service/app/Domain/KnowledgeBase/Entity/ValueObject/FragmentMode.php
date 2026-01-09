<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

enum FragmentMode: int
{
    case NORMAL = 1;
    case PARENT_CHILD = 2;

    public function getDescription(): string
    {
        return match ($this) {
            self::NORMAL => '通用mode',
            self::PARENT_CHILD => '父子分段',
        };
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject;

enum MemoryType: int
{
    case None = 0;

    // biglanguagemodelrecord 暫o clocknotrecord nouse
    case LLM = 1;

    // Flow chatrecord
    case Chat = 2;

    // IM chatrecord
    case IMChat = 3;

    // mountmemory
    case Mount = 4;

    public function isNone(): bool
    {
        return $this == self::None;
    }
}

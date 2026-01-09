<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject;

enum MemoryType: int
{
    case None = 0;

    // 大语言模型记录 暂时不记录了 无用
    case LLM = 1;

    // Flow chat记录
    case Chat = 2;

    // IM 的chat记录
    case IMChat = 3;

    // 挂载记忆
    case Mount = 4;

    public function isNone(): bool
    {
        return $this == self::None;
    }
}

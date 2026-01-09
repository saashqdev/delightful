<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\LongTermMemory\Entity\ValueObject;

/**
 * 记忆type枚举.
 */
enum MemoryType: string
{
    case MANUAL_INPUT = 'manual_input';           // 手动input
    case CONVERSATION_ANALYSIS = 'conversation_analysis';  // conversation分析
    case USER_NOTE = 'user_note';                // user笔记
    case SYSTEM_KNOWLEDGE = 'system_knowledge';  // system知识

    /**
     * get中文description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MANUAL_INPUT => '手动input',
            self::CONVERSATION_ANALYSIS => 'conversation分析',
            self::USER_NOTE => 'user笔记',
            self::SYSTEM_KNOWLEDGE => 'system知识',
        };
    }

    /**
     * 是否为user生成的记忆.
     */
    public function isUserGenerated(): bool
    {
        return in_array($this, [
            self::MANUAL_INPUT,
            self::USER_NOTE,
        ]);
    }

    /**
     * 是否为system生成的记忆.
     */
    public function isSystemGenerated(): bool
    {
        return in_array($this, [
            self::CONVERSATION_ANALYSIS,
            self::SYSTEM_KNOWLEDGE,
        ]);
    }
}

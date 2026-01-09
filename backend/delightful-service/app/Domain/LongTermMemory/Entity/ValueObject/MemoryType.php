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
    case MANUAL_INPUT = 'manual_input';           // hand动input
    case CONVERSATION_ANALYSIS = 'conversation_analysis';  // conversationanalyze
    case USER_NOTE = 'user_note';                // user笔记
    case SYSTEM_KNOWLEDGE = 'system_knowledge';  // system知识

    /**
     * getmiddle文description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::MANUAL_INPUT => 'hand动input',
            self::CONVERSATION_ANALYSIS => 'conversationanalyze',
            self::USER_NOTE => 'user笔记',
            self::SYSTEM_KNOWLEDGE => 'system知识',
        };
    }

    /**
     * whetherforusergenerate记忆.
     */
    public function isUserGenerated(): bool
    {
        return in_array($this, [
            self::MANUAL_INPUT,
            self::USER_NOTE,
        ]);
    }

    /**
     * whetherforsystemgenerate记忆.
     */
    public function isSystemGenerated(): bool
    {
        return in_array($this, [
            self::CONVERSATION_ANALYSIS,
            self::SYSTEM_KNOWLEDGE,
        ]);
    }
}

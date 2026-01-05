<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\LongTermMemory\Enum;

enum MemoryType: string
{
    case MANUAL_INPUT = 'manual_input';
    case CONVERSATION_ANALYSIS = 'conversation_analysis';
    case USER_NOTE = 'user_note';
    case SYSTEM_KNOWLEDGE = 'system_knowledge';
}

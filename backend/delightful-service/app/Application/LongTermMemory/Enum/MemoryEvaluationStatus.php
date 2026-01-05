<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\LongTermMemory\Enum;

/**
 * Memory evaluation status enum.
 */
enum MemoryEvaluationStatus: string
{
    case NO_MEMORY_NEEDED = 'no_memory_needed';
    case CREATED = 'created';
    case NOT_CREATED_LOW_SCORE = 'not_created_low_score';
}

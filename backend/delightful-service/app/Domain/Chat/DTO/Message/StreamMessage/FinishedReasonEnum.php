<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\StreamMessage;

/**
 * endreason:
 * 0:processend
 * 1.hair生exception.
 */
enum FinishedReasonEnum: int
{
    case Finished = 0;
    case Exception = 1;
}

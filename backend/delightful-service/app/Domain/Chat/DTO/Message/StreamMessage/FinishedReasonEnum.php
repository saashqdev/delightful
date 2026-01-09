<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\StreamMessage;

/**
 * 结束原因：
 * 0:流程结束
 * 1.发生exception.
 */
enum FinishedReasonEnum: int
{
    case Finished = 0;
    case Exception = 1;
}

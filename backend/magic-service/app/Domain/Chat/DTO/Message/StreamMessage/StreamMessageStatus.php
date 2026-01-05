<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\StreamMessage;

enum StreamMessageStatus: int
{
    /**
     * 开始.
     */
    case Start = 0;

    /**
     * 进行中.
     */
    case Processing = 1;

    /**
     * 已完成.
     */
    case Completed = 2;
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\StreamMessage;

enum StreamMessageStatus: int
{
    /**
     * start.
     */
    case Start = 0;

    /**
     * conductmiddle.
     */
    case Processing = 1;

    /**
     * 已complete.
     */
    case Completed = 2;
}

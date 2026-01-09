<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\StreamMessage;

/**
 * messageapplicationoption:0:coverage 1:append(stringsplice,arrayinendtailinsert).
 */
enum MessageAppendOptions: int
{
    case Overwrite = 0;
    case Append = 1;
}

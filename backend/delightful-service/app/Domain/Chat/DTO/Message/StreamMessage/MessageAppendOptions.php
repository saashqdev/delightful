<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\StreamMessage;

/**
 * messageapplicationoption：0:覆盖 1：追加（string拼接，arrayin末尾插入）.
 */
enum MessageAppendOptions: int
{
    case Overwrite = 0;
    case Append = 1;
}

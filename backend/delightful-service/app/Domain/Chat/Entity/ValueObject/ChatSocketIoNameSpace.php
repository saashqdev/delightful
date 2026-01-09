<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * SocketIo命名null间.
 */
enum ChatSocketIoNameSpace: string
{
    case Im = '/im';
}

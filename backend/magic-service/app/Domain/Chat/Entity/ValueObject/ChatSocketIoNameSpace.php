<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * SocketIo命名空间.
 */
enum ChatSocketIoNameSpace: string
{
    case Im = '/im';
}

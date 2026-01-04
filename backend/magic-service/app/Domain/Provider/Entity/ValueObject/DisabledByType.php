<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

enum DisabledByType: string
{
    case OFFICIAL = 'OFFICIAL'; // 官方禁用
    case USER = 'USER'; // 用户禁用
}

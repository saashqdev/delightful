<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

enum DisabledByType: string
{
    case OFFICIAL = 'OFFICIAL'; // officialdisable
    case USER = 'USER'; // userdisable
}

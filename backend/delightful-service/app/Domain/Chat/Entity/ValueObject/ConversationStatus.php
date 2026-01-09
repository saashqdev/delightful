<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum ConversationStatus: int
{
    case Normal = 0;

    // hidden
    case Hidden = 1;

    // delete
    case Delete = 2;
}

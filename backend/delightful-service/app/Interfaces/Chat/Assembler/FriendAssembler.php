<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Assembler;

use App\Domain\Chat\Entity\DelightfulFriendEntity;

class FriendAssembler
{
    public static function getFriendEntity(array $friend): DelightfulFriendEntity
    {
        return new DelightfulFriendEntity($friend);
    }
}

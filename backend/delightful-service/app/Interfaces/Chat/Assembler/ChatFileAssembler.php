<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Assembler;

use App\Domain\Chat\Entity\DelightfulChatFileEntity;

class ChatFileAssembler
{
    public static function getChatFileEntity(array $chatFile): DelightfulChatFileEntity
    {
        return new DelightfulChatFileEntity($chatFile);
    }
}

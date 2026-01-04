<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Interfaces\Chat\Assembler;

use App\Domain\Chat\Entity\MagicChatFileEntity;

class ChatFileAssembler
{
    public static function getChatFileEntity(array $chatFile): MagicChatFileEntity
    {
        return new MagicChatFileEntity($chatFile);
    }
}

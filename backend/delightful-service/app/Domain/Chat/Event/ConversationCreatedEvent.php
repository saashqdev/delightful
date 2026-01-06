<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Event;

use App\Domain\Chat\Entity\DelightfulConversationEntity;

class ConversationCreatedEvent
{
    /**
     * @param DelightfulConversationEntity $conversation 创建的会话实体
     */
    public function __construct(private readonly DelightfulConversationEntity $conversation)
    {
    }

    /**
     * 获取创建的会话实体.
     */
    public function getConversation(): DelightfulConversationEntity
    {
        return $this->conversation;
    }
}

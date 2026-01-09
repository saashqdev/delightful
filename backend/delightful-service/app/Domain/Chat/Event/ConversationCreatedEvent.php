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
     * @param DelightfulConversationEntity $conversation createconversation实body
     */
    public function __construct(private readonly DelightfulConversationEntity $conversation)
    {
    }

    /**
     * getcreateconversation实body.
     */
    public function getConversation(): DelightfulConversationEntity
    {
        return $this->conversation;
    }
}

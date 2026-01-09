<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

enum KnowledgeType: int
{
    /*
     * user自建knowledge base
     */
    case UserKnowledgeBase = 1;

    /*
     * user话题
     */
    case UserTopic = 4;

    /*
     * userconversation
     */
    case UserConversation = 5;

    /**
     * @return array<int>
     */
    public static function getAll(): array
    {
        return [
            self::UserKnowledgeBase->value,
            self::UserTopic->value,
            self::UserConversation->value,
        ];
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Assembler;

use App\Domain\Chat\Entity\DelightfulTopicEntity;
use App\Domain\Chat\Entity\DelightfulTopicMessageEntity;

class TopicAssembler
{
    public static function getTopicEntity(array $topic): DelightfulTopicEntity
    {
        $topicEntity = new DelightfulTopicEntity();
        $topicEntity->setId($topic['id']);
        $topicEntity->setTopicId($topic['topic_id']);
        $topicEntity->setConversationId($topic['conversation_id']);
        $topicEntity->setName($topic['name']);
        $topicEntity->setDescription($topic['description']);
        $topicEntity->setOrganizationCode($topic['organization_code']);
        $topicEntity->setCreatedAt($topic['created_at']);
        $topicEntity->setUpdatedAt($topic['updated_at']);
        return $topicEntity;
    }

    /**
     * @return array<DelightfulTopicEntity>
     */
    public static function getTopicEntities(array $topics): array
    {
        $topicEntities = [];
        foreach ($topics as $topic) {
            $topicEntities[] = self::getTopicEntity($topic);
        }
        return $topicEntities;
    }

    public static function getTopicMessageEntity(array $topicMessage): DelightfulTopicMessageEntity
    {
        return new DelightfulTopicMessageEntity($topicMessage);
    }

    /**
     * @return array<DelightfulTopicMessageEntity>
     */
    public static function getTopicMessageEntities(array $topicMessages): array
    {
        $topicMessageEntities = [];
        foreach ($topicMessages as $topicMessage) {
            $topicMessageEntities[] = self::getTopicMessageEntity($topicMessage);
        }
        return $topicMessageEntities;
    }
}

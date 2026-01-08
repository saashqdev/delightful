<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Factory;

use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;

class DelightfulSeqFactory
{
    public static function arrayToEntity(array $seq): DelightfulSeqEntity
    {
        $entity = new DelightfulSeqEntity();
        $entity->setId($seq['id']);
        $entity->setOrganizationCode($seq['organization_code']);
        isset($seq['object_type']) && $entity->setObjectType(ConversationType::tryFrom($seq['object_type']));
        $entity->setObjectId($seq['object_id']);
        $entity->setSeqId($seq['seq_id']);
        $entity->setDelightfulMessageId($seq['delightful_message_id']);
        $entity->setMessageId($seq['message_id']);
        $entity->setReferMessageId($seq['refer_message_id']);
        $entity->setSenderMessageId($seq['sender_message_id']);
        $entity->setConversationId($seq['conversation_id']);
        isset($seq['status']) && $entity->setStatus(DelightfulMessageStatus::tryFrom($seq['status']));
        $entity->setCreatedAt($seq['created_at']);
        $entity->setUpdatedAt($seq['updated_at']);
        $entity->setAppMessageId($seq['app_message_id']);
        return $entity;
    }
}

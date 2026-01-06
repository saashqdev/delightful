<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Factory;

use App\Domain\Flow\Entity\DelightfulFlowWaitMessageEntity;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowWaitMessageModel;

class DelightfulFlowWaitMessageFactory
{
    public static function modelToEntity(DelightfulFlowWaitMessageModel $model): DelightfulFlowWaitMessageEntity
    {
        $entity = new DelightfulFlowWaitMessageEntity();
        $entity->setId($model->id);
        $entity->setOrganizationCode($model->organization_code);
        $entity->setConversationId($model->conversation_id);
        $entity->setOriginConversationId($model->origin_conversation_id);
        $entity->setMessageId($model->message_id);
        $entity->setWaitNodeId($model->wait_node_id);
        $entity->setFlowCode($model->flow_code);
        $entity->setFlowVersion($model->flow_version);
        $entity->setTimeout($model->timeout);
        $entity->setHandled($model->handled);
        $entity->setPersistentData($model->persistent_data ?? []);
        $entity->setCreator($model->created_uid);
        $entity->setCreatedAt($model->created_at);
        $entity->setModifier($model->updated_uid);
        $entity->setUpdatedAt($model->updated_at);

        return $entity;
    }
}

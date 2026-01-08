<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Factory;

use App\Domain\Agent\Entity\DelightfulBotThirdPlatformChatEntity;
use App\Domain\Agent\Entity\ValueObject\ThirdPlatformChat\ThirdPlatformChatType;
use App\Domain\Agent\Repository\Persistence\Model\DelightfulBotThirdPlatformChatModel;

class DelightfulAgentThirdPlatformChatFactory
{
    public static function modelToEntity(DelightfulBotThirdPlatformChatModel $model): DelightfulBotThirdPlatformChatEntity
    {
        $entity = new DelightfulBotThirdPlatformChatEntity();
        $entity->setId($model->id);
        $entity->setBotId($model->bot_id);
        $entity->setKey($model->key);
        $entity->setType(ThirdPlatformChatType::from($model->type));
        $entity->setEnabled($model->enabled);
        $entity->setOptions($model->options);
        $entity->setIdentification($model->identification);
        return $entity;
    }
}

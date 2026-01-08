<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Factory;

use App\Domain\Contact\Entity\DelightfulUserSettingEntity;
use App\Domain\Contact\Repository\Persistence\Model\UserSettingModel;

class DelightfulUserSettingFactory
{
    public static function createEntity(UserSettingModel $model): DelightfulUserSettingEntity
    {
        $entity = new DelightfulUserSettingEntity();
        $entity->setId($model->id);
        $entity->setDelightfulId($model->delightful_id);
        $entity->setOrganizationCode($model->organization_code);
        $entity->setUserId($model->user_id);
        $entity->setKey($model->key);
        $entity->setValue($model->value);
        $entity->setCreator($model->creator);
        $entity->setCreatedAt($model->created_at);
        $entity->setModifier($model->modifier);
        $entity->setUpdatedAt($model->updated_at);

        return $entity;
    }

    public static function createModel(DelightfulUserSettingEntity $entity): array
    {
        return [
            'id' => $entity->getId(),
            'delightful_id' => $entity->getDelightfulId(),
            'organization_code' => $entity->getOrganizationCode(),
            'user_id' => $entity->getUserId(),
            'key' => $entity->getKey(),
            'value' => $entity->getValue(),
            'creator' => $entity->getCreator(),
            'created_at' => $entity->getCreatedAt(),
            'modifier' => $entity->getModifier(),
            'updated_at' => $entity->getUpdatedAt(),
        ];
    }
}

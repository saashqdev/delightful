<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Mode\Factory;

use App\Domain\Mode\Entity\ModeGroupRelationEntity;
use App\Domain\Mode\Repository\Persistence\Model\ModeGroupRelationModel;

class ModeGroupRelationFactory
{
    /**
     * 将modelconvert为实body.
     */
    public static function modelToEntity(ModeGroupRelationModel $model): ModeGroupRelationEntity
    {
        $entity = new ModeGroupRelationEntity();

        $entity->setId((string) $model->id);
        $entity->setModeId($model->mode_id);
        $entity->setGroupId((string) $model->group_id);
        $entity->setModelId($model->model_id);
        $entity->setProviderModelId($model->provider_model_id);
        $entity->setSort($model->sort);
        $entity->setOrganizationCode($model->organization_code);

        if ($model->created_at) {
            $entity->setCreatedAt($model->created_at->toDateTimeString());
        }

        if ($model->updated_at) {
            $entity->setUpdatedAt($model->updated_at->toDateTimeString());
        }

        return $entity;
    }
}

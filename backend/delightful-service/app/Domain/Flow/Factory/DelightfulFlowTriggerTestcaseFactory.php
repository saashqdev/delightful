<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Factory;

use App\Domain\Flow\Entity\DelightfulFlowTriggerTestcaseEntity;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowTriggerTestcaseModel;

class DelightfulFlowTriggerTestcaseFactory
{
    public static function modelToEntity(DelightfulFlowTriggerTestcaseModel $model): DelightfulFlowTriggerTestcaseEntity
    {
        $entity = new DelightfulFlowTriggerTestcaseEntity();
        $entity->setId($model->id);
        $entity->setFlowCode($model->flow_code);
        $entity->setCode($model->code);
        $entity->setName($model->name);
        $entity->setDescription($model->description);
        $entity->setCaseConfig($model->case_config);
        $entity->setOrganizationCode($model->organization_code);
        $entity->setCreator($model->created_uid);
        $entity->setCreatedAt($model->created_at);
        $entity->setModifier($model->updated_uid);
        $entity->setUpdatedAt($model->updated_at);

        return $entity;
    }
}

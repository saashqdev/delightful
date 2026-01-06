<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Factory;

use App\Domain\Flow\Entity\DelightfulFlowVersionEntity;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowVersionModel;

class DelightfulFlowVersionFactory
{
    public static function modelToEntity(DelightfulFlowVersionModel $magicFlowVersionModel): DelightfulFlowVersionEntity
    {
        $magicFlowDraftEntity = new DelightfulFlowVersionEntity();
        $magicFlowDraftEntity->setId($magicFlowVersionModel->id);
        $magicFlowDraftEntity->setFlowCode($magicFlowVersionModel->flow_code);
        $magicFlowDraftEntity->setCode($magicFlowVersionModel->code);
        $magicFlowDraftEntity->setName($magicFlowVersionModel->name);
        $magicFlowDraftEntity->setDescription($magicFlowVersionModel->description);
        if (! empty($magicFlowVersionModel->magic_flow)) {
            $magicFlowDraftEntity->setDelightfulFlow(DelightfulFlowFactory::arrayToEntity($magicFlowVersionModel->magic_flow, 'v0'));
        }

        $magicFlowDraftEntity->setOrganizationCode($magicFlowVersionModel->organization_code);
        $magicFlowDraftEntity->setCreator($magicFlowVersionModel->created_uid);
        $magicFlowDraftEntity->setCreatedAt($magicFlowVersionModel->created_at);
        $magicFlowDraftEntity->setModifier($magicFlowVersionModel->updated_uid);
        $magicFlowDraftEntity->setUpdatedAt($magicFlowVersionModel->updated_at);

        return $magicFlowDraftEntity;
    }
}

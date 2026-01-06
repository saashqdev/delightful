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
    public static function modelToEntity(DelightfulFlowVersionModel $delightfulFlowVersionModel): DelightfulFlowVersionEntity
    {
        $delightfulFlowDraftEntity = new DelightfulFlowVersionEntity();
        $delightfulFlowDraftEntity->setId($delightfulFlowVersionModel->id);
        $delightfulFlowDraftEntity->setFlowCode($delightfulFlowVersionModel->flow_code);
        $delightfulFlowDraftEntity->setCode($delightfulFlowVersionModel->code);
        $delightfulFlowDraftEntity->setName($delightfulFlowVersionModel->name);
        $delightfulFlowDraftEntity->setDescription($delightfulFlowVersionModel->description);
        if (! empty($delightfulFlowVersionModel->delightful_flow)) {
            $delightfulFlowDraftEntity->setDelightfulFlow(DelightfulFlowFactory::arrayToEntity($delightfulFlowVersionModel->delightful_flow, 'v0'));
        }

        $delightfulFlowDraftEntity->setOrganizationCode($delightfulFlowVersionModel->organization_code);
        $delightfulFlowDraftEntity->setCreator($delightfulFlowVersionModel->created_uid);
        $delightfulFlowDraftEntity->setCreatedAt($delightfulFlowVersionModel->created_at);
        $delightfulFlowDraftEntity->setModifier($delightfulFlowVersionModel->updated_uid);
        $delightfulFlowDraftEntity->setUpdatedAt($delightfulFlowVersionModel->updated_at);

        return $delightfulFlowDraftEntity;
    }
}

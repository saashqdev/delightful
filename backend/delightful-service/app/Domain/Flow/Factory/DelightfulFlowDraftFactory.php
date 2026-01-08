<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Factory;

use App\Domain\Flow\Entity\DelightfulFlowDraftEntity;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowDraftModel;

class DelightfulFlowDraftFactory
{
    public static function modelToEntity(DelightfulFlowDraftModel $delightfulFlowDraftModel): DelightfulFlowDraftEntity
    {
        $delightfulFlowDraftEntity = new DelightfulFlowDraftEntity();
        $delightfulFlowDraftEntity->setId($delightfulFlowDraftModel->id);
        $delightfulFlowDraftEntity->setFlowCode($delightfulFlowDraftModel->flow_code);
        $delightfulFlowDraftEntity->setCode($delightfulFlowDraftModel->code);
        $delightfulFlowDraftEntity->setName($delightfulFlowDraftModel->name);
        $delightfulFlowDraftEntity->setDescription($delightfulFlowDraftModel->description);
        if (! empty($delightfulFlowDraftModel->delightful_flow)) {
            $delightfulFlowDraftEntity->setDelightfulFlow($delightfulFlowDraftModel->delightful_flow);
        }
        $delightfulFlowDraftEntity->setOrganizationCode($delightfulFlowDraftModel->organization_code);
        $delightfulFlowDraftEntity->setCreator($delightfulFlowDraftModel->created_uid);
        $delightfulFlowDraftEntity->setCreatedAt($delightfulFlowDraftModel->created_at);
        $delightfulFlowDraftEntity->setModifier($delightfulFlowDraftModel->updated_uid);
        $delightfulFlowDraftEntity->setUpdatedAt($delightfulFlowDraftModel->updated_at);

        return $delightfulFlowDraftEntity;
    }
}

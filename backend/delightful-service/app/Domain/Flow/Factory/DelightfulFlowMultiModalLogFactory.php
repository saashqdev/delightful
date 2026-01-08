<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Factory;

use App\Domain\Flow\Entity\DelightfulFlowMultiModalLogEntity;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowMultiModalLogModel;
use Carbon\Carbon;

class DelightfulFlowMultiModalLogFactory
{
    public static function modelToEntity(DelightfulFlowMultiModalLogModel $model): DelightfulFlowMultiModalLogEntity
    {
        $entity = new DelightfulFlowMultiModalLogEntity();
        $entity->setId($model->id);
        $entity->setMessageId($model->message_id);
        $entity->setType($model->type);
        $entity->setModel($model->model);
        $entity->setAnalysisResult($model->analysis_result);
        $entity->setCreatedAt($model->created_at);
        $entity->setUpdatedAt($model->updated_at);
        return $entity;
    }

    public static function entityToModel(DelightfulFlowMultiModalLogEntity $entity): DelightfulFlowMultiModalLogModel
    {
        $model = new DelightfulFlowMultiModalLogModel();
        if ($entity->getId() !== null) {
            $model->id = $entity->getId();
        }
        $model->message_id = $entity->getMessageId();
        $model->type = $entity->getType();
        $model->model = $entity->getModel();
        $model->analysis_result = $entity->getAnalysisResult();
        $model->created_at = Carbon::make($entity->getCreatedAt());
        $model->updated_at = Carbon::make($entity->getUpdatedAt());
        return $model;
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowMultiModalLogEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Factory\DelightfulFlowMultiModalLogFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowMultiModalLogRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowMultiModalLogModel;

class DelightfulFlowMultiModalLogRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowMultiModalLogRepositoryInterface
{
    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowMultiModalLogEntity $entity): DelightfulFlowMultiModalLogEntity
    {
        $model = DelightfulFlowMultiModalLogFactory::entityToModel($entity);
        $model->save();
        return DelightfulFlowMultiModalLogFactory::modelToEntity($model);
    }

    public function getById(FlowDataIsolation $dataIsolation, int $id): ?DelightfulFlowMultiModalLogEntity
    {
        $query = $this->createBuilder($dataIsolation, DelightfulFlowMultiModalLogModel::query());
        $model = $query->where('id', $id)->first();

        if (empty($model)) {
            return null;
        }

        return DelightfulFlowMultiModalLogFactory::modelToEntity($model);
    }

    public function getByMessageId(FlowDataIsolation $dataIsolation, string $messageId): ?DelightfulFlowMultiModalLogEntity
    {
        $query = $this->createBuilder($dataIsolation, DelightfulFlowMultiModalLogModel::query());
        $model = $query->where('message_id', $messageId)->first();

        if (empty($model)) {
            return null;
        }

        return DelightfulFlowMultiModalLogFactory::modelToEntity($model);
    }

    /**
     * 批量get多个messageID对应的多模态logrecord.
     *
     * @param array<string> $messageIds
     * @return array<DelightfulFlowMultiModalLogEntity>
     */
    public function getByMessageIds(FlowDataIsolation $dataIsolation, array $messageIds, bool $keyByMessageId = false): array
    {
        $messageIds = array_filter($messageIds);
        if (empty($messageIds)) {
            return [];
        }

        $query = $this->createBuilder($dataIsolation, DelightfulFlowMultiModalLogModel::query());
        $models = $query->whereIn('message_id', $messageIds)->get();

        if ($models->isEmpty()) {
            return [];
        }

        $entities = [];
        foreach ($models as $model) {
            $entity = DelightfulFlowMultiModalLogFactory::modelToEntity($model);
            if ($keyByMessageId) {
                $entities[$entity->getMessageId()] = $entity;
            } else {
                $entities[] = $entity;
            }
        }

        return $entities;
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowMemoryHistoryEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowMemoryHistoryQuery;
use App\Domain\Flow\Factory\DelightfulFlowMemoryHistoryFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowMemoryHistoryRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowMemoryHistoryModel;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowMemoryHistoryRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowMemoryHistoryRepositoryInterface
{
    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowMemoryHistoryEntity $delightfulFlowMemoryHistoryEntity): DelightfulFlowMemoryHistoryEntity
    {
        $model = new DelightfulFlowMemoryHistoryModel();
        $model->fill($this->getAttributes($delightfulFlowMemoryHistoryEntity));
        $model->save();
        $delightfulFlowMemoryHistoryEntity->setId($model->id);
        return $delightfulFlowMemoryHistoryEntity;
    }

    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowMemoryHistoryQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowMemoryHistoryModel::query());

        if ($query->getConversationId()) {
            $builder->where('conversation_id', $query->getConversationId());
        }
        if (! is_null($query->getTopicId())) {
            $builder->where('topic_id', $query->getTopicId());
        }

        if ($query->getType()) {
            $builder->where('type', $query->getType());
        }

        if ($query->getMountId()) {
            $builder->where('mount_id', $query->getMountId());
        }

        if (! empty($query->getMountIds())) {
            $builder->whereIn('mount_id', $query->getMountIds());
        }

        if (! empty($query->getIgnoreRequestIds())) {
            $builder->whereNotIn('request_id', $query->getIgnoreRequestIds());
        }

        if ($query->getStartTime()) {
            $builder->where('created_at', '>=', $query->getStartTime()->format('Y-m-d H:i:s'));
        }

        $data = $this->getByPage($builder, $page, $query);
        if (! empty($data['list'])) {
            $list = [];
            foreach ($data['list'] as $model) {
                $list[] = DelightfulFlowMemoryHistoryFactory::modelToEntity($model);
            }
            $data['list'] = $list;
        }

        return $data;
    }

    public function removeByConversationId(FlowDataIsolation $dataIsolation, string $conversationId): void
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowMemoryHistoryModel::query());
        $builder->where('conversation_id', $conversationId)->update(['conversation_id' => $conversationId . '-d' . time()]);
    }
}

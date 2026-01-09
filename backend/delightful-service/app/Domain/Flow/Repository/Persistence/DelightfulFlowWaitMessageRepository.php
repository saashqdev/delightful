<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowWaitMessageEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Factory\DelightfulFlowWaitMessageFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowWaitMessageRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowWaitMessageModel;

class DelightfulFlowWaitMessageRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowWaitMessageRepositoryInterface
{
    protected bool $filterOrganizationCode = true;

    public function save(DelightfulFlowWaitMessageEntity $waitMessageEntity): DelightfulFlowWaitMessageEntity
    {
        if (! $waitMessageEntity->getId()) {
            $model = new DelightfulFlowWaitMessageModel();
        } else {
            /** @var DelightfulFlowWaitMessageModel $model */
            $model = DelightfulFlowWaitMessageModel::find($waitMessageEntity->getId());
        }

        $model->fill($this->getAttributes($waitMessageEntity));
        $model->save();

        $waitMessageEntity->setId($model->id);

        return $waitMessageEntity;
    }

    public function find(FlowDataIsolation $dataIsolation, int $id): ?DelightfulFlowWaitMessageEntity
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowWaitMessageModel::query());
        /** @var null|DelightfulFlowWaitMessageModel $model */
        $model = $builder->where('id', $id)->first();
        if (! $model) {
            return null;
        }
        return DelightfulFlowWaitMessageFactory::modelToEntity($model);
    }

    public function handled(FlowDataIsolation $dataIsolation, int $id): void
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowWaitMessageModel::query());
        $builder->where('id', $id)->update(['handled' => true]);
    }

    public function listByUnhandledConversationId(FlowDataIsolation $dataIsolation, string $conversationId): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowWaitMessageModel::query());
        $models = $builder
            // 这withinnotquery persistent_data，因for这fieldmaybewillvery大
            ->select(['id', 'organization_code', 'conversation_id', 'origin_conversation_id', 'message_id', 'wait_node_id', 'flow_code', 'flow_version', 'timeout', 'handled', 'created_uid', 'created_at', 'updated_uid', 'updated_at'])
            ->where('conversation_id', '=', $conversationId)
            ->where('handled', false)
            ->orderBy('id', 'asc')
            ->get();

        // useforeachloop代替mapmethod
        $result = [];
        foreach ($models as $model) {
            $result[] = DelightfulFlowWaitMessageFactory::modelToEntity($model);
        }
        return $result;
    }
}

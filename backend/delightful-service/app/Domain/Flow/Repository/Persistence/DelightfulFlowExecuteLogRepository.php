<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowExecuteLogEntity;
use App\Domain\Flow\Entity\ValueObject\ExecuteLogStatus;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Factory\DelightfulFlowExecuteLogFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowExecuteLogRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowExecuteLogModel;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowExecuteLogRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowExecuteLogRepositoryInterface
{
    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $delightfulFlowExecuteLogEntity): DelightfulFlowExecuteLogEntity
    {
        $model = new DelightfulFlowExecuteLogModel();
        $model->fill($this->getAttributes($delightfulFlowExecuteLogEntity));
        $model->save();
        $delightfulFlowExecuteLogEntity->setId($model->id);
        return $delightfulFlowExecuteLogEntity;
    }

    public function updateStatus(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $delightfulFlowExecuteLogEntity): void
    {
        $update = [
            'status' => $delightfulFlowExecuteLogEntity->getStatus()->value,
        ];
        // if是completestatus，记录结果
        if ($delightfulFlowExecuteLogEntity->getStatus()->isFinished()) {
            $update['result'] = json_encode($delightfulFlowExecuteLogEntity->getResult(), JSON_UNESCAPED_UNICODE);
        }
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowExecuteLogModel::query());
        $builder->where('id', $delightfulFlowExecuteLogEntity->getId())
            ->update($update);
    }

    /**
     * @return DelightfulFlowExecuteLogEntity[]
     */
    public function getRunningTimeoutList(FlowDataIsolation $dataIsolation, int $timeout, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowExecuteLogModel::query());
        $builder = $builder
            ->whereIn('status', [ExecuteLogStatus::Running, ExecuteLogStatus::Pending])
            // 只retry顶层
            ->where('level', 0)
            // retry次数less than 3 次
            ->where('retry_count', '<', 1)
            // 只get最近 2 小时内的data，超过 2 小时的data不再process
            ->where('created_at', '>', date('Y-m-d H:i:s', time() - 7200))
            ->where('created_at', '<', date('Y-m-d H:i:s', time() - $timeout))
            ->forPage($page->getPage(), $page->getPageNum());
        $models = $builder->get();
        $result = [];
        foreach ($models as $model) {
            $result[] = DelightfulFlowExecuteLogFactory::modelToEntity($model);
        }
        return $result;
    }

    public function getByExecuteId(FlowDataIsolation $dataIsolation, string $executeId): ?DelightfulFlowExecuteLogEntity
    {
        if (empty($executeId)) {
            return null;
        }
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowExecuteLogModel::query());
        if (strlen($executeId) === 18 && is_numeric($executeId)) {
            // primary keyquery
            $model = $builder->where('id', $executeId)->first();
        } else {
            $model = $builder->where('execute_data_id', $executeId)->first();
        }

        if ($model === null) {
            return null;
        }
        return DelightfulFlowExecuteLogFactory::modelToEntity($model);
    }

    public function incrementRetryCount(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $delightfulFlowExecuteLogEntity): void
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowExecuteLogModel::query());
        $builder->where('id', $delightfulFlowExecuteLogEntity->getId())
            ->increment('retry_count');
        $delightfulFlowExecuteLogEntity->setRetryCount($delightfulFlowExecuteLogEntity->getRetryCount() + 1);
    }
}

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
    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $magicFlowExecuteLogEntity): DelightfulFlowExecuteLogEntity
    {
        $model = new DelightfulFlowExecuteLogModel();
        $model->fill($this->getAttributes($magicFlowExecuteLogEntity));
        $model->save();
        $magicFlowExecuteLogEntity->setId($model->id);
        return $magicFlowExecuteLogEntity;
    }

    public function updateStatus(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $magicFlowExecuteLogEntity): void
    {
        $update = [
            'status' => $magicFlowExecuteLogEntity->getStatus()->value,
        ];
        // 如果是完成状态，记录结果
        if ($magicFlowExecuteLogEntity->getStatus()->isFinished()) {
            $update['result'] = json_encode($magicFlowExecuteLogEntity->getResult(), JSON_UNESCAPED_UNICODE);
        }
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowExecuteLogModel::query());
        $builder->where('id', $magicFlowExecuteLogEntity->getId())
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
            // 只重试顶层
            ->where('level', 0)
            // 重试次数小于 3 次
            ->where('retry_count', '<', 1)
            // 只获取最近 2 小时内的数据，超过 2 小时的数据不再处理
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
            // 主键查询
            $model = $builder->where('id', $executeId)->first();
        } else {
            $model = $builder->where('execute_data_id', $executeId)->first();
        }

        if ($model === null) {
            return null;
        }
        return DelightfulFlowExecuteLogFactory::modelToEntity($model);
    }

    public function incrementRetryCount(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $magicFlowExecuteLogEntity): void
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowExecuteLogModel::query());
        $builder->where('id', $magicFlowExecuteLogEntity->getId())
            ->increment('retry_count');
        $magicFlowExecuteLogEntity->setRetryCount($magicFlowExecuteLogEntity->getRetryCount() + 1);
    }
}

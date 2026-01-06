<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowTriggerTestcaseEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowTriggerTestcaseQuery;
use App\Domain\Flow\Factory\DelightfulFlowTriggerTestcaseFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowTriggerTestcaseRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowTriggerTestcaseModel;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowTriggerTestcaseRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowTriggerTestcaseRepositoryInterface
{
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowTriggerTestcaseEntity $magicFlowTriggerTestcaseEntity): DelightfulFlowTriggerTestcaseEntity
    {
        if (! $magicFlowTriggerTestcaseEntity->getId()) {
            $magicFlowTriggerTestcaseModel = new DelightfulFlowTriggerTestcaseModel();
        } else {
            $builder = $this->createBuilder($dataIsolation, DelightfulFlowTriggerTestcaseModel::query());
            /** @var DelightfulFlowTriggerTestcaseModel $magicFlowTriggerTestcaseModel */
            $magicFlowTriggerTestcaseModel = $builder->where('id', $magicFlowTriggerTestcaseEntity->getId())->first();
        }

        $magicFlowTriggerTestcaseModel->fill($this->getAttributes($magicFlowTriggerTestcaseEntity));
        $magicFlowTriggerTestcaseModel->save();

        $magicFlowTriggerTestcaseEntity->setId($magicFlowTriggerTestcaseModel->id);

        return $magicFlowTriggerTestcaseEntity;
    }

    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowTriggerTestcaseEntity
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowTriggerTestcaseModel::query());
        /** @var null|DelightfulFlowTriggerTestcaseModel $model */
        $model = $builder->where('code', $code)->first();
        if (! $model) {
            return null;
        }
        return DelightfulFlowTriggerTestcaseFactory::modelToEntity($model);
    }

    public function getByFlowCodeAndCode(FlowDataIsolation $dataIsolation, string $flowCode, string $code): ?DelightfulFlowTriggerTestcaseEntity
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowTriggerTestcaseModel::query());
        /** @var null|DelightfulFlowTriggerTestcaseModel $model */
        $model = $builder->where('flow_code', $flowCode)->where('code', $code)->first();
        if (! $model) {
            return null;
        }
        return DelightfulFlowTriggerTestcaseFactory::modelToEntity($model);
    }

    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowTriggerTestcaseQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowTriggerTestcaseModel::query());
        if ($query->flowCode) {
            $builder->where('flow_code', $query->flowCode);
        }
        $data = $this->getByPage($builder, $page, $query);
        if (! empty($data['list'])) {
            $list = [];
            foreach ($data['list'] as $model) {
                $list[] = DelightfulFlowTriggerTestcaseFactory::modelToEntity($model);
            }
            $data['list'] = $list;
        }

        return $data;
    }

    public function remove(FlowDataIsolation $dataIsolation, DelightfulFlowTriggerTestcaseEntity $magicFlowTriggerTestcaseEntity): void
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowTriggerTestcaseModel::query());
        $builder->where('code', $magicFlowTriggerTestcaseEntity->getCode())->delete();
    }
}

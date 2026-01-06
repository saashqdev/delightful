<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowDraftEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowDraftQuery;
use App\Domain\Flow\Factory\DelightfulFlowDraftFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowDraftRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowDraftModel;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowDraftRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowDraftRepositoryInterface
{
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowDraftEntity $delightfulFlowDraftEntity): DelightfulFlowDraftEntity
    {
        if (! $delightfulFlowDraftEntity->getId()) {
            $delightfulFlowDraftModel = new DelightfulFlowDraftModel();
        } else {
            $builder = $this->createBuilder($dataIsolation, DelightfulFlowDraftModel::query());
            $delightfulFlowDraftModel = $builder->where('id', $delightfulFlowDraftEntity->getId())->first();
        }

        $delightfulFlowDraftModel->fill($this->getAttributes($delightfulFlowDraftEntity));
        $delightfulFlowDraftModel->save();

        $delightfulFlowDraftEntity->setId($delightfulFlowDraftModel->id);

        return $delightfulFlowDraftEntity;
    }

    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowDraftEntity
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowDraftModel::query());
        /** @var null|DelightfulFlowDraftModel $draftModel */
        $draftModel = $builder->where('code', $code)->first();
        if (! $draftModel) {
            return null;
        }
        return DelightfulFlowDraftFactory::modelToEntity($draftModel);
    }

    public function getByFlowCodeAndCode(FlowDataIsolation $dataIsolation, string $flowCode, string $code): ?DelightfulFlowDraftEntity
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowDraftModel::query());
        /** @var null|DelightfulFlowDraftModel $draftModel */
        $draftModel = $builder->where('flow_code', $flowCode)->where('code', $code)->first();
        if (! $draftModel) {
            return null;
        }
        return DelightfulFlowDraftFactory::modelToEntity($draftModel);
    }

    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowDraftQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowDraftModel::query());
        if ($query->flowCode) {
            $builder->where('flow_code', $query->flowCode);
        }

        $data = $this->getByPage($builder, $page, $query);
        if (! empty($data['list'])) {
            $list = [];
            foreach ($data['list'] as $draftModel) {
                $list[] = DelightfulFlowDraftFactory::modelToEntity($draftModel);
            }
            $data['list'] = $list;
        }

        return $data;
    }

    public function remove(FlowDataIsolation $dataIsolation, DelightfulFlowDraftEntity $delightfulFlowDraftEntity): void
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowDraftModel::query());
        $builder->where('code', $delightfulFlowDraftEntity->getCode())->delete();
    }

    public function clearEarlyRecords(FlowDataIsolation $dataIsolation, string $flowCode): void
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowDraftModel::query());
        $builder->where('flow_code', $flowCode)->orderBy('id', 'asc');

        $count = $builder->count();

        if ($count > DelightfulFlowDraftEntity::MAX_RECORD) {
            $builder->offset(DelightfulFlowDraftEntity::MAX_RECORD)->take($count - DelightfulFlowDraftEntity::MAX_RECORD)->delete();
        }
    }
}

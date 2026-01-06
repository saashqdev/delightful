<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowVersionEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowVersionQuery;
use App\Domain\Flow\Factory\DelightfulFlowVersionFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowVersionRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowVersionModel;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowVersionRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowVersionRepositoryInterface
{
    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowVersionEntity $magicFlowVersionEntity): DelightfulFlowVersionEntity
    {
        $model = new DelightfulFlowVersionModel();

        $model->fill($this->getAttributes($magicFlowVersionEntity));
        $model->save();

        $magicFlowVersionEntity->setId($model->id);
        return $magicFlowVersionEntity;
    }

    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowVersionEntity
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowVersionModel::query());
        /** @var null|DelightfulFlowVersionModel $model */
        $model = $builder->where('code', $code)->first();
        if (! $model) {
            return null;
        }
        return DelightfulFlowVersionFactory::modelToEntity($model);
    }

    public function getByFlowCodeAndCode(FlowDataIsolation $dataIsolation, string $flowCode, string $code): ?DelightfulFlowVersionEntity
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowVersionModel::query());
        /** @var null|DelightfulFlowVersionModel $model */
        $model = $builder->where('flow_code', $flowCode)->where('code', $code)->first();
        if (! $model) {
            return null;
        }
        return DelightfulFlowVersionFactory::modelToEntity($model);
    }

    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowVersionQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowVersionModel::query());
        if ($query->flowCode) {
            $builder->where('flow_code', $query->flowCode);
        }
        $data = $this->getByPage($builder, $page, $query);
        if (! empty($data['list'])) {
            $list = [];
            foreach ($data['list'] as $model) {
                $list[] = DelightfulFlowVersionFactory::modelToEntity($model);
            }
            $data['list'] = $list;
        }

        return $data;
    }

    public function getLastVersion(FlowDataIsolation $dataIsolation, string $flowCode): ?DelightfulFlowVersionEntity
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowVersionModel::query());
        /** @var null|DelightfulFlowVersionModel $model */
        $model = $builder->where('flow_code', $flowCode)->orderByDesc('id')->first();
        if (! $model) {
            return null;
        }
        return DelightfulFlowVersionFactory::modelToEntity($model);
    }

    public function existVersion(FlowDataIsolation $dataIsolation, string $flowCode): bool
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowVersionModel::query());
        return $builder->where('flow_code', $flowCode)->exists();
    }

    public function getByCodes(FlowDataIsolation $dataIsolation, array $versionCodes): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowVersionModel::query());
        /** @var array<DelightfulFlowVersionModel> $models */
        $models = $builder->whereIn('code', $versionCodes)->get();
        if (empty($models)) {
            return [];
        }
        $list = [];
        foreach ($models as $model) {
            $list[] = DelightfulFlowVersionFactory::modelToEntity($model);
        }
        return $list;
    }
}

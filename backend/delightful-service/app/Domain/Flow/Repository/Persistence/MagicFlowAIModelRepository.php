<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowAIModelEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowAIModelQuery;
use App\Domain\Flow\Factory\DelightfulFlowAIModelFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowAIModelRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowAIModelModel;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowAIModelRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowAIModelRepositoryInterface
{
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowAIModelEntity $delightfulFlowAIModelEntity): DelightfulFlowAIModelEntity
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowAIModelModel::query());
        $model = $builder->where('name', $delightfulFlowAIModelEntity->getName())->first();
        if (! $model) {
            $model = new DelightfulFlowAIModelModel();
        }
        $model->fill($this->getAttributes($delightfulFlowAIModelEntity));
        $model->save();
        $delightfulFlowAIModelEntity->setId($model->id);
        return $delightfulFlowAIModelEntity;
    }

    public function getByName(FlowDataIsolation $dataIsolation, string $name): ?DelightfulFlowAIModelEntity
    {
        if (empty($name)) {
            return null;
        }
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowAIModelModel::query());
        /** @var null|DelightfulFlowAIModelModel $model */
        $model = $builder->where('name', $name)->first();
        if (! $model) {
            return null;
        }
        return DelightfulFlowAIModelFactory::modelToEntity($model);
    }

    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowAIModelQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowAIModelModel::query());

        if ($query->getEnabled() !== null) {
            $builder->where('enabled', $query->getEnabled());
        }
        if ($query->getDisplay() !== null) {
            $builder->where('display', $query->getDisplay());
        }
        if ($query->getSupportEmbedding() !== null) {
            $builder->where('support_embedding', $query->getSupportEmbedding());
        }

        $data = $this->getByPage($builder, $page, $query);

        if (! empty($data['list'])) {
            $list = [];
            foreach ($data['list'] as $value) {
                $list[] = DelightfulFlowAIModelFactory::modelToEntity($value);
            }
            $data['list'] = $list;
        }

        return $data;
    }
}

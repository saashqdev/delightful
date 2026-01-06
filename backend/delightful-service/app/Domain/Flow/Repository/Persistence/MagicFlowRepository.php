<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowQuery;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\Domain\Flow\Factory\DelightfulFlowFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowModel;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowRepositoryInterface
{
    protected bool $filterOrganizationCode = true;

    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $magicFlowEntity): DelightfulFlowEntity
    {
        if (! $magicFlowEntity->getId()) {
            $magicFlowModel = new DelightfulFlowModel();
        } else {
            /** @var DelightfulFlowModel $magicFlowModel */
            $magicFlowModel = DelightfulFlowModel::find($magicFlowEntity->getId());
        }

        $magicFlowModel->fill($this->getAttributes($magicFlowEntity));
        $magicFlowModel->save();

        $magicFlowEntity->setId($magicFlowModel->id);

        return $magicFlowEntity;
    }

    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowEntity
    {
        if (empty($code)) {
            return null;
        }
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowModel::query());
        /** @var null|DelightfulFlowModel $magicFlowModel */
        $magicFlowModel = $builder->where('code', $code)->first();

        if (! $magicFlowModel) {
            return null;
        }

        return DelightfulFlowFactory::modelToEntity($magicFlowModel);
    }

    public function getByCodes(FlowDataIsolation $dataIsolation, array $codes): array
    {
        if (empty($codes)) {
            return [];
        }
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowModel::query());
        $magicFlowModels = $builder->whereIn('code', $codes)->get();

        $result = [];
        foreach ($magicFlowModels as $magicFlowModel) {
            $result[] = DelightfulFlowFactory::modelToEntity($magicFlowModel);
        }
        return $result;
    }

    public function getByName(FlowDataIsolation $dataIsolation, string $name, Type $type): ?DelightfulFlowEntity
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowModel::query());
        /** @var null|DelightfulFlowModel $magicFlowModel */
        $magicFlowModel = $builder
            ->where('name', $name)
            ->where('type', $type->value)
            ->first();

        if (! $magicFlowModel) {
            return null;
        }

        return DelightfulFlowFactory::modelToEntity($magicFlowModel);
    }

    public function remove(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $magicFlowEntity): void
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowModel::query());
        $builder->where('code', $magicFlowEntity->getCode())->delete();
    }

    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowModel::query());
        if ($query->type) {
            $builder->where('type', $query->type);
        }
        if (! is_null($query->getCodes())) {
            $builder->whereIn('code', $query->getCodes());
        }
        if (! empty($query->getToolSetId())) {
            $builder->where('tool_set_id', $query->getToolSetId());
        }
        if (! is_null($query->getToolSetIds())) {
            $builder->whereIn('tool_set_id', $query->getToolSetIds());
        }
        if (! is_null($query->getEnabled())) {
            $builder->where('enabled', $query->getEnabled());
        }
        if (! empty($query->getName())) {
            $builder->where('name', 'like', "%{$query->getName()}%");
        }
        $data = $this->getByPage($builder, $page, $query);

        if (! empty($data['list'])) {
            $list = [];
            foreach ($data['list'] as $magicFlowModel) {
                $list[] = DelightfulFlowFactory::modelToEntity($magicFlowModel);
            }
            $data['list'] = $list;
        }

        return $data;
    }

    public function changeEnable(FlowDataIsolation $dataIsolation, string $code, bool $enable): void
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowModel::query());
        $builder
            ->where('code', $code)
            ->update([
                'enabled' => $enable,
                'updated_uid' => $dataIsolation->getCurrentUserId(),
            ]);
    }

    public function getToolsInfo(FlowDataIsolation $dataIsolation, string $toolSetId): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowModel::query());
        $builder->select('tool_set_id', 'code', 'name', 'description', 'icon', 'enabled');
        $builder->where('type', Type::Tools->value);
        $builder->where('tool_set_id', $toolSetId);

        return $builder->get()->toArray();
    }
}

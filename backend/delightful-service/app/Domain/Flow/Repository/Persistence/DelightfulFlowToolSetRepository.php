<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowToolSetEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowToolSetQuery;
use App\Domain\Flow\Factory\DelightfulFlowToolSetFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowToolSetRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowToolSetModel;
use App\Infrastructure\Core\ValueObject\Page;
use Hyperf\Database\Model\Relations\HasMany;

class DelightfulFlowToolSetRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowToolSetRepositoryInterface
{
    protected bool $filterOrganizationCode = true;

    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowToolSetEntity $delightfulFlowToolSetEntity): DelightfulFlowToolSetEntity
    {
        /** @var DelightfulFlowToolSetModel $model */
        $model = $this->createBuilder($dataIsolation, DelightfulFlowToolSetModel::query())->firstOrNew([
            'code' => $delightfulFlowToolSetEntity->getCode(),
        ]);

        $model->fill($this->getAttributes($delightfulFlowToolSetEntity));
        $model->save();
        $delightfulFlowToolSetEntity->setId($model->id);
        return $delightfulFlowToolSetEntity;
    }

    public function destroy(FlowDataIsolation $dataIsolation, string $code): void
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowToolSetModel::query());
        $builder->where('code', $code)->delete();
    }

    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowToolSetQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowToolSetModel::query());

        if (! is_null($query->getCodes())) {
            $builder->whereIn('code', $query->getCodes());
        }
        if (! is_null($query->getEnabled())) {
            $builder->where('enabled', $query->getEnabled());
        }

        if ($query->withToolsSimpleInfo) {
            $builder->with(['tools' => function (HasMany $hasMany) {
                $hasMany->select(['tool_set_id', 'code', 'name', 'description', 'icon', 'enabled', 'updated_at'])->orderBy('updated_at', 'desc');
            }]);
        }
        if (! empty($query->name)) {
            $builder->where('name', 'like', "%{$query->name}%");
        }

        $data = $this->getByPage($builder, $page, $query);
        if (! empty($data['list'])) {
            $list = [];
            foreach ($data['list'] as $model) {
                $list[] = DelightfulFlowToolSetFactory::modelToEntity($model);
            }
            $data['list'] = $list;
        }
        return $data;
    }

    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowToolSetEntity
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowToolSetModel::query());
        /** @var null|DelightfulFlowToolSetModel $model */
        $model = $builder->where('code', $code)->first();
        if (! $model) {
            return null;
        }
        return DelightfulFlowToolSetFactory::modelToEntity($model);
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Repository\Persistence;

use App\Infrastructure\Core\ValueObject\Page;
use BeDelightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use BeDelightful\BeDelightful\Domain\Agent\Entity\ValueObject\Query\BeDelightfulAgentQuery;
use BeDelightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentDataIsolation;
use BeDelightful\BeDelightful\Domain\Agent\Factory\BeDelightfulAgentFactory;
use BeDelightful\BeDelightful\Domain\Agent\Repository\Facade\BeDelightfulAgentRepositoryInterface;
use BeDelightful\BeDelightful\Domain\Agent\Repository\Persistence\Model\BeDelightfulAgentModel;

class BeDelightfulAgentRepository extends BeDelightfulAbstractRepository implements BeDelightfulAgentRepositoryInterface
{
    public function getByCode(BeDelightfulAgentDataIsolation $dataIsolation, string $code): ?BeDelightfulAgentEntity
    {
        $builder = $this->createBuilder($dataIsolation, BeDelightfulAgentModel::query());

        /** @var null|BeDelightfulAgentModel $model */
        $model = $builder->where('code', $code)->first();

        if (! $model) {
            return null;
        }

        return BeDelightfulAgentFactory::createEntity($model);
    }

    public function queries(BeDelightfulAgentDataIsolation $dataIsolation, BeDelightfulAgentQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, BeDelightfulAgentModel::query());

        if (! is_null($query->getCodes())) {
            if (empty($query->getCodes())) {
                return ['total' => 0, 'list' => []];
            }
            $builder->whereIn('code', $query->getCodes());
        }

        if ($query->getCreatorId() !== null) {
            $builder->where('creator', $query->getCreatorId());
        }

        if ($query->getName()) {
            $builder->where('name', 'like', '%' . $query->getName() . '%');
        }

        if ($query->getEnabled() !== null) {
            $builder->where('enabled', $query->getEnabled());
        }

        $result = $this->getByPage($builder, $page, $query);

        $list = [];

        /** @var BeDelightfulAgentModel $model */
        foreach ($result['list'] as $model) {
            $entity = BeDelightfulAgentFactory::createEntity($model);
            $list[] = $entity;
        }
        $result['list'] = $list;

        return $result;
    }

    public function save(BeDelightfulAgentDataIsolation $dataIsolation, BeDelightfulAgentEntity $entity): BeDelightfulAgentEntity
    {
        if (! $entity->getId()) {
            $model = new BeDelightfulAgentModel();
        } else {
            $builder = $this->createBuilder($dataIsolation, BeDelightfulAgentModel::query());
            $model = $builder->where('id', $entity->getId())->first();
        }

        $model->fill($this->getAttributes($entity));
        $model->save();

        $entity->setId($model->id);
        return $entity;
    }

    public function delete(BeDelightfulAgentDataIsolation $dataIsolation, string $code): bool
    {
        $builder = $this->createBuilder($dataIsolation, BeDelightfulAgentModel::query());
        return $builder->where('code', $code)->delete() > 0;
    }

    public function countByCreator(BeDelightfulAgentDataIsolation $dataIsolation, string $creator): int
    {
        $builder = $this->createBuilder($dataIsolation, BeDelightfulAgentModel::query());
        return $builder->where('creator', $creator)->count();
    }
}

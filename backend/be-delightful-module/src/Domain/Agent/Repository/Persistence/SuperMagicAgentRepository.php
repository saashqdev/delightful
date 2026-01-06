<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperDelightful\Domain\Agent\Repository\Persistence;

use App\Infrastructure\Core\ValueObject\Page;
use Delightful\SuperDelightful\Domain\Agent\Entity\SuperDelightfulAgentEntity;
use Delightful\SuperDelightful\Domain\Agent\Entity\ValueObject\Query\SuperDelightfulAgentQuery;
use Delightful\SuperDelightful\Domain\Agent\Entity\ValueObject\SuperDelightfulAgentDataIsolation;
use Delightful\SuperDelightful\Domain\Agent\Factory\SuperDelightfulAgentFactory;
use Delightful\SuperDelightful\Domain\Agent\Repository\Facade\SuperDelightfulAgentRepositoryInterface;
use Delightful\SuperDelightful\Domain\Agent\Repository\Persistence\Model\SuperDelightfulAgentModel;

class SuperDelightfulAgentRepository extends SuperDelightfulAbstractRepository implements SuperDelightfulAgentRepositoryInterface
{
    public function getByCode(SuperDelightfulAgentDataIsolation $dataIsolation, string $code): ?SuperDelightfulAgentEntity
    {
        $builder = $this->createBuilder($dataIsolation, SuperDelightfulAgentModel::query());

        /** @var null|SuperDelightfulAgentModel $model */
        $model = $builder->where('code', $code)->first();

        if (! $model) {
            return null;
        }

        return SuperDelightfulAgentFactory::createEntity($model);
    }

    public function queries(SuperDelightfulAgentDataIsolation $dataIsolation, SuperDelightfulAgentQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, SuperDelightfulAgentModel::query());

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

        /** @var SuperDelightfulAgentModel $model */
        foreach ($result['list'] as $model) {
            $entity = SuperDelightfulAgentFactory::createEntity($model);
            $list[] = $entity;
        }
        $result['list'] = $list;

        return $result;
    }

    public function save(SuperDelightfulAgentDataIsolation $dataIsolation, SuperDelightfulAgentEntity $entity): SuperDelightfulAgentEntity
    {
        if (! $entity->getId()) {
            $model = new SuperDelightfulAgentModel();
        } else {
            $builder = $this->createBuilder($dataIsolation, SuperDelightfulAgentModel::query());
            $model = $builder->where('id', $entity->getId())->first();
        }

        $model->fill($this->getAttributes($entity));
        $model->save();

        $entity->setId($model->id);
        return $entity;
    }

    public function delete(SuperDelightfulAgentDataIsolation $dataIsolation, string $code): bool
    {
        $builder = $this->createBuilder($dataIsolation, SuperDelightfulAgentModel::query());
        return $builder->where('code', $code)->delete() > 0;
    }

    public function countByCreator(SuperDelightfulAgentDataIsolation $dataIsolation, string $creator): int
    {
        $builder = $this->createBuilder($dataIsolation, SuperDelightfulAgentModel::query());
        return $builder->where('creator', $creator)->count();
    }
}

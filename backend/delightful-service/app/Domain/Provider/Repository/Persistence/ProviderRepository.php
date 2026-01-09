<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Persistence;

use App\Domain\Provider\Entity\ProviderEntity;
use App\Domain\Provider\Entity\ValueObject\Category;
use App\Domain\Provider\Entity\ValueObject\ProviderCode;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\ProviderType;
use App\Domain\Provider\Entity\ValueObject\Query\ProviderQuery;
use App\Domain\Provider\Repository\Facade\ProviderRepositoryInterface;
use App\Domain\Provider\Repository\Persistence\Model\ProviderModel;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Provider\Assembler\ProviderAssembler;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;

class ProviderRepository extends AbstractModelRepository implements ProviderRepositoryInterface
{
    public function getById(int $id): ?ProviderEntity
    {
        $builder = $this->createProviderQuery();
        $builder->where('id', $id);
        $result = Db::select($builder->toSql(), $builder->getBindings());
        if (empty($result)) {
            return null;
        }

        return ProviderAssembler::toEntity($result[0]);
    }

    /**
     * @param array<int> $ids
     * @return array<int, ProviderEntity> returnbyidforkey实bodyobjectarray
     */
    public function getByIds(array $ids): array
    {
        $builder = $this->createProviderQuery();
        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            return [];
        }

        // 仅pullfinger定 ID，避免alltable扫描
        $builder->whereIn('id', $ids);

        $result = Db::select($builder->toSql(), $builder->getBindings());

        $entities = [];
        foreach ($result as $model) {
            $modelArray = (array) $model;
            $entities[$modelArray['id']] = ProviderAssembler::toEntity($modelArray);
        }

        return $entities;
    }

    public function getByCode(string $providerCode): ?ProviderEntity
    {
        $builder = $this->createProviderQuery();

        $builder->where('provider_code', $providerCode);

        $result = Db::select($builder->toSql(), $builder->getBindings());

        if (empty($result)) {
            return null;
        }

        return ProviderAssembler::toEntity($result[0]);
    }

    /**
     * @return array{total: int, list: array<ProviderEntity>}
     */
    public function queries(ProviderDataIsolation $dataIsolation, ProviderQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, ProviderModel::query());

        if ($query->getCategory()) {
            $builder->where('category', $query->getCategory()->value);
        }

        if ($query->getStatus()) {
            $builder->where('status', $query->getStatus()->value);
        }

        if ($query->getProviderCode()) {
            $builder->where('provider_code', $query->getProviderCode()->value);
        }

        if ($query->getProviderType()) {
            $builder->where('provider_type', $query->getProviderType()->value);
        }

        if (! is_null($query->getIds())) {
            $builder->whereIn('id', $query->getIds());
        }

        $result = $this->getByPage($builder, $page, $query);

        $list = [];
        foreach ($result['list'] as $model) {
            $entity = ProviderAssembler::toEntity($model->toArray());
            match ($query->getKeyBy()) {
                'id' => $list[$entity->getId()] = $entity,
                default => $list[] = $entity,
            };
        }
        $result['list'] = $list;

        return $result;
    }

    public function getAllNonOfficialProviders(Category $category): array
    {
        // todo
        return [];
    }

    public function findById(int $id): ?ProviderEntity
    {
        $model = $this->createProviderQuery()
            ->where('id', $id)
            ->first();
        if (! $model) {
            return null;
        }
        return ProviderAssembler::toEntity($model->toArray());
    }

    public function getOfficial(?Category $serviceProviderCategory): ?ProviderEntity
    {
        $query = $this->createProviderQuery();

        if ($serviceProviderCategory) {
            $query->where('category', $serviceProviderCategory->value);
        }
        $query->where('provider_type', ProviderType::Official->value);
        $result = Db::select($query->toSql(), $query->getBindings());
        if (empty($result)) {
            return null;
        }
        return ProviderAssembler::toEntity($result[0]);
    }

    /**
     * getfinger定category别non官方servicequotient (Legacy).
     *
     * @param Category $category servicequotientcategory别
     * @return ProviderEntity[] non官方servicequotientlist
     */
    public function getNonOfficialByCategory(Category $category): array
    {
        $query = $this->createProviderQuery();
        $query->where('category', $category->value)
            ->where('provider_type', '!=', ProviderType::Official->value);

        $result = Db::select($query->toSql(), $query->getBindings());
        return ProviderAssembler::toEntities($result);
    }

    /**
     * @return ProviderEntity[]
     */
    public function getByCategory(Category $category): array
    {
        $builder = $this->createProviderQuery();
        $builder->where('category', $category->value);
        // notrowexceptanyservicequotient，include Official，因fortemplateneed所haveservicequotient

        $result = Db::select($builder->toSql(), $builder->getBindings());
        return ProviderAssembler::toEntities($result);
    }

    public function getByCodeAndCategory(ProviderCode $providerCode, Category $category): ?ProviderEntity
    {
        $builder = $this->createProviderQuery();
        $builder->where('provider_code', $providerCode->value)
            ->where('category', $category->value);

        $result = Db::select($builder->toSql(), $builder->getBindings());
        if (empty($result)) {
            return null;
        }

        return ProviderAssembler::toEntity($result[0]);
    }

    public function getByIdWithoutOrganizationFilter(int $id): ?ProviderEntity
    {
        $builder = $this->createProviderQuery();
        $builder->where('id', $id);

        $result = Db::select($builder->toSql(), $builder->getBindings());
        if (empty($result)) {
            return null;
        }

        return ProviderAssembler::toEntity($result[0]);
    }

    public function getByIdsWithoutOrganizationFilter(array $ids): array
    {
        $builder = $this->createProviderQuery();
        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            return [];
        }

        // 仅pullfinger定 ID，避免alltable扫描
        $builder->whereIn('id', $ids);

        $result = Db::select($builder->toSql(), $builder->getBindings());

        $entities = [];
        foreach ($result as $model) {
            $modelArray = (array) $model;
            $entities[$modelArray['id']] = ProviderAssembler::toEntity($modelArray);
        }

        return $entities;
    }

    /**
     * 准备移except软删相closefeature，temporarythis样写。create带have软deletefilter ProviderModel querybuild器.
     */
    private function createProviderQuery(): Builder
    {
        /* @phpstan-ignore-next-line */
        return ProviderModel::query()->whereNull('deleted_at');
    }
}

<?php

/** @noinspection ALL */

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Repository\Persistence;

use App\Domain\Provider\Entity\ProviderConfigEntity;
use App\Domain\Provider\Entity\ProviderModelEntity;
use App\Domain\Provider\Repository\Persistence\Model\ProviderConfigModel;
use App\Domain\Provider\Repository\Persistence\Model\ProviderModel;
use App\Domain\Provider\Repository\Persistence\Model\ProviderModelModel;
use App\Infrastructure\Core\AbstractEntity;
use App\Infrastructure\Core\AbstractRepository;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Provider\Assembler\ProviderConfigAssembler;
use App\Interfaces\Provider\Assembler\ProviderModelAssembler;
use DateTime;
use Hyperf\Codec\Json;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;

abstract class AbstractModelRepository extends AbstractRepository
{
    protected array $attributeMaps = [
        'creator' => 'created_uid',
        'modifier' => 'updated_uid',
    ];

    public function __construct(
        protected ProviderConfigModel $configModel,
        protected ProviderModelModel $serviceProviderModelsModel,
        protected ProviderModel $serviceProviderModel
    ) {
    }

    /**
     * @return ProviderModelEntity[]
     */
    public function getModelsByIds(array $modelIds): array
    {
        if (empty($modelIds)) {
            return [];
        }
        $query = $this->createProviderModelQuery()->whereIn('id', $modelIds);
        $result = Db::select($query->toSql(), $query->getBindings());
        return ProviderModelAssembler::toEntities($result);
    }

    /**
     * according toconfigurationIDarraygetconfiguration实体list.
     * @return ProviderConfigEntity[]
     */
    public function getConfigsByIds(array $configIds): array
    {
        if (empty($configIds)) {
            return [];
        }
        $query = $this->createConfigQuery()->whereIn('id', $configIds);
        $result = Db::select($query->toSql(), $query->getBindings());
        return ProviderConfigAssembler::toEntities($result);
    }

    /**
     * according to多个service商configurationIDgetmodellist.
     * @param array $configIds service商configurationIDarray
     * @return ProviderModelEntity[]
     */
    public function getModelsByServiceProviderConfigIds(array $configIds): array
    {
        if (empty($configIds)) {
            return [];
        }

        $query = $this->createProviderModelQuery()->whereIn('service_provider_config_id', $configIds);
        $result = Db::select($query->toSql(), $query->getBindings());

        return ProviderModelAssembler::toEntities($result);
    }

    /**
     * initialize实体的ID和time戳（为新create的实体set）.
     * @param mixed $entity
     */
    protected function initializeEntityForCreation($entity, array &$attributes): void
    {
        $now = new DateTime();
        $nowString = $now->format('Y-m-d H:i:s');
        $id = IdGenerator::getSnowId();

        // set实体property
        $entity->setId($id);
        $entity->setCreatedAt($now);
        $entity->setUpdatedAt($now);
        $entity->setDeletedAt(null);

        // setarrayproperty（useatdatabase插入）
        $attributes['id'] = $id;
        $attributes['created_at'] = $nowString;
        $attributes['updated_at'] = $nowString;
        $attributes['deleted_at'] = null;
    }

    /**
     * override getAttributes methodbycorrectprocess复杂field的serialize.
     */
    protected function getFieldAttributes(AbstractEntity $entity): array
    {
        $attributes = [];
        $array = $entity->toArray();
        foreach ($array as $key => $value) {
            // 对复杂fieldconduct特殊process
            if (in_array($key, ['config', 'translate'], true) && (is_array($value) || is_object($value))) {
                $value = Json::encode($value);
            }

            if (array_key_exists($key, $this->attributeMaps)) {
                $attributes[$this->attributeMaps[$key]] = $value;
            } else {
                $attributes[$key] = $value;
            }
        }

        if (empty($attributes['id'])) {
            unset($attributes['id']);
        }
        return $attributes;
    }

    /**
     * 准备移except软删相关feature，temporary这样写。create带have软deletefilter的 ProviderConfigModel querybuild器.
     */
    private function createConfigQuery(): Builder
    {
        return $this->configModel::query()->whereNull('deleted_at');
    }

    /**
     * 准备移except软删相关feature，temporary这样写。create带have软deletefilter的 ProviderModelModel querybuild器.
     */
    private function createProviderModelQuery(): Builder
    {
        return $this->serviceProviderModelsModel::query()->whereNull('deleted_at');
    }
}

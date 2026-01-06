<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence;

use App\Domain\Flow\Entity\DelightfulFlowCacheEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Factory\DelightfulFlowCacheFactory;
use App\Domain\Flow\Repository\Facade\DelightfulFlowCacheRepositoryInterface;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowCacheModel;

class DelightfulFlowCacheRepository extends DelightfulFlowAbstractRepository implements DelightfulFlowCacheRepositoryInterface
{
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowCacheEntity $entity): DelightfulFlowCacheEntity
    {
        if ($entity->shouldCreate()) {
            $entity->prepareForCreation();
            $model = new DelightfulFlowCacheModel();
        } else {
            /** @var DelightfulFlowCacheModel $model */
            $model = DelightfulFlowCacheModel::find($entity->getId());
        }

        $model->fill($this->getAttributes($entity));
        $model->save();

        $entity->setId($model->id);

        return $entity;
    }

    public function findByPrefixAndKey(FlowDataIsolation $dataIsolation, string $cachePrefix, string $cacheKey): ?DelightfulFlowCacheEntity
    {
        $cacheHash = $this->generateCacheHash($cachePrefix, $cacheKey);

        $builder = $this->createBuilder($dataIsolation, DelightfulFlowCacheModel::query());
        /** @var null|DelightfulFlowCacheModel $model */
        $model = $builder->where('cache_hash', $cacheHash)->first();

        if (! $model) {
            return null;
        }

        return DelightfulFlowCacheFactory::modelToEntity($model);
    }

    public function deleteByPrefixAndKey(FlowDataIsolation $dataIsolation, string $cachePrefix, string $cacheKey): bool
    {
        $cacheHash = $this->generateCacheHash($cachePrefix, $cacheKey);

        $builder = $this->createBuilder($dataIsolation, DelightfulFlowCacheModel::query());
        $deleted = $builder->where('cache_hash', $cacheHash)->delete();

        return $deleted > 0;
    }

    public function delete(FlowDataIsolation $dataIsolation, DelightfulFlowCacheEntity $entity): bool
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulFlowCacheModel::query());
        $deleted = $builder->where('id', $entity->getId())->delete();

        return $deleted > 0;
    }

    /**
     * Generate cache hash using the same algorithm as DelightfulFlowCacheEntity.
     *
     * @param string $cachePrefix Cache prefix
     * @param string $cacheKey Cache key
     * @return string MD5 hash of the cache key
     */
    private function generateCacheHash(string $cachePrefix, string $cacheKey): string
    {
        return md5($cachePrefix . '+' . $cacheKey);
    }
}

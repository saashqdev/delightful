<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowCacheEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Repository\Facade\DelightfulFlowCacheRepositoryInterface;

/**
 * Flowcache领域服务
 */
class DelightfulFlowCacheDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowCacheRepositoryInterface $delightfulFlowCacheRepository,
    ) {
    }

    public function saveCache(FlowDataIsolation $dataIsolation, DelightfulFlowCacheEntity $entity): DelightfulFlowCacheEntity
    {
        $entity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $existingEntity = $this->delightfulFlowCacheRepository->findByPrefixAndKey($dataIsolation, $entity->getCachePrefix(), $entity->getCacheKey());

        if ($existingEntity) {
            $existingEntity->refresh($entity->getCacheValue(), $entity->getTtlSeconds());
            $existingEntity->setModifier($dataIsolation->getCurrentUserId());
            return $this->delightfulFlowCacheRepository->save($dataIsolation, $existingEntity);
        }
        $entity->setCreator($dataIsolation->getCurrentUserId());
        $entity->setModifier($dataIsolation->getCurrentUserId());
        $entity->prepareForCreation();
        return $this->delightfulFlowCacheRepository->save($dataIsolation, $entity);
    }

    public function getCache(FlowDataIsolation $dataIsolation, string $cachePrefix, string $cacheKey): ?DelightfulFlowCacheEntity
    {
        $entity = $this->delightfulFlowCacheRepository->findByPrefixAndKey($dataIsolation, $cachePrefix, $cacheKey);

        if ($entity && $entity->isExpired()) {
            $this->delightfulFlowCacheRepository->delete($dataIsolation, $entity);
            return null;
        }

        return $entity;
    }

    public function deleteCache(FlowDataIsolation $dataIsolation, string $cachePrefix, string $cacheKey): bool
    {
        return $this->delightfulFlowCacheRepository->deleteByPrefixAndKey($dataIsolation, $cachePrefix, $cacheKey);
    }
}

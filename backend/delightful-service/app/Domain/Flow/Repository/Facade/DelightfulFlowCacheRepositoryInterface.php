<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowCacheEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;

/**
 * Flowcache仓储接口.
 */
interface DelightfulFlowCacheRepositoryInterface
{
    /**
     * Save cache entity (create or update based on entity state).
     */
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowCacheEntity $entity): DelightfulFlowCacheEntity;

    /**
     * Find cache by prefix and key (using hash internally).
     */
    public function findByPrefixAndKey(FlowDataIsolation $dataIsolation, string $cachePrefix, string $cacheKey): ?DelightfulFlowCacheEntity;

    /**
     * Delete cache by prefix and key (using hash internally).
     */
    public function deleteByPrefixAndKey(FlowDataIsolation $dataIsolation, string $cachePrefix, string $cacheKey): bool;

    public function delete(FlowDataIsolation $dataIsolation, DelightfulFlowCacheEntity $entity): bool;
}

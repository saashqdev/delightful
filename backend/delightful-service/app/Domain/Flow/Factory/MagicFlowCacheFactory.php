<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Factory;

use App\Domain\Flow\Entity\DelightfulFlowCacheEntity;
use App\Domain\Flow\Repository\Persistence\Model\DelightfulFlowCacheModel;

class DelightfulFlowCacheFactory
{
    /**
     * Convert model to entity.
     */
    public static function modelToEntity(DelightfulFlowCacheModel $model): DelightfulFlowCacheEntity
    {
        $entity = new DelightfulFlowCacheEntity();
        $entity->setId($model->id);
        $entity->setCacheHash($model->cache_hash);
        $entity->setCachePrefix($model->cache_prefix);
        $entity->setCacheKey($model->cache_key);
        $entity->setScopeTag($model->scope_tag);
        $entity->setCacheValue($model->cache_value);
        $entity->setTtlSeconds($model->ttl_seconds);
        $entity->setExpiresAt($model->expires_at);
        $entity->setOrganizationCode($model->organization_code);
        $entity->setCreator($model->created_uid ?? '');
        $entity->setCreatedAt($model->created_at);
        $entity->setModifier($model->updated_uid ?? '');
        $entity->setUpdatedAt($model->updated_at);

        return $entity;
    }
}

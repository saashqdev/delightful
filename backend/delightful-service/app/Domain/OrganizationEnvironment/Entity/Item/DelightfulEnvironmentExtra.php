<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Entity\Item;

use App\Domain\Chat\Entity\AbstractEntity;

class DelightfulEnvironmentExtra extends AbstractEntity
{
    // 预publish和生产can看做是one环境，所by这within存一downassociate的环境 ids
    protected array $relationEnvIds;

    public function getRelationEnvIds(): array
    {
        // 预publish和生产can看做是one环境，所by这within存一downassociate的环境 ids
        return $this->relationEnvIds;
    }

    public function setRelationEnvIds(array $relationEnvIds): void
    {
        $this->relationEnvIds = $relationEnvIds;
    }
}

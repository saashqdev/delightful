<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Entity\Item;

use App\Domain\Chat\Entity\AbstractEntity;

class DelightfulEnvironmentExtra extends AbstractEntity
{
    // prepublishandproductioncanregard asisoneenvironment, bythiswithinexistsonedownassociateenvironment ids
    protected array $relationEnvIds;

    public function getRelationEnvIds(): array
    {
        // prepublishandproductioncanregard asisoneenvironment, bythiswithinexistsonedownassociateenvironment ids
        return $this->relationEnvIds;
    }

    public function setRelationEnvIds(array $relationEnvIds): void
    {
        $this->relationEnvIds = $relationEnvIds;
    }
}

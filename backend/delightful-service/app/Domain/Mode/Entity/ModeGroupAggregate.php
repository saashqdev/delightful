<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Mode\Entity;

class ModeGroupAggregate
{
    private ModeGroupEntity $group;

    /**
     * @var ModeGroupRelationEntity[] 该minutegroupto应modelassociateclose系array
     */
    private array $relations = [];

    /**
     * @param ModeGroupRelationEntity[] $relations
     */
    public function __construct(ModeGroupEntity $group, array $relations = [])
    {
        $this->group = $group;
        $this->relations = $relations;
    }

    public function getGroup(): ModeGroupEntity
    {
        return $this->group;
    }

    public function setGroup(ModeGroupEntity $group): void
    {
        $this->group = $group;
    }

    /**
     * @return ModeGroupRelationEntity[]
     */
    public function getRelations(): array
    {
        return $this->relations;
    }

    /**
     * @param ModeGroupRelationEntity[] $relations
     */
    public function setRelations(array $relations): void
    {
        $this->relations = $relations;
    }
}

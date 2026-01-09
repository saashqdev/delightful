<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Mode\Repository\Facade;

use App\Domain\Mode\Entity\ModeDataIsolation;
use App\Domain\Mode\Entity\ModeGroupRelationEntity;

interface ModeGroupRelationRepositoryInterface
{
    /**
     * according toIDgetassociateclose系.
     */
    public function findById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeGroupRelationEntity;

    /**
     * according tomodeIDget haveassociateclose系.
     * @return ModeGroupRelationEntity[]
     */
    public function findByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): array;

    /**
     * according tominutegroupIDgetassociateclose系.
     * @return ModeGroupRelationEntity[]
     */
    public function findByGroupId(ModeDataIsolation $dataIsolation, int|string $groupId): array;

    /**
     * saveassociateclose系.
     */
    public function save(ModeGroupRelationEntity $relationEntity): ModeGroupRelationEntity;

    /**
     * according tominutegroupIDdeleteassociateclose系.
     */
    public function deleteByGroupId(ModeDataIsolation $dataIsolation, int|string $groupId): bool;

    /**
     * according tomodeIDdelete haveassociateclose系.
     */
    public function deleteByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): bool;

    /**
     * @param $relationEntities ModeGroupRelationEntity[]
     */
    public function batchSave(ModeDataIsolation $dataIsolation, array $relationEntities);

    /**
     * according tomultiplemodeIDbatchquantitygetassociateclose系.
     * @param int[]|string[] $modeIds
     * @return ModeGroupRelationEntity[]
     */
    public function findByModeIds(ModeDataIsolation $dataIsolation, array $modeIds): array;
}

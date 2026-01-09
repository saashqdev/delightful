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
     * according toIDgetassociateclosesystem.
     */
    public function findById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeGroupRelationEntity;

    /**
     * according tomodeIDget haveassociateclosesystem.
     * @return ModeGroupRelationEntity[]
     */
    public function findByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): array;

    /**
     * according tominutegroupIDgetassociateclosesystem.
     * @return ModeGroupRelationEntity[]
     */
    public function findByGroupId(ModeDataIsolation $dataIsolation, int|string $groupId): array;

    /**
     * saveassociateclosesystem.
     */
    public function save(ModeGroupRelationEntity $relationEntity): ModeGroupRelationEntity;

    /**
     * according tominutegroupIDdeleteassociateclosesystem.
     */
    public function deleteByGroupId(ModeDataIsolation $dataIsolation, int|string $groupId): bool;

    /**
     * according tomodeIDdelete haveassociateclosesystem.
     */
    public function deleteByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): bool;

    /**
     * @param $relationEntities ModeGroupRelationEntity[]
     */
    public function batchSave(ModeDataIsolation $dataIsolation, array $relationEntities);

    /**
     * according tomultiplemodeIDbatchquantitygetassociateclosesystem.
     * @param int[]|string[] $modeIds
     * @return ModeGroupRelationEntity[]
     */
    public function findByModeIds(ModeDataIsolation $dataIsolation, array $modeIds): array;
}

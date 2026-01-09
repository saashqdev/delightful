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
     * according toIDget关联关系.
     */
    public function findById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeGroupRelationEntity;

    /**
     * according tomodeIDget所有关联关系.
     * @return ModeGroupRelationEntity[]
     */
    public function findByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): array;

    /**
     * according to分组IDget关联关系.
     * @return ModeGroupRelationEntity[]
     */
    public function findByGroupId(ModeDataIsolation $dataIsolation, int|string $groupId): array;

    /**
     * 保存关联关系.
     */
    public function save(ModeGroupRelationEntity $relationEntity): ModeGroupRelationEntity;

    /**
     * according to分组IDdelete关联关系.
     */
    public function deleteByGroupId(ModeDataIsolation $dataIsolation, int|string $groupId): bool;

    /**
     * according tomodeIDdelete所有关联关系.
     */
    public function deleteByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): bool;

    /**
     * @param $relationEntities ModeGroupRelationEntity[]
     */
    public function batchSave(ModeDataIsolation $dataIsolation, array $relationEntities);

    /**
     * according to多个modeID批量get关联关系.
     * @param int[]|string[] $modeIds
     * @return ModeGroupRelationEntity[]
     */
    public function findByModeIds(ModeDataIsolation $dataIsolation, array $modeIds): array;
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Mode\Repository\Facade;

use App\Domain\Mode\Entity\ModeDataIsolation;
use App\Domain\Mode\Entity\ModeGroupEntity;

interface ModeGroupRepositoryInterface
{
    /**
     * according toIDget分组.
     */
    public function findById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeGroupEntity;

    /**
     * according tomodeIDget分组列表.
     * @return ModeGroupEntity[]
     */
    public function findByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): array;

    /**
     * 保存分组.
     */
    public function save(ModeDataIsolation $dataIsolation, ModeGroupEntity $groupEntity): ModeGroupEntity;

    /**
     * update分组.
     */
    public function update(ModeDataIsolation $dataIsolation, ModeGroupEntity $groupEntity): ModeGroupEntity;

    /**
     * getmode下启用的分组列表.
     * @return ModeGroupEntity[]
     */
    public function findEnabledByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): array;

    /**
     * delete分组.
     */
    public function delete(ModeDataIsolation $dataIsolation, int|string $id): bool;

    /**
     * according tomodeIDdelete所有分组.
     */
    public function deleteByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): bool;

    /**
     * @param $groupEntities ModeGroupEntity[]
     */
    public function batchSave(ModeDataIsolation $dataIsolation, array $groupEntities);

    /**
     * according to多个modeID批量get分组列表.
     * @param int[]|string[] $modeIds
     * @return ModeGroupEntity[]
     */
    public function findByModeIds(ModeDataIsolation $dataIsolation, array $modeIds): array;
}

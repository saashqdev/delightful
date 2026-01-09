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
     * according toIDgetminutegroup.
     */
    public function findById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeGroupEntity;

    /**
     * according tomodeIDgetminutegroupcolumn表.
     * @return ModeGroupEntity[]
     */
    public function findByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): array;

    /**
     * saveminutegroup.
     */
    public function save(ModeDataIsolation $dataIsolation, ModeGroupEntity $groupEntity): ModeGroupEntity;

    /**
     * updateminutegroup.
     */
    public function update(ModeDataIsolation $dataIsolation, ModeGroupEntity $groupEntity): ModeGroupEntity;

    /**
     * getmodedownenable的minutegroupcolumn表.
     * @return ModeGroupEntity[]
     */
    public function findEnabledByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): array;

    /**
     * deleteminutegroup.
     */
    public function delete(ModeDataIsolation $dataIsolation, int|string $id): bool;

    /**
     * according tomodeIDdelete所haveminutegroup.
     */
    public function deleteByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): bool;

    /**
     * @param $groupEntities ModeGroupEntity[]
     */
    public function batchSave(ModeDataIsolation $dataIsolation, array $groupEntities);

    /**
     * according to多modeID批quantitygetminutegroupcolumn表.
     * @param int[]|string[] $modeIds
     * @return ModeGroupEntity[]
     */
    public function findByModeIds(ModeDataIsolation $dataIsolation, array $modeIds): array;
}

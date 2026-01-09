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
     * according toIDget分group.
     */
    public function findById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeGroupEntity;

    /**
     * according tomodeIDget分group列表.
     * @return ModeGroupEntity[]
     */
    public function findByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): array;

    /**
     * save分group.
     */
    public function save(ModeDataIsolation $dataIsolation, ModeGroupEntity $groupEntity): ModeGroupEntity;

    /**
     * update分group.
     */
    public function update(ModeDataIsolation $dataIsolation, ModeGroupEntity $groupEntity): ModeGroupEntity;

    /**
     * getmode下enable的分group列表.
     * @return ModeGroupEntity[]
     */
    public function findEnabledByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): array;

    /**
     * delete分group.
     */
    public function delete(ModeDataIsolation $dataIsolation, int|string $id): bool;

    /**
     * according tomodeIDdelete所have分group.
     */
    public function deleteByModeId(ModeDataIsolation $dataIsolation, int|string $modeId): bool;

    /**
     * @param $groupEntities ModeGroupEntity[]
     */
    public function batchSave(ModeDataIsolation $dataIsolation, array $groupEntities);

    /**
     * according to多个modeID批量get分group列表.
     * @param int[]|string[] $modeIds
     * @return ModeGroupEntity[]
     */
    public function findByModeIds(ModeDataIsolation $dataIsolation, array $modeIds): array;
}

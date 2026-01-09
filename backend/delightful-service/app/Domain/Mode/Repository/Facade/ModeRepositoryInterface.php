<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Mode\Repository\Facade;

use App\Domain\Mode\Entity\ModeDataIsolation;
use App\Domain\Mode\Entity\ModeEntity;
use App\Domain\Mode\Entity\ValueQuery\ModeQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface ModeRepositoryInterface
{
    /**
     * 根据IDgetmode.
     */
    public function findById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeEntity;

    /**
     * 根据标识符getmode.
     */
    public function findByIdentifier(ModeDataIsolation $dataIsolation, string $identifier): ?ModeEntity;

    /**
     * get默认mode.
     */
    public function findDefaultMode(ModeDataIsolation $dataIsolation): ?ModeEntity;

    /**
     * @return array{total: int, list: ModeEntity[]}
     */
    public function queries(ModeDataIsolation $dataIsolation, ModeQuery $query, Page $page): array;

    /**
     * 保存mode.
     */
    public function save(ModeDataIsolation $dataIsolation, ModeEntity $modeEntity): ModeEntity;

    /**
     * deletemode.
     */
    public function delete(ModeDataIsolation $dataIsolation, string $id): bool;

    /**
     * 检查标识符是否唯一
     */
    public function isIdentifierUnique(ModeDataIsolation $dataIsolation, string $identifier, ?string $excludeId = null): bool;

    /**
     * get所有启用的mode.
     */
    public function findEnabledModes(ModeDataIsolation $dataIsolation): array;

    /**
     * 根据跟随modeIDgetmode列表.
     */
    public function findByFollowModeId(ModeDataIsolation $dataIsolation, string $followModeId): array;

    /**
     * updatemodestatus
     */
    public function updateStatus(ModeDataIsolation $dataIsolation, string $id, bool $status): bool;
}

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
     * 根据IDget模式.
     */
    public function findById(ModeDataIsolation $dataIsolation, int|string $id): ?ModeEntity;

    /**
     * 根据标识符get模式.
     */
    public function findByIdentifier(ModeDataIsolation $dataIsolation, string $identifier): ?ModeEntity;

    /**
     * get默认模式.
     */
    public function findDefaultMode(ModeDataIsolation $dataIsolation): ?ModeEntity;

    /**
     * @return array{total: int, list: ModeEntity[]}
     */
    public function queries(ModeDataIsolation $dataIsolation, ModeQuery $query, Page $page): array;

    /**
     * 保存模式.
     */
    public function save(ModeDataIsolation $dataIsolation, ModeEntity $modeEntity): ModeEntity;

    /**
     * delete模式.
     */
    public function delete(ModeDataIsolation $dataIsolation, string $id): bool;

    /**
     * 检查标识符是否唯一
     */
    public function isIdentifierUnique(ModeDataIsolation $dataIsolation, string $identifier, ?string $excludeId = null): bool;

    /**
     * get所有启用的模式.
     */
    public function findEnabledModes(ModeDataIsolation $dataIsolation): array;

    /**
     * 根据跟随模式IDget模式列表.
     */
    public function findByFollowModeId(ModeDataIsolation $dataIsolation, string $followModeId): array;

    /**
     * update模式status
     */
    public function updateStatus(ModeDataIsolation $dataIsolation, string $id, bool $status): bool;
}

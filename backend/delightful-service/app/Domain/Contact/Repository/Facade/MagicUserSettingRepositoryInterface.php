<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Facade;

use App\Domain\Contact\Entity\DelightfulUserSettingEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\Query\DelightfulUserSettingQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulUserSettingRepositoryInterface
{
    public function save(DataIsolation $dataIsolation, DelightfulUserSettingEntity $magicUserSettingEntity): DelightfulUserSettingEntity;

    public function get(DataIsolation $dataIsolation, string $key): ?DelightfulUserSettingEntity;

    // 通过 magicId 维度存取
    public function saveByDelightfulId(string $magicId, DelightfulUserSettingEntity $magicUserSettingEntity): DelightfulUserSettingEntity;

    public function getByDelightfulId(string $magicId, string $key): ?DelightfulUserSettingEntity;

    /**
     * 全局配置：organization_code/user_id/magic_id 全部为 null.
     */
    public function getGlobal(string $key): ?DelightfulUserSettingEntity;

    /**
     * 保存全局配置。
     */
    public function saveGlobal(DelightfulUserSettingEntity $magicUserSettingEntity): DelightfulUserSettingEntity;

    /**
     * @return array{total: int, list: array<DelightfulUserSettingEntity>}
     */
    public function queries(DataIsolation $dataIsolation, DelightfulUserSettingQuery $query, Page $page): array;
}

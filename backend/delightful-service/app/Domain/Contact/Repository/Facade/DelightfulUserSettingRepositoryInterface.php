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
    public function save(DataIsolation $dataIsolation, DelightfulUserSettingEntity $delightfulUserSettingEntity): DelightfulUserSettingEntity;

    public function get(DataIsolation $dataIsolation, string $key): ?DelightfulUserSettingEntity;

    // pass delightfulId 维degreeaccess
    public function saveByDelightfulId(string $delightfulId, DelightfulUserSettingEntity $delightfulUserSettingEntity): DelightfulUserSettingEntity;

    public function getByDelightfulId(string $delightfulId, string $key): ?DelightfulUserSettingEntity;

    /**
     * alllocalconfiguration:organization_code/user_id/delightful_id alldepartmentfor null.
     */
    public function getGlobal(string $key): ?DelightfulUserSettingEntity;

    /**
     * savealllocalconfiguration.
     */
    public function saveGlobal(DelightfulUserSettingEntity $delightfulUserSettingEntity): DelightfulUserSettingEntity;

    /**
     * @return array{total: int, list: array<DelightfulUserSettingEntity>}
     */
    public function queries(DataIsolation $dataIsolation, DelightfulUserSettingQuery $query, Page $page): array;
}

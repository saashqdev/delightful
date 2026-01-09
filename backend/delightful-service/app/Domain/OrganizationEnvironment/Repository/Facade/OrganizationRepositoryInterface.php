<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Repository\Facade;

use App\Domain\OrganizationEnvironment\Entity\OrganizationEntity;
use App\Infrastructure\Core\ValueObject\Page;

/**
 * organization仓库接口.
 */
interface OrganizationRepositoryInterface
{
    /**
     * 保存organization.
     */
    public function save(OrganizationEntity $organizationEntity): OrganizationEntity;

    /**
     * 根据IDgetorganization.
     */
    public function getById(int $id): ?OrganizationEntity;

    /**
     * 根据编码getorganization.
     */
    public function getByCode(string $code): ?OrganizationEntity;

    /**
     * 根据编码列表批量getorganization.
     * @param string[] $codes
     * @return OrganizationEntity[]
     */
    public function getByCodes(array $codes): array;

    /**
     * 根据名称getorganization.
     */
    public function getByName(string $name): ?OrganizationEntity;

    /**
     * queryorganization列表.
     * @return array{total: int, list: OrganizationEntity[]}
     */
    public function queries(Page $page, ?array $filters = null): array;

    /**
     * deleteorganization.
     */
    public function delete(OrganizationEntity $organizationEntity): void;

    /**
     * 检查编码是否已存在.
     */
    public function existsByCode(string $code, ?int $excludeId = null): bool;
}

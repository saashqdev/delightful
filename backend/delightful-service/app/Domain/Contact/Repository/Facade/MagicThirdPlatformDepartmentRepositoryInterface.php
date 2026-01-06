<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Facade;

use App\Domain\Contact\Entity\DelightfulThirdPlatformDepartmentEntity;
use App\Domain\Contact\Entity\ValueObject\PlatformType;
use JetBrains\PhpStorm\ArrayShape;

/**
 * @deprecated
 */
interface DelightfulThirdPlatformDepartmentRepositoryInterface
{
    public function getDepartmentById(string $thirdDepartmentId, string $organizationCode, PlatformType $thirdPlatformType): ?DelightfulThirdPlatformDepartmentEntity;

    /**
     * @return DelightfulThirdPlatformDepartmentEntity[]
     */
    public function getDepartmentByIds(array $departmentIds, string $organizationCode, bool $keyById = false): array;

    /**
     * @return DelightfulThirdPlatformDepartmentEntity[]
     */
    public function getSubDepartmentsById(string $departmentId, string $organizationCode, int $size, int $offset): array;

    /**
     * 获取某一层级的部门.
     * @return DelightfulThirdPlatformDepartmentEntity[]
     */
    public function getSubDepartmentsByLevel(int $currentDepartmentLevel, string $organizationCode, int $depth, int $size, int $offset): array;

    // 给定的部门id是否有下级部门
    #[ArrayShape([
        'third_parent_department_id' => 'string',
    ])]
    public function hasChildDepartment(array $departmentIds, string $organizationCode): array;

    public function getDepartmentByParentId(string $departmentId, string $organizationCode): ?DelightfulThirdPlatformDepartmentEntity;

    /**
     * 获取组织的所有部门.
     * @return DelightfulThirdPlatformDepartmentEntity[]
     */
    public function getOrganizationDepartments(string $organizationCode, array $fields = ['*']): array;
}

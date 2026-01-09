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
     * getsome一layerleveldepartment.
     * @return DelightfulThirdPlatformDepartmentEntity[]
     */
    public function getSubDepartmentsByLevel(int $currentDepartmentLevel, string $organizationCode, int $depth, int $size, int $offset): array;

    // give定departmentidwhetherhavedownleveldepartment
    #[ArrayShape([
        'third_parent_department_id' => 'string',
    ])]
    public function hasChildDepartment(array $departmentIds, string $organizationCode): array;

    public function getDepartmentByParentId(string $departmentId, string $organizationCode): ?DelightfulThirdPlatformDepartmentEntity;

    /**
     * getorganization所havedepartment.
     * @return DelightfulThirdPlatformDepartmentEntity[]
     */
    public function getOrganizationDepartments(string $organizationCode, array $fields = ['*']): array;
}

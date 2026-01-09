<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Facade;

use App\Domain\Chat\DTO\PageResponseDTO\DepartmentsPageResponseDTO;
use App\Domain\Contact\Entity\DelightfulDepartmentEntity;
use App\Domain\Contact\Entity\ValueObject\DepartmentOption;
use JetBrains\PhpStorm\ArrayShape;

interface DelightfulDepartmentRepositoryInterface
{
    public function getDepartmentById(string $departmentId, string $organizationCode): ?DelightfulDepartmentEntity;

    // get父departmentdownone子department
    public function getDepartmentByParentId(string $departmentId, string $organizationCode): ?DelightfulDepartmentEntity;

    /**
     * @return DelightfulDepartmentEntity[]
     */
    public function getDepartmentsByIds(array $departmentIds, string $organizationCode, bool $keyById = false): array;

    /**
     * @return DelightfulDepartmentEntity[]
     */
    public function getDepartmentsByIdsInDelightful(array $departmentIds, bool $keyById = false): array;

    /**
     * batchquantitygetdepartmentdownnleveldepartment.
     */
    public function getSubDepartmentsById(string $departmentId, string $organizationCode, int $size, int $offset): DepartmentsPageResponseDTO;

    /**
     * getsomeonelayerleveldepartment.
     */
    public function getSubDepartmentsByLevel(int $level, string $organizationCode, int $depth, int $size, int $offset): DepartmentsPageResponseDTO;

    // give定departmentidwhetherhavedownleveldepartment
    #[ArrayShape([
        [
            'parent_department_id' => 'string',
        ],
    ])]
    public function hasChildDepartment(array $departmentIds, string $organizationCode): array;

    /**
     * @return DelightfulDepartmentEntity[]
     */
    public function searchDepartments(string $departmentName, string $organizationCode, string $pageToken = '', ?int $pageSize = null): array;

    /**
     * getorganization所havedepartment.
     * @return DelightfulDepartmentEntity[]
     */
    public function getOrganizationDepartments(string $organizationCode, array $fields = ['*'], bool $keyById = false): array;

    /**
     * increasedepartmentinstruction书.
     */
    public function addDepartmentDocument(string $departmentId, string $documentId): void;

    /**
     * getdepartment所have子departmentmembertotal.
     */
    public function getSelfAndChildrenEmployeeSum(DelightfulDepartmentEntity $delightfulDepartmentEntity): int;

    /**
     * @param DelightfulDepartmentEntity[] $delightfulDepartmentsDTO
     * @return DelightfulDepartmentEntity[]
     */
    public function createDepartments(array $delightfulDepartmentsDTO): array;

    public function updateDepartment(string $departmentId, array $data, string $organizationCode): int;

    public function updateDepartmentsOptionByIds(array $departmentIds, ?DepartmentOption $departmentOption = null): int;

    /**
     * according todepartmentIDbatchquantitydeletedepartment（逻辑delete，set deleted_at field）。
     */
    public function deleteDepartmentsByIds(array $departmentIds, string $organizationCode): int;

    /**
     * getorganizationrootdepartmentID.
     */
    public function getDepartmentRootId(string $organizationCode): ?string;

    /**
     * batchquantityget多organizationrootdepartmentinfo.
     * @param array $organizationCodes organizationcodearray
     * @return DelightfulDepartmentEntity[] rootdepartment实bodyarray
     */
    public function getOrganizationsRootDepartment(array $organizationCodes): array;

    /**
     * Get all organizations root departments with pagination support.
     * @param int $page Page number
     * @param int $pageSize Page size
     * @param string $organizationName Organization name for fuzzy search (optional)
     * @param array $organizationCodes Organization codes for exact match filter (optional)
     * @return array Array containing total and list
     */
    public function getAllOrganizationsRootDepartments(int $page = 1, int $pageSize = 20, string $organizationName = '', array $organizationCodes = []): array;
}

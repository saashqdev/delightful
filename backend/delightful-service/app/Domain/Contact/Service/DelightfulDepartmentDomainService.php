<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Service;

use App\Domain\Chat\DTO\PageResponseDTO\DepartmentsPageResponseDTO;
use App\Domain\Contact\Entity\DelightfulDepartmentEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\DepartmentOption;
use App\Domain\Contact\Entity\ValueObject\PlatformType;

class DelightfulDepartmentDomainService extends AbstractContactDomainService
{
    public function getDepartmentById(DataIsolation $dataIsolation, string $departmentId): ?DelightfulDepartmentEntity
    {
        // -1 table示rootdepartmentinfo.
        return $this->departmentRepository->getDepartmentById($departmentId, $dataIsolation->getCurrentOrganizationCode());
    }

    /**
     * @return DelightfulDepartmentEntity[]
     */
    public function getDepartmentByIds(DataIsolation $dataIsolation, array $departmentIds, bool $keyById = false): array
    {
        return $this->departmentRepository->getDepartmentsByIds($departmentIds, $dataIsolation->getCurrentOrganizationCode(), $keyById);
    }

    /**
     * @return array<string, DelightfulDepartmentEntity[]>
     */
    public function getDepartmentFullPathByIds(DataIsolation $dataIsolation, array $departmentIds): array
    {
        // todepartmentidsconductgo重
        $departmentIds = array_values(array_unique($departmentIds));
        // getorganization所havedepartment
        $departments = $this->departmentRepository->getOrganizationDepartments($dataIsolation->getCurrentOrganizationCode(), keyById: true);
        // group装departmentinfo
        $res = [];
        foreach ($departmentIds as $departmentId) {
            $curDepartmentId = $departmentId;
            while (true) {
                $department = $departments[$curDepartmentId] ?? null;
                if ($department === null) {
                    break;
                }
                $res[$departmentId][] = $department;
                $curDepartmentId = $department->getParentDepartmentId();
                if ($department->getLevel() === 0) {
                    break;
                }
            }
            isset($res[$departmentId]) && $res[$departmentId] = array_reverse($res[$departmentId]);
        }
        return $res;
    }

    /**
     * @return DelightfulDepartmentEntity[]
     */
    public function getDepartmentByIdsInDelightful(array $departmentIds, bool $keyById = false): array
    {
        return $this->departmentRepository->getDepartmentsByIdsInDelightful($departmentIds, $keyById);
    }

    public function updateDepartmentsOptionByIds(array $departmentIds, ?DepartmentOption $departmentOption = null): int
    {
        if (empty($departmentIds)) {
            return 0;
        }
        return $this->departmentRepository->updateDepartmentsOptionByIds($departmentIds, $departmentOption);
    }

    public function getSubDepartmentsByLevel(DataIsolation $dataIsolation, int $level, int $depth, int $size, int $offset): DepartmentsPageResponseDTO
    {
        $orgCode = $dataIsolation->getCurrentOrganizationCode();
        $departmentsPageResponseDTO = $this->departmentRepository->getSubDepartmentsByLevel($level, $orgCode, $depth, $size, $offset);
        $departments = $departmentsPageResponseDTO->getItems();
        // certaindownleveldepartmentwhetheralsohave子department
        $items = $this->getDepartmentsHasChild($departments, $orgCode);
        $departmentsPageResponseDTO->setItems($items);
        return $departmentsPageResponseDTO;
    }

    public function getSubDepartmentsById(DataIsolation $dataIsolation, string $departmentId, int $size, int $offset): DepartmentsPageResponseDTO
    {
        $orgCode = $dataIsolation->getCurrentOrganizationCode();
        $departmentsPageResponseDTO = $this->departmentRepository->getSubDepartmentsById($departmentId, $orgCode, $size, $offset);
        $departments = $departmentsPageResponseDTO->getItems();
        // certaindownleveldepartmentwhetheralsohave子department
        $items = $this->getDepartmentsHasChild($departments, $orgCode);
        $departmentsPageResponseDTO->setItems($items);
        return $departmentsPageResponseDTO;
    }

    /**
     * @param DelightfulDepartmentEntity[] $departments
     * @return DelightfulDepartmentEntity[]
     */
    public function getDepartmentsHasChild(array $departments, string $organizationCode): array
    {
        $departmentIds = array_column($departments, 'department_id');
        $childDepartments = $this->departmentRepository->hasChildDepartment($departmentIds, $organizationCode);
        $childDepartments = array_column($childDepartments, null, 'parent_department_id');
        $departmentsHasChild = [];
        foreach ($departments as $department) {
            $hasChild = isset($childDepartments[$department->getDepartmentId()]);
            $department->setHasChild($hasChild);
            // 移exceptnotneedfield
            $departmentsHasChild[] = $department;
        }
        return $departmentsHasChild;
    }

    /**
     * @return DelightfulDepartmentEntity[]
     */
    public function searchDepartment(DataIsolation $dataIsolation, string $departmentName): array
    {
        $orgCode = $dataIsolation->getCurrentOrganizationCode();
        $departments = $this->departmentRepository->searchDepartments($departmentName, $orgCode);
        return $this->getDepartmentsHasChild($departments, $orgCode);
    }

    /**
     * @return DelightfulDepartmentEntity[]
     */
    public function searchDepartmentForPage(DataIsolation $dataIsolation, string $departmentName, string $pageToken = '', int $pageSize = 50): array
    {
        $orgCode = $dataIsolation->getCurrentOrganizationCode();
        $departments = $this->departmentRepository->searchDepartments($departmentName, $orgCode, $pageToken, $pageSize);
        return $this->getDepartmentsHasChild($departments, $orgCode);
    }

    /**
     * batchquantitygetdepartment所have子department.
     * @return DelightfulDepartmentEntity[]
     */
    public function getAllChildrenByDepartmentIds(array $departmentIds, DataIsolation $dataIsolation): array
    {
        $departments = $this->departmentRepository->getOrganizationDepartments(
            $dataIsolation->getCurrentOrganizationCode(),
            ['department_id', 'parent_department_id', 'name', 'path']
        );

        $departmentsChildrenEntities = $this->getChildrenByDepartmentIds($departments, $departmentIds);
        // merge && go重
        $departmentIds = array_column(array_merge(...$departmentsChildrenEntities), 'department_id');
        return array_values(array_unique($departmentIds));
    }

    public function addDepartmentDocument(string $departmentId, string $documentId): void
    {
        $this->departmentRepository->addDepartmentDocument($departmentId, $documentId);
    }

    public function getDepartmentChildrenEmployeeSum(DelightfulDepartmentEntity $departmentEntity): int
    {
        return $this->departmentRepository->getSelfAndChildrenEmployeeSum($departmentEntity);
    }

    /**
     * rootdepartmentbeabstractfor -1,所bythiswithinneedconvertforactualrootdepartment id.
     */
    public function getDepartmentRootId(DataIsolation $dataIsolation): string
    {
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();
        // getorganization所属platformtype
        $platformType = $this->organizationsPlatformRepository->getOrganizationPlatformType($organizationCode);
        if ($platformType === PlatformType::Delightful) {
            // getrootdepartmentID
            return $this->departmentRepository->getDepartmentRootId($organizationCode);
        }

        // according toorganizationencodingandplatformtypegetrootdepartmentID
        return $this->thirdPlatformIdMappingRepository->getDepartmentRootId($organizationCode, $platformType);
    }

    /**
     * batchquantityget多organizationrootdepartmentinfo.
     * @param array $organizationCodes organizationcodearray
     * @return array<string,DelightfulDepartmentEntity> byorganizationcodeforkey,rootdepartment实bodyforvalueassociatearray
     */
    public function getOrganizationsRootDepartment(array $organizationCodes): array
    {
        $rootDepartments = $this->departmentRepository->getOrganizationsRootDepartment($organizationCodes);

        // checkwhetherhaverootdepartmentdata
        if (empty($rootDepartments)) {
            return [];
        }

        // processdataformat,byorganizationcodeforkey,rootdepartment实bodyforvalue
        $result = [];
        foreach ($rootDepartments as $department) {
            $result[$department->getOrganizationCode()] = $department;
        }

        return $result;
    }

    /**
     * Get all organizations root departments with pagination support.
     * @param int $page Page number
     * @param int $pageSize Page size
     * @param string $organizationName Organization name for fuzzy search (optional)
     * @param array $organizationCodes Organization codes for exact match filter (optional)
     * @return array Array containing total and list
     */
    public function getAllOrganizationsRootDepartments(int $page = 1, int $pageSize = 20, string $organizationName = '', array $organizationCodes = []): array
    {
        return $this->departmentRepository->getAllOrganizationsRootDepartments($page, $pageSize, $organizationName, $organizationCodes);
    }

    public function getOrganizationNameByCode(string $organizationCode): string
    {
        $entity = $this->departmentRepository->getDepartmentById('-1', $organizationCode);
        if (empty($entity)) {
            return '';
        }
        return $entity->getName();
    }

    /**
     * Batch get organization names by organization codes.
     *
     * @param array $organizationCodes Array of organization codes
     * @return array Array with structure [code => name]
     */
    public function batchGetOrganizationNamesByCodes(array $organizationCodes): array
    {
        if (empty($organizationCodes)) {
            return [];
        }

        $result = [];
        foreach ($organizationCodes as $organizationCode) {
            $entity = $this->departmentRepository->getDepartmentById('-1', $organizationCode);
            $result[$organizationCode] = $entity ? $entity->getName() : '';
        }

        return $result;
    }

    /**
     * getdepartment所have子department.
     * @param DelightfulDepartmentEntity[] $allDepartments
     */
    protected function getChildrenByDepartmentIds(array $allDepartments, array $departmentIds): array
    {
        $childrenDepartments = [];
        foreach ($allDepartments as $department) {
            foreach ($departmentIds as $departmentId) {
                if (str_contains($department->getPath(), $departmentId)) {
                    $childrenDepartments[$departmentId][] = $department;
                }
            }
        }
        return $childrenDepartments;
    }
}

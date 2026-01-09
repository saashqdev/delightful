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
        // -1 table示根departmentinfo.
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
        // 对departmentids进行去重
        $departmentIds = array_values(array_unique($departmentIds));
        // getorganization所有department
        $departments = $this->departmentRepository->getOrganizationDepartments($dataIsolation->getCurrentOrganizationCode(), keyById: true);
        // 组装departmentinfo
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
        // 确定下级department是否还有子department
        $items = $this->getDepartmentsHasChild($departments, $orgCode);
        $departmentsPageResponseDTO->setItems($items);
        return $departmentsPageResponseDTO;
    }

    public function getSubDepartmentsById(DataIsolation $dataIsolation, string $departmentId, int $size, int $offset): DepartmentsPageResponseDTO
    {
        $orgCode = $dataIsolation->getCurrentOrganizationCode();
        $departmentsPageResponseDTO = $this->departmentRepository->getSubDepartmentsById($departmentId, $orgCode, $size, $offset);
        $departments = $departmentsPageResponseDTO->getItems();
        // 确定下级department是否还有子department
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
            // 移除不need的field
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
     * 批量getdepartment的所有子department.
     * @return DelightfulDepartmentEntity[]
     */
    public function getAllChildrenByDepartmentIds(array $departmentIds, DataIsolation $dataIsolation): array
    {
        $departments = $this->departmentRepository->getOrganizationDepartments(
            $dataIsolation->getCurrentOrganizationCode(),
            ['department_id', 'parent_department_id', 'name', 'path']
        );

        $departmentsChildrenEntities = $this->getChildrenByDepartmentIds($departments, $departmentIds);
        // 合并 && 去重
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
     * 根department被抽象为 -1，所以这里need转换为actual的根department id.
     */
    public function getDepartmentRootId(DataIsolation $dataIsolation): string
    {
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();
        // getorganization所属的平台type
        $platformType = $this->organizationsPlatformRepository->getOrganizationPlatformType($organizationCode);
        if ($platformType === PlatformType::Delightful) {
            // get根departmentID
            return $this->departmentRepository->getDepartmentRootId($organizationCode);
        }

        // according toorganization编码和平台typeget根departmentID
        return $this->thirdPlatformIdMappingRepository->getDepartmentRootId($organizationCode, $platformType);
    }

    /**
     * 批量get多个organization的根departmentinfo.
     * @param array $organizationCodes organization代码array
     * @return array<string,DelightfulDepartmentEntity> 以organization代码为键，根department实体为value的关联array
     */
    public function getOrganizationsRootDepartment(array $organizationCodes): array
    {
        $rootDepartments = $this->departmentRepository->getOrganizationsRootDepartment($organizationCodes);

        // check是否有根department数据
        if (empty($rootDepartments)) {
            return [];
        }

        // process数据format，以organization代码为键，根department实体为value
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
     * getdepartment的所有子department.
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

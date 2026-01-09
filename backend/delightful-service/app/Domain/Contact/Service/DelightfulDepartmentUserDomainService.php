<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Service;

use App\Domain\Chat\DTO\PageResponseDTO\DepartmentUsersPageResponseDTO;
use App\Domain\Contact\DTO\UserQueryDTO;
use App\Domain\Contact\Entity\DelightfulDepartmentUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Repository\Facade\DelightfulDepartmentUserRepositoryInterface;

readonly class DelightfulDepartmentUserDomainService
{
    public function __construct(
        private DelightfulDepartmentUserRepositoryInterface $departmentUserRepository,
    ) {
    }

    /**
     * @return DelightfulDepartmentUserEntity[]
     */
    public function getDepartmentUsersByUserIds(array $userIds, DataIsolation $dataIsolation): array
    {
        return $this->departmentUserRepository->getDepartmentUsersByUserIds($userIds, $dataIsolation->getCurrentOrganizationCode());
    }

    /**
     * @return DelightfulDepartmentUserEntity[]
     */
    public function getDepartmentUsersByUserIdsInDelightful(array $userIds): array
    {
        return $this->departmentUserRepository->getDepartmentUsersByUserIdsInDelightful($userIds);
    }

    public function getDepartmentUsersByDepartmentId(UserQueryDTO $contactUserListQueryDTO, DataIsolation $dataIsolation): DepartmentUsersPageResponseDTO
    {
        // 暂时不支持递归处理
        return $this->departmentUserRepository->getDepartmentUsersByDepartmentId(
            $contactUserListQueryDTO->getDepartmentId(),
            $dataIsolation->getCurrentOrganizationCode(),
            $contactUserListQueryDTO->getPageSize(),
            (int) $contactUserListQueryDTO->getPageToken()
        );
    }

    /**
     * getdepartment和其所有子departmentuser数量.
     */
    public function getDepartmentUsersByDepartmentIds(array $departmentIds, DataIsolation $dataIsolation, int $limit, array $fields = ['*']): array
    {
        return $this->departmentUserRepository->getDepartmentUsersByDepartmentIds(
            $departmentIds,
            $dataIsolation->getCurrentOrganizationCode(),
            $limit,
            $fields
        );
    }

    /**
     * getuser所在department.
     * 一对多关系.
     */
    public function getDepartmentIdsByUserIds(DataIsolation $dataIsolation, array $userIds, bool $withAllParentIds = false): array
    {
        return $this->departmentUserRepository->getDepartmentIdsByUserIds($dataIsolation, $userIds, $withAllParentIds);
    }

    /**
     * getuser所在department.
     * 一对多关系.
     */
    public function getDepartmentIdsByUserId(DataIsolation $dataIsolation, string $userId, bool $withAllParentIds = false): array
    {
        return $this->departmentUserRepository->getDepartmentIdsByUserIds($dataIsolation, [$userId], $withAllParentIds)[$userId] ?? [];
    }

    /**
     * @return DelightfulDepartmentUserEntity[]
     */
    public function searchDepartmentUsersByJobTitle(string $keyword, DataIsolation $dataIsolation): array
    {
        return $this->departmentUserRepository->searchDepartmentUsersByJobTitle($keyword, $dataIsolation->getCurrentOrganizationCode());
    }
}

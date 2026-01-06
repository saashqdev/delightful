<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Facade;

use App\Domain\Chat\DTO\PageResponseDTO\DepartmentUsersPageResponseDTO;
use App\Domain\Contact\Entity\DelightfulDepartmentUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;

interface DelightfulDepartmentUserRepositoryInterface
{
    /**
     * @return DelightfulDepartmentUserEntity[]
     */
    public function getDepartmentUsersByUserIds(array $userIds, string $organizationCode): array;

    /**
     * @return DelightfulDepartmentUserEntity[]
     */
    public function getDepartmentUsersByUserIdsInDelightful(array $userIds): array;

    public function getDepartmentUsersByDepartmentId(string $departmentId, string $organizationCode, int $limit, int $offset): DepartmentUsersPageResponseDTO;

    /**
     * @return DelightfulDepartmentUserEntity[]
     */
    public function getDepartmentUsersByDepartmentIds(array $departmentIds, string $organizationCode, int $limit, array $fields = ['*']): array;

    public function getDepartmentIdsByUserIds(DataIsolation $dataIsolation, array $userIds, bool $withAllParentIds = false): array;

    public function createDepartmentUsers(array $createDepartmentUserDTOs): bool;

    public function updateDepartmentUser(string $magicDepartmentUserPrimaryId, array $updateData): int;

    public function deleteDepartmentUsersByDelightfulIds(array $magicIds, string $departmentId, string $magicOrganizationCode): int;

    public function searchDepartmentUsersByJobTitle(string $keyword, string $magicOrganizationCode): array;
}

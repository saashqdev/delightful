<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Permission\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation as ContactDataIsolation;
use App\Domain\Contact\Repository\Facade\MagicDepartmentUserRepositoryInterface;
use App\Domain\Group\Repository\Facade\MagicGroupRepositoryInterface;
use App\Domain\Permission\Entity\OperationPermissionEntity;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\Operation;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\TargetType;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\Domain\Permission\Repository\Facade\OperationPermissionRepositoryInterface;
use DateTime;
use Hyperf\DbConnection\Annotation\Transactional;
use JetBrains\PhpStorm\ArrayShape;

readonly class OperationPermissionDomainService
{
    public function __construct(
        private OperationPermissionRepositoryInterface $operationPermissionRepository,
        private MagicGroupRepositoryInterface $magicGroupRepository,
        private MagicDepartmentUserRepositoryInterface $departmentUserRepository,
    ) {
    }

    /**
     * @return array<string, OperationPermissionEntity>
     */
    public function listByResource(PermissionDataIsolation $dataIsolation, ResourceType $resourceType, string $resourceId): array
    {
        return $this->operationPermissionRepository->listByResource($dataIsolation, $resourceType, $resourceId);
    }

    /**
     * @return array<OperationPermissionEntity>
     */
    public function listByTargetIds(PermissionDataIsolation $dataIsolation, ResourceType $resourceType, array $targetIds, array $resourceIds = []): array
    {
        return $this->operationPermissionRepository->listByTargetIds($dataIsolation, $resourceType, $targetIds, $resourceIds);
    }

    /**
     * Grant ownership.
     */
    public function accessOwner(PermissionDataIsolation $dataIsolation, ResourceType $resourceType, string $resourceId, string $userId): OperationPermissionEntity
    {
        $operationPermission = new OperationPermissionEntity();
        $operationPermission->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $operationPermission->setResourceType($resourceType);
        $operationPermission->setResourceId($resourceId);
        $operationPermission->setTargetType(TargetType::UserId);
        $operationPermission->setTargetId($userId);
        $operationPermission->setOperation(Operation::Owner);
        $operationPermission->setCreator($dataIsolation->getCurrentUserId());
        $operationPermission->prepareForSave();
        return $this->operationPermissionRepository->save($dataIsolation, $operationPermission);
    }

    /**
     * Transfer resource ownership.
     */
    #[Transactional]
    public function transferOwner(PermissionDataIsolation $dataIsolation, ResourceType $resourceType, string $resourceId, string $ownerUserId, bool $reserveManager = true): void
    {
        $ownerOperationPermission = $this->operationPermissionRepository->getResourceOwner($dataIsolation, $resourceType, $resourceId);
        if (! $ownerOperationPermission) {
            return;
        }
        if ($ownerOperationPermission->getTargetId() === $ownerUserId) {
            // No changes
            return;
        }
        // Assign the new owner
        $this->accessOwner($dataIsolation, $resourceType, $resourceId, $ownerUserId);
        if ($reserveManager) {
            // Keep admin permission
            $ownerOperationPermission->setOperation(Operation::Admin);
            $this->operationPermissionRepository->save($dataIsolation, $ownerOperationPermission);
        } else {
            $this->operationPermissionRepository->beachDelete($dataIsolation, [$ownerOperationPermission]);
        }
    }

    /**
     * Authorize operations on a resource.
     * @param array<OperationPermissionEntity> $operationPermissions
     */
    #[Transactional]
    public function resourceAccess(PermissionDataIsolation $dataIsolation, ResourceType $resourceType, string $resourceId, array $operationPermissions): void
    {
        // Load all current permissions for this resource; split into add/edit/delete (could switch to delete+insert)
        $add = $edit = [];

        $historyOperationPermissions = $this->operationPermissionRepository->listByResource($dataIsolation, $resourceType, $resourceId);
        // Remove owner entries since they cannot be changed
        foreach ($historyOperationPermissions as $index => $historyOperationPermission) {
            if ($historyOperationPermission->getOperation()->isOwner()) {
                unset($historyOperationPermissions[$index]);
            }
        }

        $handleKey = [];
        foreach ($operationPermissions as $operationPermission) {
            $operationPermission->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
            $operationPermission->setResourceType($resourceType);
            $operationPermission->setResourceId($resourceId);
            $operationPermission->setCreator($dataIsolation->getCurrentUserId());

            $key = $operationPermission->getTargetType()->value . '_' . $operationPermission->getTargetId();
            $historyOperationPermission = $historyOperationPermissions[$key] ?? null;
            unset($historyOperationPermissions[$key]);

            // De-duplicate by key
            if ($handleKey[$key] ?? false) {
                continue;
            }
            $handleKey[$key] = true;

            // Skip if current or new operation is owner
            if ($historyOperationPermission && $historyOperationPermission->getOperation()->isOwner()) {
                continue;
            }
            if ($operationPermission->getOperation()->isOwner()) {
                continue;
            }

            if ($historyOperationPermission) {
                if ($operationPermission->getOperation() === $historyOperationPermission->getOperation()) {
                    continue;
                }
                $historyOperationPermission->setOperation($operationPermission->getOperation());
                $historyOperationPermission->setUpdatedAt(new DateTime());
                $edit[] = $historyOperationPermission;
            } else {
                $operationPermission->prepareForSave();
                $add[] = $operationPermission;
            }
        }

        $delete = $historyOperationPermissions;

        $this->operationPermissionRepository->batchInsert($dataIsolation, $add);
        $this->operationPermissionRepository->beachUpdate($dataIsolation, $edit);
        $this->operationPermissionRepository->beachDelete($dataIsolation, $delete);
    }

    /**
     * Get the highest operation per user for a resource type.
     */
    #[ArrayShape([
        // userId => [resourceId => Operation]
        'string' => [
            'string' => Operation::class,
        ],
    ])]
    public function getResourceOperationByUserIds(PermissionDataIsolation $dataIsolation, ResourceType $resourceType, array $userIds, array $resourceIds = []): array
    {
        $contactDataIsolation = ContactDataIsolation::simpleMake($dataIsolation->getCurrentOrganizationCode(), $dataIsolation->getCurrentUserId());
        // Add departments and groups of the users into targets
        $userDepartmentList = $this->departmentUserRepository->getDepartmentIdsByUserIds($contactDataIsolation, $userIds, true);
        $userGroupIds = $this->magicGroupRepository->getGroupIdsByUserIds($userIds);
        $targetIds = [];
        $targetIds[] = $userIds;

        $departmentUserIds = [];
        foreach ($userDepartmentList as $userId => $departmentIds) {
            $targetIds[] = $departmentIds;
            foreach ($departmentIds as $departmentId) {
                $departmentUserIds[$departmentId][] = $userId;
            }
        }

        $groupUserIds = [];
        foreach ($userGroupIds as $userId => $groupIds) {
            $targetIds[] = $groupIds;
            foreach ($groupIds as $groupId) {
                $groupUserIds[$groupId][] = $userId;
            }
        }
        $targetIds = array_merge(...$targetIds);
        $targetIds = array_values(array_unique($targetIds));
        $resourcesOperationPermissions = $this->listByTargetIds($dataIsolation, $resourceType, $targetIds, $resourceIds);

        $list = [];
        foreach ($resourcesOperationPermissions as $resourcesOperationPermission) {
            if ($resourcesOperationPermission->getTargetType() === TargetType::UserId) {
                $userId = $resourcesOperationPermission->getTargetId();
                $topOperation = $list[$userId][$resourcesOperationPermission->getResourceId()] ?? null;
                if ($resourcesOperationPermission->getOperation()->gt($topOperation)) {
                    $list[$userId][$resourcesOperationPermission->getResourceId()] = $resourcesOperationPermission->getOperation();
                }
            }
            if ($resourcesOperationPermission->getTargetType() === TargetType::GroupId) {
                foreach ($groupUserIds[$resourcesOperationPermission->getTargetId()] ?? [] as $userId) {
                    $topOperation = $list[$userId][$resourcesOperationPermission->getResourceId()] ?? null;
                    if ($resourcesOperationPermission->getOperation()->gt($topOperation)) {
                        $list[$userId][$resourcesOperationPermission->getResourceId()] = $resourcesOperationPermission->getOperation();
                    }
                }
            }
            if ($resourcesOperationPermission->getTargetType() === TargetType::DepartmentId) {
                foreach ($departmentUserIds[$resourcesOperationPermission->getTargetId()] ?? [] as $userId) {
                    $topOperation = $list[$userId][$resourcesOperationPermission->getResourceId()] ?? null;
                    if ($resourcesOperationPermission->getOperation()->gt($topOperation)) {
                        $list[$userId][$resourcesOperationPermission->getResourceId()] = $resourcesOperationPermission->getOperation();
                    }
                }
            }
        }

        return $list;
    }
}

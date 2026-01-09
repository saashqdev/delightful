<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Permission\Service;

use App\Domain\Contact\Entity\DelightfulDepartmentEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation as ContactDataIsolation;
use App\Domain\Contact\Service\DelightfulDepartmentDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentUserDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\Flow\Entity\ValueObject\ConstValue;
use App\Domain\Group\Entity\DelightfulGroupEntity;
use App\Domain\Group\Service\DelightfulGroupDomainService;
use App\Domain\Permission\Entity\OperationPermissionEntity;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\Operation;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\TargetType;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\Domain\Permission\Service\OperationPermissionDomainService;
use App\ErrorCode\PermissionErrorCode;
use App\Infrastructure\Core\DataIsolation\BaseDataIsolation;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use JetBrains\PhpStorm\ArrayShape;
use Qbhy\HyperfAuth\Authenticatable;

class OperationPermissionAppService extends AbstractPermissionAppService
{
    public function __construct(
        private readonly OperationPermissionDomainService $operationPermissionDomainService,
        private readonly DelightfulUserDomainService $delightfulUserDomainService,
        private readonly DelightfulDepartmentDomainService $delightfulDepartmentDomainService,
        private readonly DelightfulDepartmentUserDomainService $delightfulDepartmentUserDomainService,
        private readonly DelightfulGroupDomainService $delightfulGroupDomainService,
    ) {
    }

    public function transferOwner(Authenticatable $authorization, ResourceType $resourceType, string $resourceId, string $ownerUserId, bool $reserveManager = true): void
    {
        $dataIsolation = $this->createPermissionDataIsolation($authorization);
        $operation = $this->getOperationByResourceAndUser(
            $dataIsolation,
            $resourceType,
            $resourceId,
            $authorization->getId(),
        );
        $operation->validate('transfer', 'owner');
        $this->operationPermissionDomainService->transferOwner($dataIsolation, $resourceType, $resourceId, $ownerUserId, $reserveManager);
    }

    /**
     * @return array{list: array<string, OperationPermissionEntity>, users: array<string, DelightfulUserEntity>,departments:DelightfulDepartmentEntity[], groups: array<string, DelightfulGroupEntity>}
     */
    public function listByResource(Authenticatable $authorization, ResourceType $resourceType, string $resourceId): array
    {
        $dataIsolation = $this->createPermissionDataIsolation($authorization);

        $operation = $this->getOperationByResourceAndUser(
            $dataIsolation,
            $resourceType,
            $resourceId,
            $authorization->getId()
        );
        if (! $operation->canRead()) {
            ExceptionBuilder::throw(PermissionErrorCode::BusinessException, 'common.access', ['label' => $resourceId]);
        }

        $list = $this->operationPermissionDomainService->listByResource($dataIsolation, $resourceType, $resourceId);
        $userIds = [];
        $departmentIds = [];
        $groupIds = [];
        foreach ($list as $item) {
            if ($item->getTargetType() === TargetType::UserId) {
                $userIds[] = $item->getTargetId();
            }
            if ($item->getTargetType() === TargetType::DepartmentId) {
                $departmentIds[] = $item->getTargetId();
            }
            if ($item->getTargetType() === TargetType::GroupId) {
                $groupIds[] = $item->getTargetId();
            }
        }
        $contactDataIsolation = ContactDataIsolation::simpleMake($dataIsolation->getCurrentOrganizationCode(), $dataIsolation->getCurrentUserId());
        // according to userid getuserinformation
        $users = $this->delightfulUserDomainService->getByUserIds($contactDataIsolation, $userIds);
        // getuser departmentId
        $userDepartmentList = $this->delightfulDepartmentUserDomainService->getDepartmentIdsByUserIds($contactDataIsolation, $userIds);
        foreach ($userDepartmentList as $userDepartmentIds) {
            $departmentIds = array_merge($departmentIds, $userDepartmentIds);
        }
        $departments = $this->delightfulDepartmentDomainService->getDepartmentByIds($contactDataIsolation, $departmentIds, true);
        // getgroupinformation
        $groups = $this->delightfulGroupDomainService->getGroupsInfoByIds($groupIds, $contactDataIsolation, true);

        return [
            'list' => $list,
            'users' => $users,
            'departments' => $departments,
            'groups' => $groups,
        ];
    }

    /**
     * toresourceconductauthorization.
     * @param array<OperationPermissionEntity> $operationPermissions
     */
    public function resourceAccess(Authenticatable $authorization, ResourceType $resourceType, string $resourceId, array $operationPermissions): void
    {
        $dataIsolation = $this->createPermissionDataIsolation($authorization);
        $operation = $this->getOperationByResourceAndUser(
            $dataIsolation,
            $resourceType,
            $resourceId,
            $authorization->getId()
        );
        $operation->validate('manage', $resourceId);

        $this->operationPermissionDomainService->resourceAccess($dataIsolation, $resourceType, $resourceId, $operationPermissions);
    }

    /**
     * getusertosomeresourcemost高permission.
     */
    public function getOperationByResourceAndUser(PermissionDataIsolation $dataIsolation, ResourceType $resourceType, string $resourceId, string $userId): Operation
    {
        if ($resourceType === ResourceType::ToolSet && $resourceId === ConstValue::TOOL_SET_DEFAULT_CODE) {
            return Operation::Admin;
        }
        return $this->getResourceOperationByUserIds($dataIsolation, $resourceType, [$userId], [$resourceId])[$userId][$resourceId] ?? Operation::None;
    }

    /**
     * getusertosome一categoryresourcemost高操aspermission.
     */
    #[ArrayShape([
        // userId => [resourceId => Operation]
        'string' => [
            'string' => Operation::class,
        ],
    ])]
    public function getResourceOperationByUserIds(BaseDataIsolation|PermissionDataIsolation $dataIsolation, ResourceType $resourceType, array $userIds, array $resourceIds = []): array
    {
        if (! $dataIsolation instanceof PermissionDataIsolation) {
            $dataIsolation = $this->createPermissionDataIsolation($dataIsolation);
        }
        return $this->operationPermissionDomainService->getResourceOperationByUserIds($dataIsolation, $resourceType, $userIds, $resourceIds);
    }
}

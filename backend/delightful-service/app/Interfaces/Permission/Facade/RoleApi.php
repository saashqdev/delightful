<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Permission\Facade;

use App\Application\Chat\Service\DelightfulUserInfoAppService;
use App\Application\Kernel\Enum\DelightfulOperationEnum;
use App\Application\Kernel\Enum\DelightfulResourceEnum;
use App\Application\Permission\Service\RoleAppService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation as ContactDataIsolation;
use App\Domain\Permission\Entity\RoleEntity;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\Domain\Permission\Entity\ValueObject\Query\SubAdminQuery;
use App\Infrastructure\Util\Permission\Annotation\CheckPermission;
use App\Interfaces\Kernel\DTO\PageDTO;
use App\Interfaces\Permission\Assembler\SubAdminAssembler;
use App\Interfaces\Permission\DTO\CreateSubAdminRequestDTO;
use App\Interfaces\Permission\DTO\UpdateSubAdminRequestDTO;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;
use InvalidArgumentException;

#[ApiResponse(version: 'low_code')]
class RoleApi extends AbstractPermissionApi
{
    #[Inject]
    protected RoleAppService $roleAppService;

    #[Inject]
    protected DelightfulUserInfoAppService $userInfoAppService;

    #[CheckPermission(DelightfulResourceEnum::SAFE_SUB_ADMIN, DelightfulOperationEnum::QUERY)]
    public function getSubAdminList(): array
    {
        // getauthinfo
        $authorization = $this->getAuthorization();

        // createdata隔离updown文
        $dataIsolation = PermissionDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId()
        );

        // createpaginationobject
        $page = $this->createPage();

        // buildqueryobject（from动filter掉paginationfield）
        $query = new SubAdminQuery($this->request->all());

        // convertfor仓储filterarray
        $filters = $query->toFilters();

        // queryrolelist
        $result = $this->roleAppService->queries($dataIsolation, $page, $filters);

        // batchquantitygetuserdetail（eachrole仅取front5userId）
        $contactIsolation = ContactDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId()
        );

        // 收collectionneedqueryuserID
        $roleUserIdsMap = [];
        $allNeedUserIds = [];
        foreach ($result['list'] as $index => $roleEntity) {
            /** @var RoleEntity $roleEntity */
            $limitedIds = array_slice($roleEntity->getUserIds(), 0, 5);
            $limitedIds[] = $roleEntity->getUpdatedUid();
            $roleUserIdsMap[$index] = $limitedIds;
            $allNeedUserIds = array_merge($allNeedUserIds, $limitedIds);
        }
        $allNeedUserIds = array_values(array_unique($allNeedUserIds));

        // batchquantityqueryuserinfo
        $allUserInfo = [];
        if (! empty($allNeedUserIds)) {
            $allUserInfo = $this->userInfoAppService->getBatchUserInfo($allNeedUserIds, $contactIsolation);
        }

        // 重新group装listdata
        $list = [];
        foreach ($result['list'] as $index => $roleEntity) {
            $limitedIds = $roleUserIdsMap[$index] ?? [];
            // Exclude the operator (updatedUid) from the user list that will be displayed
            $displayUserIds = array_diff($limitedIds, [$roleEntity->getUpdatedUid()]);
            $userDetailsForRole = array_intersect_key($allUserInfo, array_flip($displayUserIds));
            $list[] = SubAdminAssembler::assembleWithUserInfo(
                $roleEntity,
                $userDetailsForRole,
                $allUserInfo[$roleEntity->getUpdatedUid()] ?? null
            );
        }

        return (new PageDTO($page->getPage(), $result['total'], $list))->toArray();
    }

    #[CheckPermission(DelightfulResourceEnum::SAFE_SUB_ADMIN, DelightfulOperationEnum::QUERY)]
    public function getSubAdminById(int $id): array
    {
        // getauthinfo
        $authorization = $this->getAuthorization();

        // createdata隔离updown文
        $dataIsolation = PermissionDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId()
        );

        // getroledetail
        $roleEntity = $this->roleAppService->show($dataIsolation, $id);

        // getroleassociateuserinfo
        $contactIsolation = ContactDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId()
        );
        $userInfo = $this->userInfoAppService->getBatchUserInfo(
            $roleEntity->getUserIds(),
            $contactIsolation
        );

        return SubAdminAssembler::assembleWithUserInfo($roleEntity, $userInfo);
    }

    #[CheckPermission(DelightfulResourceEnum::SAFE_SUB_ADMIN, DelightfulOperationEnum::EDIT)]
    public function createSubAdmin(): array
    {
        // getauthinfo
        $authorization = $this->getAuthorization();

        // createdata隔离updown文
        $dataIsolation = PermissionDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId()
        );

        // createandverifyrequestDTO
        $requestDTO = new CreateSubAdminRequestDTO($this->request->all());
        if (! $requestDTO->validate()) {
            $errors = $requestDTO->getValidationErrors();
            throw new InvalidArgumentException('requestparameterverifyfail: ' . implode(', ', $errors));
        }

        // createrole实body
        $roleEntity = new RoleEntity();
        $roleEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $roleEntity->setCreatedUid($dataIsolation->getCurrentUserId());
        $roleEntity->setName($requestDTO->getName());
        $roleEntity->setPermissions($requestDTO->getPermissions());
        $roleEntity->setUserIds($requestDTO->getUserIds());
        $roleEntity->setStatus($requestDTO->getStatus());

        $savedRole = $this->roleAppService->createRole(
            $dataIsolation,
            $roleEntity
        );

        return $savedRole->toArray();
    }

    #[CheckPermission(DelightfulResourceEnum::SAFE_SUB_ADMIN, DelightfulOperationEnum::EDIT)]
    public function updateSubAdmin(): array
    {
        // getauthinfo
        $authorization = $this->getAuthorization();

        // createdata隔离updown文
        $dataIsolation = PermissionDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId()
        );

        // getroleID
        $roleId = (int) $this->request->route('id');

        // createandverifyrequestDTO
        $requestDTO = new UpdateSubAdminRequestDTO($this->request->all());

        if (! $requestDTO->validate()) {
            $errors = $requestDTO->getValidationErrors();
            throw new InvalidArgumentException('requestparameterverifyfail: ' . implode(', ', $errors));
        }
        if (! $requestDTO->hasUpdates()) {
            throw new InvalidArgumentException('at leastneed提供one要updatefield');
        }

        // get现haverole
        $roleEntity = $this->roleAppService->show($dataIsolation, $roleId);

        $updateFields = $requestDTO->getUpdateFields();
        if (isset($updateFields['name'])) {
            $roleEntity->setName($updateFields['name']);
        }
        if (isset($updateFields['status'])) {
            $roleEntity->setStatus($updateFields['status']);
        }
        if (isset($updateFields['permissions'])) {
            $roleEntity->setPermissions($updateFields['permissions']);
        }
        if (isset($updateFields['userIds'])) {
            $roleEntity->setUserIds($requestDTO->getUserIds());
        }

        $savedRole = $this->roleAppService->updateRole($dataIsolation, $roleEntity);

        return $savedRole->toArray();
    }

    #[CheckPermission(DelightfulResourceEnum::SAFE_SUB_ADMIN, DelightfulOperationEnum::EDIT)]
    public function deleteSubAdmin(int $id): array
    {
        // getauthinfo
        $authorization = $this->getAuthorization();

        // createdata隔离updown文
        $dataIsolation = PermissionDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId()
        );

        // deleterole
        $this->roleAppService->destroy($dataIsolation, $id);

        // returnemptyarrayby触hair统one ApiResponse 封装
        return [];
    }
}

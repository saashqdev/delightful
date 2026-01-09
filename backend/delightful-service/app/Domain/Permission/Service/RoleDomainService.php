<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Permission\Service;

use App\Application\Kernel\Contract\DelightfulPermissionInterface;
use App\Application\Kernel\DelightfulPermission;
use App\Domain\Contact\Repository\Facade\DelightfulUserRepositoryInterface;
use App\Domain\Permission\Entity\RoleEntity;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\Domain\Permission\Repository\Facade\RoleRepositoryInterface;
use App\ErrorCode\PermissionErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Throwable;

readonly class RoleDomainService
{
    /**
     * organization管理员rolenameconstant.
     */
    public const ORGANIZATION_ADMIN_ROLE_NAME = 'ORGANIZATION_ADMIN';

    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private DelightfulPermissionInterface $permission,
        private DelightfulUserRepositoryInterface $userRepository
    ) {
    }

    /**
     * queryrolecolumn表.
     * @return array{total: int, list: RoleEntity[]}
     */
    public function queries(PermissionDataIsolation $dataIsolation, Page $page, ?array $filters = null): array
    {
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // queryrolecolumn表
        $result = $this->roleRepository->queries($organizationCode, $page, $filters);

        // 批quantityqueryuserID，避免 N+1 query
        $roleIds = array_map(static fn (RoleEntity $r) => $r->getId(), $result['list']);
        $roleUsersMap = $this->roleRepository->getRoleUsersMap($organizationCode, $roleIds);

        foreach ($result['list'] as $roleEntity) {
            /* @var RoleEntity $roleEntity */
            $userIds = $roleUsersMap[$roleEntity->getId()] ?? [];
            $roleEntity->setUserIds($userIds);
        }

        return $result;
    }

    /**
     * saverole.
     */
    public function save(PermissionDataIsolation $dataIsolation, RoleEntity $savingRoleEntity): RoleEntity
    {
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();
        $savingRoleEntity->setOrganizationCode($organizationCode);

        // 校验传入的userIDwhether属atcurrentorganization
        $inputUserIds = $savingRoleEntity->getUserIds();
        if (! empty($inputUserIds)) {
            $validUsers = $this->userRepository->getByUserIds($organizationCode, $inputUserIds);
            if (count($validUsers) !== count($inputUserIds)) {
                $invalidIds = array_diff($inputUserIds, array_keys($validUsers));
                ExceptionBuilder::throw(
                    PermissionErrorCode::ValidateFailed,
                    'permission.error.user_not_in_organization',
                    ['userIds' => implode(',', $invalidIds)]
                );
            }
        }

        // 1. 校验permission键validproperty
        // update permissionTag info：according topermission键提取二level模piecetag，useatfront端展示category
        $permissionTags = [];
        foreach ($savingRoleEntity->getPermissions() as $permissionKey) {
            // 校验permission键validproperty
            if (! $this->permission->isValidPermission($permissionKey)) {
                ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.invalid_permission_key', ['key' => $permissionKey]);
            }

            // skipall局permissionconstant，无需参与tag提取
            if ($permissionKey === DelightfulPermission::ALL_PERMISSIONS) {
                continue;
            }

            // parsepermission键，get资源并提取其二level模piecetag
            try {
                $parsed = $this->permission->parsePermission($permissionKey);
                $resource = $parsed['resource'];
                $moduleLabel = $this->permission->getResourceModule($resource);
                $permissionTags[$moduleLabel] = $moduleLabel; // use键value去重
            } catch (Throwable $e) {
                // parsefailo clockignore该permission的tag提取，校验已pass，not影响save
            }
        }

        // 将tagcolumn表write RoleEntity
        if (! empty($permissionTags)) {
            $savingRoleEntity->setPermissionTag(array_values($permissionTags));
        }

        if ($savingRoleEntity->shouldCreate()) {
            $roleEntity = clone $savingRoleEntity;
            $roleEntity->prepareForCreation($dataIsolation->getCurrentUserId());

            // checknameinorganizationdownwhether唯一
            if ($this->roleRepository->getByName($organizationCode, $savingRoleEntity->getName())) {
                ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.role_name_exists', ['name' => $savingRoleEntity->getName()]);
            }
        } else {
            $roleEntity = $this->roleRepository->getById($organizationCode, $savingRoleEntity->getId());
            if (! $roleEntity) {
                ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.role_not_found', ['id' => $savingRoleEntity->getId()]);
            }

            // checkname修改backwhetherconflict
            if ($roleEntity->getName() !== $savingRoleEntity->getName()) {
                $existingRole = $this->roleRepository->getByName($organizationCode, $savingRoleEntity->getName());
                if ($existingRole && $existingRole->getId() !== $savingRoleEntity->getId()) {
                    ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.role_name_exists', ['name' => $savingRoleEntity->getName()]);
                }
            }

            $savingRoleEntity->prepareForModification();
            $roleEntity = $savingRoleEntity;
        }

        // saverole本身
        $savedRoleEntity = $this->roleRepository->save($organizationCode, $roleEntity);

        // 2. 维护role与user的associate关系
        $userIds = $savedRoleEntity->getUserIds();
        if (! empty($userIds)) {
            $this->roleRepository->assignUsers(
                $organizationCode,
                $savedRoleEntity->getId(),
                $userIds,
                $dataIsolation->getCurrentUserId()
            );
        }

        return $savedRoleEntity;
    }

    /**
     * getroledetail.
     */
    public function show(PermissionDataIsolation $dataIsolation, int $id): RoleEntity
    {
        $roleEntity = $this->roleRepository->getById($dataIsolation->getCurrentOrganizationCode(), $id);
        if (! $roleEntity) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.role_not_found', ['id' => $id]);
        }

        // 补充roleassociate的userIDinfo
        $roleUsers = $this->roleRepository->getRoleUsers($dataIsolation->getCurrentOrganizationCode(), $id);
        $roleEntity->setUserIds($roleUsers);

        return $roleEntity;
    }

    /**
     * according tonamegetrole.
     */
    public function getByName(PermissionDataIsolation $dataIsolation, string $name): ?RoleEntity
    {
        $roleEntity = $this->roleRepository->getByName($dataIsolation->getCurrentOrganizationCode(), $name);

        // 补充roleassociate的userIDinfo，避免call方getnotto userIds
        if ($roleEntity !== null) {
            $userIds = $this->roleRepository->getRoleUsers($dataIsolation->getCurrentOrganizationCode(), $roleEntity->getId());
            $roleEntity->setUserIds($userIds);
        }

        return $roleEntity;
    }

    /**
     * deleterole.
     */
    public function destroy(PermissionDataIsolation $dataIsolation, RoleEntity $roleEntity): void
    {
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // checkrolewhetheralsohaveuserassociate
        $roleUsers = $this->roleRepository->getRoleUsers($organizationCode, $roleEntity->getId());
        if (! empty($roleUsers)) {
            // 先deleterole与user的associate关系
            $this->roleRepository->removeUsers($organizationCode, $roleEntity->getId(), $roleUsers);
        }

        $this->roleRepository->delete($organizationCode, $roleEntity);
    }

    /**
     * getuserrolecolumn表.
     */
    public function getUserRoles(PermissionDataIsolation $dataIsolation, string $userId): array
    {
        return $this->roleRepository->getUserRoles($dataIsolation->getCurrentOrganizationCode(), $userId);
    }

    /**
     * getuser所havepermission.
     */
    public function getUserPermissions(PermissionDataIsolation $dataIsolation, string $userId): array
    {
        return $this->roleRepository->getUserPermissions($dataIsolation->getCurrentOrganizationCode(), $userId);
    }

    /**
     * checkuserwhether拥havefinger定permission.
     */
    public function hasPermission(PermissionDataIsolation $dataIsolation, string $userId, string $permissionKey): bool
    {
        $isPlatformOrganization = false;
        $officialOrganization = config('service_provider.office_organization');
        if ($officialOrganization === $dataIsolation->getCurrentOrganizationCode()) {
            $isPlatformOrganization = true;
        }
        $userPermissions = $this->roleRepository->getUserPermissions($dataIsolation->getCurrentOrganizationCode(), $userId);
        return $this->permission->checkPermission($permissionKey, $userPermissions, $isPlatformOrganization);
    }

    /**
     * getpermission资源树结构.
     *
     * @param bool $isPlatformOrganization whether平台organization
     */
    public function getPermissionTree(bool $isPlatformOrganization = false): array
    {
        $permissionEnum = di(DelightfulPermissionInterface::class);
        return $permissionEnum->getPermissionTree($isPlatformOrganization);
    }

    /**
     * 为finger定usercreateor维护“organization管理员”role（拥haveall局permission）。
     *
     * 逻辑：
     * 1. according tocurrentorganization查找whether已have同名role；
     * 2. 若not存in，thencreatenewrole并赋予 DelightfulPermission::ALL_PERMISSIONS；
     * 3. 若存in，thenensure其contain ALL_PERMISSIONS；
     * 4. 将user ID column表加入roleassociateusercolumn表；
     * 5. saverole。
     *
     * exception由call方自linehandle，避免影响主process。
     */
    public function addOrganizationAdmin(PermissionDataIsolation $dataIsolation, array $userIds): RoleEntity
    {
        // getcurrentorganizationencoding
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // 1. 尝试get已存in的organization管理员role
        $roleEntity = $this->getByName($dataIsolation, self::ORGANIZATION_ADMIN_ROLE_NAME);

        if ($roleEntity === null) {
            // create新role
            $roleEntity = new RoleEntity();
            $roleEntity->setName(self::ORGANIZATION_ADMIN_ROLE_NAME);
            $roleEntity->setOrganizationCode($organizationCode);
            $roleEntity->setStatus(1);
            $roleEntity->setIsDisplay(0);
        }

        // 2. ensure拥haveall局permission ALL_PERMISSIONS
        $permissions = $roleEntity->getPermissions();
        if (! in_array(DelightfulPermission::ALL_PERMISSIONS, $permissions, true)) {
            $permissions[] = DelightfulPermission::ALL_PERMISSIONS;
            $roleEntity->setPermissions($permissions);
        }

        // 3. 将usercolumn表加入roleusercolumn表
        $existingUserIds = $roleEntity->getUserIds();
        // merge并去重
        $mergedUserIds = array_unique(array_merge($existingUserIds, $userIds));
        $roleEntity->setUserIds($mergedUserIds);

        // 4. save并return
        return $this->save($dataIsolation, $roleEntity);
    }

    /**
     * 移exceptuser的“organization管理员”role。
     *
     * 逻辑：
     * 1. getcurrentorganizationdown名为 ORGANIZATION_ADMIN_ROLE_NAME 的role；
     * 2. 若not存in直接return；
     * 3. call仓library移exceptuser与该role的associate关系；
     * 4. ifrolenotagainassociate任何user，保持role本身not变（如haveneed，可考虑back续清理）。
     */
    public function removeOrganizationAdmin(PermissionDataIsolation $dataIsolation, string $userId): void
    {
        // getorganization管理员role
        $roleEntity = $this->getByName($dataIsolation, self::ORGANIZATION_ADMIN_ROLE_NAME);

        if ($roleEntity === null) {
            // rolenot存in，无需handle
            return;
        }

        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // use仓library移exceptuser与role的associate
        $this->roleRepository->removeUsers($organizationCode, $roleEntity->getId(), [$userId]);
    }
}

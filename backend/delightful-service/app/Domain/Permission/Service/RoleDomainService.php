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
     * organization管理员role名称constant.
     */
    public const ORGANIZATION_ADMIN_ROLE_NAME = 'ORGANIZATION_ADMIN';

    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private DelightfulPermissionInterface $permission,
        private DelightfulUserRepositoryInterface $userRepository
    ) {
    }

    /**
     * queryrole列表.
     * @return array{total: int, list: RoleEntity[]}
     */
    public function queries(PermissionDataIsolation $dataIsolation, Page $page, ?array $filters = null): array
    {
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // queryrole列表
        $result = $this->roleRepository->queries($organizationCode, $page, $filters);

        // 批量queryuserID，避免 N+1 query
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

        // 校验传入的userID是否属于currentorganization
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

        // 1. 校验permission键valid性
        // update permissionTag info：according topermission键提取二级模块tag，用于前端展示category
        $permissionTags = [];
        foreach ($savingRoleEntity->getPermissions() as $permissionKey) {
            // 校验permission键valid性
            if (! $this->permission->isValidPermission($permissionKey)) {
                ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.invalid_permission_key', ['key' => $permissionKey]);
            }

            // 跳过全局permissionconstant，无需参与tag提取
            if ($permissionKey === DelightfulPermission::ALL_PERMISSIONS) {
                continue;
            }

            // parsepermission键，get资源并提取其二级模块tag
            try {
                $parsed = $this->permission->parsePermission($permissionKey);
                $resource = $parsed['resource'];
                $moduleLabel = $this->permission->getResourceModule($resource);
                $permissionTags[$moduleLabel] = $moduleLabel; // use键value去重
            } catch (Throwable $e) {
                // parsefail时忽略该permission的tag提取，校验已pass，不影响save
            }
        }

        // 将tag列表write RoleEntity
        if (! empty($permissionTags)) {
            $savingRoleEntity->setPermissionTag(array_values($permissionTags));
        }

        if ($savingRoleEntity->shouldCreate()) {
            $roleEntity = clone $savingRoleEntity;
            $roleEntity->prepareForCreation($dataIsolation->getCurrentUserId());

            // check名称在organization下是否唯一
            if ($this->roleRepository->getByName($organizationCode, $savingRoleEntity->getName())) {
                ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.role_name_exists', ['name' => $savingRoleEntity->getName()]);
            }
        } else {
            $roleEntity = $this->roleRepository->getById($organizationCode, $savingRoleEntity->getId());
            if (! $roleEntity) {
                ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.role_not_found', ['id' => $savingRoleEntity->getId()]);
            }

            // check名称修改后是否冲突
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

        // 2. 维护role与user的关联关系
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
     * getrole详情.
     */
    public function show(PermissionDataIsolation $dataIsolation, int $id): RoleEntity
    {
        $roleEntity = $this->roleRepository->getById($dataIsolation->getCurrentOrganizationCode(), $id);
        if (! $roleEntity) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.role_not_found', ['id' => $id]);
        }

        // 补充role关联的userIDinfo
        $roleUsers = $this->roleRepository->getRoleUsers($dataIsolation->getCurrentOrganizationCode(), $id);
        $roleEntity->setUserIds($roleUsers);

        return $roleEntity;
    }

    /**
     * according to名称getrole.
     */
    public function getByName(PermissionDataIsolation $dataIsolation, string $name): ?RoleEntity
    {
        $roleEntity = $this->roleRepository->getByName($dataIsolation->getCurrentOrganizationCode(), $name);

        // 补充role关联的userIDinfo，避免call方get不到 userIds
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

        // checkrole是否还有user关联
        $roleUsers = $this->roleRepository->getRoleUsers($organizationCode, $roleEntity->getId());
        if (! empty($roleUsers)) {
            // 先deleterole与user的关联关系
            $this->roleRepository->removeUsers($organizationCode, $roleEntity->getId(), $roleUsers);
        }

        $this->roleRepository->delete($organizationCode, $roleEntity);
    }

    /**
     * getuserrole列表.
     */
    public function getUserRoles(PermissionDataIsolation $dataIsolation, string $userId): array
    {
        return $this->roleRepository->getUserRoles($dataIsolation->getCurrentOrganizationCode(), $userId);
    }

    /**
     * getuser所有permission.
     */
    public function getUserPermissions(PermissionDataIsolation $dataIsolation, string $userId): array
    {
        return $this->roleRepository->getUserPermissions($dataIsolation->getCurrentOrganizationCode(), $userId);
    }

    /**
     * checkuser是否拥有指定permission.
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
     * @param bool $isPlatformOrganization 是否平台organization
     */
    public function getPermissionTree(bool $isPlatformOrganization = false): array
    {
        $permissionEnum = di(DelightfulPermissionInterface::class);
        return $permissionEnum->getPermissionTree($isPlatformOrganization);
    }

    /**
     * 为指定usercreate或维护“organization管理员”role（拥有全局permission）。
     *
     * 逻辑：
     * 1. according tocurrentorganization查找是否已有同名role；
     * 2. 若不存在，则createnewrole并赋予 DelightfulPermission::ALL_PERMISSIONS；
     * 3. 若存在，则ensure其contain ALL_PERMISSIONS；
     * 4. 将user ID 列表加入role关联user列表；
     * 5. saverole。
     *
     * exception由call方自行handle，避免影响主process。
     */
    public function addOrganizationAdmin(PermissionDataIsolation $dataIsolation, array $userIds): RoleEntity
    {
        // getcurrentorganization编码
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // 1. 尝试get已存在的organization管理员role
        $roleEntity = $this->getByName($dataIsolation, self::ORGANIZATION_ADMIN_ROLE_NAME);

        if ($roleEntity === null) {
            // create新role
            $roleEntity = new RoleEntity();
            $roleEntity->setName(self::ORGANIZATION_ADMIN_ROLE_NAME);
            $roleEntity->setOrganizationCode($organizationCode);
            $roleEntity->setStatus(1);
            $roleEntity->setIsDisplay(0);
        }

        // 2. ensure拥有全局permission ALL_PERMISSIONS
        $permissions = $roleEntity->getPermissions();
        if (! in_array(DelightfulPermission::ALL_PERMISSIONS, $permissions, true)) {
            $permissions[] = DelightfulPermission::ALL_PERMISSIONS;
            $roleEntity->setPermissions($permissions);
        }

        // 3. 将user列表加入roleuser列表
        $existingUserIds = $roleEntity->getUserIds();
        // merge并去重
        $mergedUserIds = array_unique(array_merge($existingUserIds, $userIds));
        $roleEntity->setUserIds($mergedUserIds);

        // 4. save并return
        return $this->save($dataIsolation, $roleEntity);
    }

    /**
     * 移除user的“organization管理员”role。
     *
     * 逻辑：
     * 1. getcurrentorganization下名为 ORGANIZATION_ADMIN_ROLE_NAME 的role；
     * 2. 若不存在直接return；
     * 3. call仓库移除user与该role的关联关系；
     * 4. 如果role不再关联任何user，保持role本身不变（如有need，可考虑后续清理）。
     */
    public function removeOrganizationAdmin(PermissionDataIsolation $dataIsolation, string $userId): void
    {
        // getorganization管理员role
        $roleEntity = $this->getByName($dataIsolation, self::ORGANIZATION_ADMIN_ROLE_NAME);

        if ($roleEntity === null) {
            // role不存在，无需handle
            return;
        }

        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // use仓库移除user与role的关联
        $this->roleRepository->removeUsers($organizationCode, $roleEntity->getId(), [$userId]);
    }
}

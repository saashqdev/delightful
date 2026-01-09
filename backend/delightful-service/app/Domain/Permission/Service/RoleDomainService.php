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
     * organizationadministratorrolenameconstant.
     */
    public const ORGANIZATION_ADMIN_ROLE_NAME = 'ORGANIZATION_ADMIN';

    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private DelightfulPermissionInterface $permission,
        private DelightfulUserRepositoryInterface $userRepository
    ) {
    }

    /**
     * queryrolecolumntable.
     * @return array{total: int, list: RoleEntity[]}
     */
    public function queries(PermissionDataIsolation $dataIsolation, Page $page, ?array $filters = null): array
    {
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // queryrolecolumntable
        $result = $this->roleRepository->queries($organizationCode, $page, $filters);

        // batchquantityqueryuserID,avoid N+1 query
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

        // validationpass inuserIDwhether属atcurrentorganization
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

        // 1. validationpermissionkeyvalidproperty
        // update permissionTag info:according topermissionkeyextracttwolevel模piecetag,useatfront端showcategory
        $permissionTags = [];
        foreach ($savingRoleEntity->getPermissions() as $permissionKey) {
            // validationpermissionkeyvalidproperty
            if (! $this->permission->isValidPermission($permissionKey)) {
                ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.invalid_permission_key', ['key' => $permissionKey]);
            }

            // skipall局permissionconstant,no需参andtagextract
            if ($permissionKey === DelightfulPermission::ALL_PERMISSIONS) {
                continue;
            }

            // parsepermissionkey,getresourceandextractitstwolevel模piecetag
            try {
                $parsed = $this->permission->parsePermission($permissionKey);
                $resource = $parsed['resource'];
                $moduleLabel = $this->permission->getResourceModule($resource);
                $permissionTags[$moduleLabel] = $moduleLabel; // usekeyvaluego重
            } catch (Throwable $e) {
                // parsefailo clockignorethepermissiontagextract,validationalreadypass,notimpactsave
            }
        }

        // willtagcolumntablewrite RoleEntity
        if (! empty($permissionTags)) {
            $savingRoleEntity->setPermissionTag(array_values($permissionTags));
        }

        if ($savingRoleEntity->shouldCreate()) {
            $roleEntity = clone $savingRoleEntity;
            $roleEntity->prepareForCreation($dataIsolation->getCurrentUserId());

            // checknameinorganizationdownwhether唯one
            if ($this->roleRepository->getByName($organizationCode, $savingRoleEntity->getName())) {
                ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.role_name_exists', ['name' => $savingRoleEntity->getName()]);
            }
        } else {
            $roleEntity = $this->roleRepository->getById($organizationCode, $savingRoleEntity->getId());
            if (! $roleEntity) {
                ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.role_not_found', ['id' => $savingRoleEntity->getId()]);
            }

            // checknamemodifybackwhetherconflict
            if ($roleEntity->getName() !== $savingRoleEntity->getName()) {
                $existingRole = $this->roleRepository->getByName($organizationCode, $savingRoleEntity->getName());
                if ($existingRole && $existingRole->getId() !== $savingRoleEntity->getId()) {
                    ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.error.role_name_exists', ['name' => $savingRoleEntity->getName()]);
                }
            }

            $savingRoleEntity->prepareForModification();
            $roleEntity = $savingRoleEntity;
        }

        // saveroleitself
        $savedRoleEntity = $this->roleRepository->save($organizationCode, $roleEntity);

        // 2. 维护roleanduserassociateclose系
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

        // supplementroleassociateuserIDinfo
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

        // supplementroleassociateuserIDinfo,avoidcall方getnotto userIds
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
            // 先deleteroleanduserassociateclose系
            $this->roleRepository->removeUsers($organizationCode, $roleEntity->getId(), $roleUsers);
        }

        $this->roleRepository->delete($organizationCode, $roleEntity);
    }

    /**
     * getuserrolecolumntable.
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
     * getpermissionresourcetreestructure.
     *
     * @param bool $isPlatformOrganization whetherplatformorganization
     */
    public function getPermissionTree(bool $isPlatformOrganization = false): array
    {
        $permissionEnum = di(DelightfulPermissionInterface::class);
        return $permissionEnum->getPermissionTree($isPlatformOrganization);
    }

    /**
     * forfinger定usercreateor维护“organizationadministrator”role(拥haveall局permission).
     *
     * logic:
     * 1. according tocurrentorganizationfindwhetheralreadyhavesame namerole;
     * 2. 若not存in,thencreatenewroleand赋予 DelightfulPermission::ALL_PERMISSIONS;
     * 3. 若存in,thenensureitscontain ALL_PERMISSIONS;
     * 4. willuser ID columntableadd入roleassociateusercolumntable;
     * 5. saverole.
     *
     * exceptionbycall方fromlinehandle,avoidimpact主process.
     */
    public function addOrganizationAdmin(PermissionDataIsolation $dataIsolation, array $userIds): RoleEntity
    {
        // getcurrentorganizationencoding
        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // 1. trygetalready存inorganizationadministratorrole
        $roleEntity = $this->getByName($dataIsolation, self::ORGANIZATION_ADMIN_ROLE_NAME);

        if ($roleEntity === null) {
            // createnewrole
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

        // 3. willusercolumntableadd入roleusercolumntable
        $existingUserIds = $roleEntity->getUserIds();
        // mergeandgo重
        $mergedUserIds = array_unique(array_merge($existingUserIds, $userIds));
        $roleEntity->setUserIds($mergedUserIds);

        // 4. saveandreturn
        return $this->save($dataIsolation, $roleEntity);
    }

    /**
     * 移exceptuser“organizationadministrator”role.
     *
     * logic:
     * 1. getcurrentorganizationdown名for ORGANIZATION_ADMIN_ROLE_NAME role;
     * 2. 若not存indirectlyreturn;
     * 3. call仓library移exceptuserandtheroleassociateclose系;
     * 4. ifrolenotagainassociateanyuser,maintainroleitselfnot变(如haveneed,canconsiderback续cleanup).
     */
    public function removeOrganizationAdmin(PermissionDataIsolation $dataIsolation, string $userId): void
    {
        // getorganizationadministratorrole
        $roleEntity = $this->getByName($dataIsolation, self::ORGANIZATION_ADMIN_ROLE_NAME);

        if ($roleEntity === null) {
            // rolenot存in,no需handle
            return;
        }

        $organizationCode = $dataIsolation->getCurrentOrganizationCode();

        // use仓library移exceptuserandroleassociate
        $this->roleRepository->removeUsers($organizationCode, $roleEntity->getId(), [$userId]);
    }
}

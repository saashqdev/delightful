<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Permission\Repository;

use App\Domain\Permission\Entity\RoleEntity;
use App\Domain\Permission\Repository\Facade\RoleRepositoryInterface;
use App\Domain\Permission\Repository\Persistence\Model\RoleModel;
use App\Domain\Permission\Repository\Persistence\Model\RoleUserModel;
use App\Infrastructure\Core\ValueObject\Page;
use DateTime;

use function Hyperf\Support\now;

/**
 * role仓libraryimplement.
 */
class RoleRepository implements RoleRepositoryInterface
{
    /**
     * saverole.
     */
    public function save(string $organizationCode, RoleEntity $roleEntity): RoleEntity
    {
        $data = [
            'name' => $roleEntity->getName(),
            'permission_key' => $roleEntity->getPermissions(),
            'organization_code' => $organizationCode,
            'permission_tag' => $roleEntity->getPermissionTag(),
            'is_display' => $roleEntity->getIsDisplay(),
            'status' => $roleEntity->getStatus(),
            'created_uid' => $roleEntity->getCreatedUid(),
            'updated_uid' => $roleEntity->getUpdatedUid(),
            'updated_at' => $roleEntity->getUpdatedAt() ?? now(),
        ];

        if ($roleEntity->shouldCreate()) {
            $data['created_at'] = $roleEntity->getCreatedAt() ?? now();

            $model = RoleModel::create($data);
            $roleEntity->setId($model->id);
        } else {
            // usemodelupdate以便use casts process JSON 与datefield
            $model = $this->roleQuery($organizationCode)
                ->where('id', $roleEntity->getId())
                ->first();
            if ($model) {
                $model->fill($data);
                $model->save();
            }
        }

        return $roleEntity;
    }

    /**
     * according toIDgetrole.
     */
    public function getById(string $organizationCode, int $id): ?RoleEntity
    {
        $model = $this->roleQuery($organizationCode)
            ->where('id', $id)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    /**
     * according tonamegetrole.
     */
    public function getByName(string $organizationCode, string $name): ?RoleEntity
    {
        $model = $this->roleQuery($organizationCode)
            ->where('name', $name)
            ->first();

        return $model ? $this->mapToEntity($model) : null;
    }

    /**
     * queryrolelist.
     */
    public function queries(string $organizationCode, Page $page, ?array $filters = null): array
    {
        $query = $this->roleQuery($organizationCode);
        // default只queryneed展示的role
        $query->where('is_display', 1);

        // applicationfilter条件
        if ($filters) {
            if (isset($filters['name']) && ! empty($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        }

        // gettotal
        $total = $query->count();

        // paginationquery
        $models = $query->orderBy('created_at', 'desc')
            ->forPage($page->getPage(), $page->getPageNum())
            ->get();

        $roles = [];
        foreach ($models as $model) {
            $roles[] = $this->mapToEntity($model);
        }

        return [
            'total' => $total,
            'list' => $roles,
        ];
    }

    /**
     * deleterole.
     */
    public function delete(string $organizationCode, RoleEntity $roleEntity): void
    {
        $model = $this->roleQuery($organizationCode)
            ->where('id', $roleEntity->getId())
            ->first();

        if ($model) {
            $model->delete();
        }
    }

    /**
     * 为role分配user.
     */
    public function assignUsers(string $organizationCode, int $roleId, array $userIds, ?string $assignedBy = null): void
    {
        // getcurrent已分配的userlist
        $existingUserIds = $this->roleUserQuery($organizationCode)
            ->where('role_id', $roleId)
            ->pluck('user_id')
            ->toArray();

        // 计算need添加和移除的user
        $toAdd = array_diff($userIds, $existingUserIds);
        $toRemove = array_diff($existingUserIds, $userIds);

        // 移除不再属于该role的user
        if (! empty($toRemove)) {
            $this->roleUserQuery($organizationCode)
                ->where('role_id', $roleId)
                ->whereIn('user_id', $toRemove)
                ->delete();
        }

        // 插入new关系
        $data = [];
        foreach ($toAdd as $userId) {
            $data[] = [
                'role_id' => $roleId,
                'user_id' => $userId,
                'organization_code' => $organizationCode,
                'assigned_by' => $assignedBy,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($data)) {
            RoleUserModel::insert($data);
        }
    }

    /**
     * 移除roleuser.
     */
    public function removeUsers(string $organizationCode, int $roleId, array $userIds): void
    {
        $this->roleUserQuery($organizationCode)
            ->where('role_id', $roleId)
            ->whereIn('user_id', $userIds)
            ->delete();
    }

    /**
     * getrole的userlist.
     */
    public function getRoleUsers(string $organizationCode, int $roleId): array
    {
        return $this->roleUserQuery($organizationCode)
            ->where('role_id', $roleId)
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * 批量getrole的userlist，return [roleId => userIds[]]。
     */
    public function getRoleUsersMap(string $organizationCode, array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }

        $rows = $this->roleUserQuery($organizationCode)
            ->whereIn('role_id', $roleIds)
            ->get(['role_id', 'user_id'])
            ->toArray();

        $map = [];
        foreach ($rows as $row) {
            $rid = (int) $row['role_id'];
            $map[$rid][] = $row['user_id'];
        }

        // ensure所有 roleIds 都有 key
        foreach ($roleIds as $rid) {
            $map[$rid] = $map[$rid] ?? [];
        }

        return $map;
    }

    /**
     * getuser的rolelist.
     */
    public function getUserRoles(string $organizationCode, string $userId): array
    {
        $roleIds = $this->roleUserQuery($organizationCode)
            ->where('user_id', $userId)
            ->pluck('role_id')
            ->toArray();

        if (empty($roleIds)) {
            return [];
        }

        $models = $this->roleQuery($organizationCode)
            ->whereIn('id', $roleIds)
            ->where('status', RoleModel::STATUS_ENABLED) // 只returnenable的role
            ->get();

        $roles = [];
        foreach ($models as $model) {
            $roles[] = $this->mapToEntity($model);
        }

        return $roles;
    }

    /**
     * getuser的所有permission.
     */
    public function getUserPermissions(string $organizationCode, string $userId): array
    {
        $roles = $this->getUserRoles($organizationCode, $userId);

        $permissions = [];
        foreach ($roles as $role) {
            $permissions = array_merge($permissions, $role->getPermissions());
        }

        return array_unique($permissions);
    }

    /**
     * based onorganizationencodingget RoleModel query构造器.
     */
    private function roleQuery(string $organizationCode)
    {
        return RoleModel::query()->where('organization_code', $organizationCode);
    }

    /**
     * based onorganizationencodingget RoleUserModel query构造器.
     */
    private function roleUserQuery(string $organizationCode)
    {
        return RoleUserModel::query()->where('organization_code', $organizationCode);
    }

    /**
     * mappingmodel到实体.
     */
    private function mapToEntity(RoleModel $model): RoleEntity
    {
        $entity = new RoleEntity();
        $entity->setId($model->id);
        $entity->setName($model->name);
        $entity->setOrganizationCode($model->organization_code);

        // 从modelgetpermissionarray
        $entity->setPermissions($model->getPermissions());

        // getpermissiontag
        $entity->setPermissionTag($model->getPermissionTag());

        // is_display
        $entity->setIsDisplay($model->is_display);

        $entity->setStatus($model->status);
        $entity->setCreatedUid($model->created_uid);
        $entity->setUpdatedUid($model->updated_uid);

        if ($model->created_at) {
            $entity->setCreatedAt(DateTime::createFromInterface($model->created_at));
        }
        if ($model->updated_at) {
            $entity->setUpdatedAt(DateTime::createFromInterface($model->updated_at));
        }

        return $entity;
    }
}

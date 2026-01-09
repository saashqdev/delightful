<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Permission\DTO;

use App\Infrastructure\Core\AbstractDTO;

/**
 * update子管理员role请求DTO.
 */
class UpdateSubAdminRequestDTO extends AbstractDTO
{
    /**
     * rolename（可选，仅在needupdate时提供）.
     */
    public ?string $name = null;

    /**
     * rolestatus：0=禁用, 1=启用（可选，仅在needupdate时提供）.
     */
    public ?int $status = null;

    /**
     * permissiontag，用于前端展示category（可选，仅在needupdate时提供）.
     */
    public ?array $permissionTag = null;

    /**
     * permission键list（可选，仅在needupdate时提供）.
     * 注意：如果提供了此field，将替换所有现有permission.
     */
    public ?array $permissions = null;

    /**
     * userIDlist（可选，仅在needupdate时提供）.
     * 注意：如果提供了此field，将替换所有现有user关联.
     */
    public ?array $userIds = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    public function getPermissionTag(): ?array
    {
        return $this->permissionTag;
    }

    public function setPermissionTag(?array $permissionTag): void
    {
        $this->permissionTag = $permissionTag;
    }

    public function getPermissions(): ?array
    {
        return $this->permissions;
    }

    public function setPermissions(?array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function getUserIds(): ?array
    {
        return $this->userIds;
    }

    public function setUserIds(?array $userIds): void
    {
        $this->userIds = $userIds;
    }

    /**
     * check是否有needupdate的field.
     */
    public function hasUpdates(): bool
    {
        return $this->name !== null
            || $this->status !== null
            || $this->permissionTag !== null
            || $this->permissions !== null
            || $this->userIds !== null;
    }

    /**
     * verifyDTO数据的valid性.
     */
    public function validate(): bool
    {
        // verifyrolename（如果提供）
        if ($this->name !== null) {
            if (empty(trim($this->name))) {
                return false;
            }

            if (strlen($this->name) > 255) {
                return false;
            }
        }

        // verifystatusvalue（如果提供）
        if ($this->status !== null && ! in_array($this->status, [0, 1])) {
            return false;
        }

        // verifypermissionlist（如果提供）
        if ($this->permissions !== null) {
            foreach ($this->permissions as $permission) {
                if (! is_string($permission)) {
                    return false;
                }
            }
        }

        // verifyuserIDlist（如果提供）
        if ($this->userIds !== null) {
            foreach ($this->userIds as $userId) {
                if (! is_string($userId)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * getverifyerrorinfo.
     */
    public function getValidationErrors(): array
    {
        $errors = [];

        if ($this->name !== null) {
            if (empty(trim($this->name))) {
                $errors[] = 'rolename不能为空';
            }

            if (strlen($this->name) > 255) {
                $errors[] = 'rolenamelength不能超过255个字符';
            }
        }

        if ($this->status !== null && ! in_array($this->status, [0, 1])) {
            $errors[] = 'rolestatusvalueinvalid，只能是0或1';
        }

        if ($this->permissions !== null) {
            foreach ($this->permissions as $index => $permission) {
                if (! is_string($permission)) {
                    $errors[] = "permissionlist第{$index}项must是string";
                }
            }
        }

        if ($this->userIds !== null) {
            foreach ($this->userIds as $index => $userId) {
                if (! is_string($userId)) {
                    $errors[] = "userIDlist第{$index}项must是string";
                }
            }
        }

        return $errors;
    }

    /**
     * get所有已set的updatefield.
     */
    public function getUpdateFields(): array
    {
        $fields = [];

        if ($this->name !== null) {
            $fields['name'] = $this->name;
        }

        if ($this->status !== null) {
            $fields['status'] = $this->status;
        }

        if ($this->permissions !== null) {
            $fields['permissions'] = $this->permissions;
        }

        if ($this->userIds !== null) {
            $fields['userIds'] = $this->userIds;
        }

        return $fields;
    }
}

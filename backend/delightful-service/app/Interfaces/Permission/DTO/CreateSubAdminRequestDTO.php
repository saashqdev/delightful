<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Permission\DTO;

use App\Infrastructure\Core\AbstractDTO;

/**
 * create子管理员rolerequestDTO.
 */
class CreateSubAdminRequestDTO extends AbstractDTO
{
    /**
     * rolename（必填）.
     */
    public string $name = '';

    /**
     * rolestatus：0=禁用, 1=启用（default启用）.
     */
    public int $status = 1;

    /**
     * permission键list（可选）.
     */
    public array $permissions = [];

    /**
     * userIDlist（可选）.
     */
    public array $userIds = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function getUserIds(): array
    {
        return $this->userIds;
    }

    public function setUserIds(array $userIds): void
    {
        $this->userIds = $userIds;
    }

    /**
     * verifyDTO数据的valid性.
     */
    public function validate(): bool
    {
        // verifyrolename不能为空
        if (empty(trim($this->name))) {
            return false;
        }

        // verifyrolenamelength不超过255字符
        if (strlen($this->name) > 255) {
            return false;
        }

        // verifystatusvaluevalid性
        if (! in_array($this->status, [0, 1])) {
            return false;
        }

        // verifypermissionlist是否为stringarray
        if (! empty($this->permissions)) {
            foreach ($this->permissions as $permission) {
                if (! is_string($permission)) {
                    return false;
                }
            }
        }

        // verifyuserIDlist是否为stringarray
        if (! empty($this->userIds)) {
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
     * TODO: needconfiguration多语言
     */
    public function getValidationErrors(): array
    {
        $errors = [];

        if (empty(trim($this->name))) {
            $errors[] = 'rolename不能为空';
        }

        if (strlen($this->name) > 255) {
            $errors[] = 'rolenamelength不能超过255个字符';
        }

        if (! in_array($this->status, [0, 1])) {
            $errors[] = 'rolestatusvalueinvalid，只能是0或1';
        }

        if (! empty($this->permissions)) {
            foreach ($this->permissions as $index => $permission) {
                if (! is_string($permission)) {
                    $errors[] = "permissionlist第{$index}项must是string";
                }
            }
        }

        if (! empty($this->userIds)) {
            foreach ($this->userIds as $index => $userId) {
                if (! is_string($userId)) {
                    $errors[] = "userIDlist第{$index}项must是string";
                }
            }
        }

        return $errors;
    }
}

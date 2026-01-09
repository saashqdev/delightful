<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Permission\DTO;

use App\Infrastructure\Core\AbstractDTO;

/**
 * create子administratorrolerequestDTO.
 */
class CreateSubAdminRequestDTO extends AbstractDTO
{
    /**
     * rolename（必填）.
     */
    public string $name = '';

    /**
     * rolestatus：0=disable, 1=enable（defaultenable）.
     */
    public int $status = 1;

    /**
     * permission键list（optional）.
     */
    public array $permissions = [];

    /**
     * userIDlist（optional）.
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
     * verifyDTOdata的validproperty.
     */
    public function validate(): bool
    {
        // verifyrolenamenot能为空
        if (empty(trim($this->name))) {
            return false;
        }

        // verifyrolenamelengthnot超过255字符
        if (strlen($this->name) > 255) {
            return false;
        }

        // verifystatusvaluevalidproperty
        if (! in_array($this->status, [0, 1])) {
            return false;
        }

        // verifypermissionlistwhether为stringarray
        if (! empty($this->permissions)) {
            foreach ($this->permissions as $permission) {
                if (! is_string($permission)) {
                    return false;
                }
            }
        }

        // verifyuserIDlistwhether为stringarray
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
            $errors[] = 'rolenamenot能为空';
        }

        if (strlen($this->name) > 255) {
            $errors[] = 'rolenamelengthnot能超过255字符';
        }

        if (! in_array($this->status, [0, 1])) {
            $errors[] = 'rolestatusvalueinvalid，只能是0or1';
        }

        if (! empty($this->permissions)) {
            foreach ($this->permissions as $index => $permission) {
                if (! is_string($permission)) {
                    $errors[] = "permissionlistthe{$index}itemmust是string";
                }
            }
        }

        if (! empty($this->userIds)) {
            foreach ($this->userIds as $index => $userId) {
                if (! is_string($userId)) {
                    $errors[] = "userIDlistthe{$index}itemmust是string";
                }
            }
        }

        return $errors;
    }
}

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
     * permissionkeylist（optional）.
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
     * verifyDTOdatavalidproperty.
     */
    public function validate(): bool
    {
        // verifyrolenamenotcanforempty
        if (empty(trim($this->name))) {
            return false;
        }

        // verifyrolenamelengthnot超pass255character
        if (strlen($this->name) > 255) {
            return false;
        }

        // verifystatusvaluevalidproperty
        if (! in_array($this->status, [0, 1])) {
            return false;
        }

        // verifypermissionlistwhetherforstringarray
        if (! empty($this->permissions)) {
            foreach ($this->permissions as $permission) {
                if (! is_string($permission)) {
                    return false;
                }
            }
        }

        // verifyuserIDlistwhetherforstringarray
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
     * TODO: needconfiguration多language
     */
    public function getValidationErrors(): array
    {
        $errors = [];

        if (empty(trim($this->name))) {
            $errors[] = 'rolenamenotcanforempty';
        }

        if (strlen($this->name) > 255) {
            $errors[] = 'rolenamelengthnotcan超pass255character';
        }

        if (! in_array($this->status, [0, 1])) {
            $errors[] = 'rolestatusvalueinvalid，onlycanis0or1';
        }

        if (! empty($this->permissions)) {
            foreach ($this->permissions as $index => $permission) {
                if (! is_string($permission)) {
                    $errors[] = "permissionlistthe{$index}itemmustisstring";
                }
            }
        }

        if (! empty($this->userIds)) {
            foreach ($this->userIds as $index => $userId) {
                if (! is_string($userId)) {
                    $errors[] = "userIDlistthe{$index}itemmustisstring";
                }
            }
        }

        return $errors;
    }
}

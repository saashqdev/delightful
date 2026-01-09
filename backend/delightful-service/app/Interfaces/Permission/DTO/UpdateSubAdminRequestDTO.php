<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Permission\DTO;

use App\Infrastructure\Core\AbstractDTO;

/**
 * update子administratorrolerequestDTO.
 */
class UpdateSubAdminRequestDTO extends AbstractDTO
{
    /**
     * rolename（optional，仅inneedupdateo clock提供）.
     */
    public ?string $name = null;

    /**
     * rolestatus：0=disable, 1=enable（optional，仅inneedupdateo clock提供）.
     */
    public ?int $status = null;

    /**
     * permissiontag，useatfront端showcategory（optional，仅inneedupdateo clock提供）.
     */
    public ?array $permissionTag = null;

    /**
     * permissionkeylist（optional，仅inneedupdateo clock提供）.
     * 注意：if提供thisfield，will替换所have现havepermission.
     */
    public ?array $permissions = null;

    /**
     * userIDlist（optional，仅inneedupdateo clock提供）.
     * 注意：if提供thisfield，will替换所have现haveuserassociate.
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
     * checkwhetherhaveneedupdatefield.
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
     * verifyDTOdatavalidproperty.
     */
    public function validate(): bool
    {
        // verifyrolename（if提供）
        if ($this->name !== null) {
            if (empty(trim($this->name))) {
                return false;
            }

            if (strlen($this->name) > 255) {
                return false;
            }
        }

        // verifystatusvalue（if提供）
        if ($this->status !== null && ! in_array($this->status, [0, 1])) {
            return false;
        }

        // verifypermissionlist（if提供）
        if ($this->permissions !== null) {
            foreach ($this->permissions as $permission) {
                if (! is_string($permission)) {
                    return false;
                }
            }
        }

        // verifyuserIDlist（if提供）
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
                $errors[] = 'rolenamenotcanforempty';
            }

            if (strlen($this->name) > 255) {
                $errors[] = 'rolenamelengthnotcan超pass255character';
            }
        }

        if ($this->status !== null && ! in_array($this->status, [0, 1])) {
            $errors[] = 'rolestatusvalueinvalid，只canis0or1';
        }

        if ($this->permissions !== null) {
            foreach ($this->permissions as $index => $permission) {
                if (! is_string($permission)) {
                    $errors[] = "permissionlistthe{$index}itemmustisstring";
                }
            }
        }

        if ($this->userIds !== null) {
            foreach ($this->userIds as $index => $userId) {
                if (! is_string($userId)) {
                    $errors[] = "userIDlistthe{$index}itemmustisstring";
                }
            }
        }

        return $errors;
    }

    /**
     * get所havealreadysetupdatefield.
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

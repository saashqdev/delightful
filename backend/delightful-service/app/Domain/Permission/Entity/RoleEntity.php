<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Permission\Entity;

use App\ErrorCode\PermissionErrorCode;
use App\Infrastructure\Core\AbstractEntity;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use DateTime;

/**
 * RBACrole实body.
 */
class RoleEntity extends AbstractEntity
{
    protected ?int $id = null;

    protected string $name;

    protected string $organizationCode;

    protected ?array $permissionTag = null; // permissiontag，useatfront端showcategory

    /**
     * whetherinfront端column表middleshow：1=is 0=否.
     */
    protected int $isDisplay = 1;

    protected int $status = 1; // status: 0=disable, 1=enable

    protected ?string $createdUid = null;

    protected ?string $updatedUid = null;

    protected ?DateTime $createdAt = null;

    protected ?DateTime $updatedAt = null;

    /**
     * roleassociatepermissionkeycolumn表.
     */
    protected array $permissions = [];

    /**
     * roleassociateuserIDcolumn表.
     */
    protected array $userIds = [];

    public function shouldCreate(): bool
    {
        return empty($this->id);
    }

    public function prepareForCreation($createUid): void
    {
        $this->validate();
        $this->createdUid = $createUid;

        if (empty($this->createdAt)) {
            $this->createdAt = new DateTime();
        }

        if (empty($this->updatedAt)) {
            $this->updatedAt = $this->createdAt;
        }

        if (! empty($this->createdUid) && empty($this->updatedUid)) {
            $this->updatedUid = $this->createdUid;
        }

        $this->id = null;
    }

    public function prepareForModification(): void
    {
        $this->validate();
        $this->updatedAt = new DateTime();
    }

    public function addPermission(string $permissionKey): void
    {
        if (! in_array($permissionKey, $this->permissions)) {
            $this->permissions[] = $permissionKey;
        }
    }

    public function removePermission(string $permissionKey): void
    {
        $index = array_search($permissionKey, $this->permissions);
        if ($index !== false) {
            unset($this->permissions[$index]);
            $this->permissions = array_values($this->permissions); // 重newindex
        }
    }

    public function hasPermission(string $permissionKey): bool
    {
        return in_array($permissionKey, $this->permissions);
    }

    public function setPermissions(array $permissions): void
    {
        $this->permissions = [];
        foreach ($permissions as $permission) {
            $this->addPermission($permission);
        }
    }

    public function hasUser(string $userId): bool
    {
        return in_array($userId, $this->userIds);
    }

    public function isEnabled(): bool
    {
        return $this->status === 1;
    }

    public function enable(): void
    {
        $this->status = 1;
    }

    public function disable(): void
    {
        $this->status = 0;
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
    }

    public function getPermissionTag(): ?array
    {
        return $this->permissionTag;
    }

    public function setPermissionTag(?array $permissionTag): void
    {
        $this->permissionTag = $permissionTag;
    }

    public function getIsDisplay(): int
    {
        return $this->isDisplay;
    }

    public function setIsDisplay(int $isDisplay): void
    {
        $this->isDisplay = $isDisplay;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getCreatedUid(): ?string
    {
        return $this->createdUid;
    }

    public function setCreatedUid(?string $createdUid): void
    {
        $this->createdUid = $createdUid;
    }

    public function getUpdatedUid(): ?string
    {
        return $this->updatedUid;
    }

    public function setUpdatedUid(?string $updatedUid): void
    {
        $this->updatedUid = $updatedUid;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function getUserIds(): array
    {
        return $this->userIds;
    }

    public function setUserIds(array $userIds): void
    {
        $this->userIds = $userIds;
    }

    protected function validate(): void
    {
        if (empty($this->name)) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'common.empty', ['label' => 'role_name']);
        }

        if (empty($this->organizationCode)) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'common.empty', ['label' => 'organization_code']);
        }

        if (strlen($this->name) > 255) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'common.too_long', ['label' => 'role_name', 'max' => 255]);
        }

        if (! in_array($this->status, [0, 1])) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.invalid_status');
        }

        if (! in_array($this->isDisplay, [0, 1])) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'permission.invalid_is_display');
        }
    }
}

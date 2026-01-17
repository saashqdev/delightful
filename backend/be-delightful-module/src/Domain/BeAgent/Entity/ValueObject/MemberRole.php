<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;

use function Hyperf\Translation\trans;

/**
 * Member role value object
 *
 * Encapsulates business logic and permission validation rules for member roles
 */
enum MemberRole: string
{
    case OWNER = 'owner';
    case MANAGE = 'manage';
    case EDITOR = 'editor';
    case VIEWER = 'viewer';

    /**
     * Create instance from string.
     */
    public static function fromString(string $role): self
    {
        return match ($role) {
            'owner' => self::OWNER,
            'manage' => self::MANAGE,
            'editor' => self::EDITOR,
            'viewer' => self::VIEWER,
            default => ExceptionBuilder::throw(BeAgentErrorCode::INVALID_MEMBER_ROLE, trans('project.invalid_member_role'))
        };
    }

    /**
     * Create instance from value.
     */
    public static function fromValue(string $value): self
    {
        return self::from($value);
    }

    /**
     * Get value
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Whether is owner role.
     */
    public function isOwner(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * Whether is manager role.
     */
    public function isManager(): bool
    {
        return $this === self::MANAGE;
    }

    /**
     * Whether is editor role.
     */
    public function isEditor(): bool
    {
        return $this === self::EDITOR;
    }

    /**
     * Whether is viewer role.
     */
    public function isViewer(): bool
    {
        return $this === self::VIEWER;
    }

    /**
     * Whether has write permission.
     */
    public function hasWritePermission(): bool
    {
        return match ($this) {
            self::OWNER, self::MANAGE, self::EDITOR => true,
            self::VIEWER => false,
        };
    }

    /**
     * Whether has delete permission.
     */
    public function hasDeletePermission(): bool
    {
        return $this === self::OWNER;
    }

    /**
     * Whether has manage permission (invite members, set permissions, etc).
     */
    public function hasManagePermission(): bool
    {
        return match ($this) {
            self::OWNER, self::MANAGE => true,
            self::EDITOR, self::VIEWER => false,
        };
    }

    /**
     * Whether has share permission.
     */
    public function hasSharePermission(): bool
    {
        return match ($this) {
            self::OWNER, self::MANAGE, self::EDITOR => true,
            self::VIEWER => false,
        };
    }

    /**
     * Whether can set shortcut.
     */
    public function canSetShortcut(): bool
    {
        return $this->hasSharePermission();
    }

    /**
     * Get permission level (larger numbers mean higher permissions).
     */
    public function getPermissionLevel(): int
    {
        return match ($this) {
            self::VIEWER => 1,
            self::EDITOR => 2,
            self::MANAGE => 3,
            self::OWNER => 4,
        };
    }

    /**
     * Compare role permission level.
     */
    public function isHigherOrEqualThan(self $other): bool
    {
        return $this->getPermissionLevel() >= $other->getPermissionLevel();
    }

    /**
     * Get all available roles.
     */
    public static function getAllRoles(): array
    {
        return [self::OWNER, self::MANAGE, self::EDITOR, self::VIEWER];
    }

    /**
     * Get string values of all roles.
     */
    public static function getAllRoleValues(): array
    {
        return array_map(fn ($role) => $role->value, self::getAllRoles());
    }

    /**
     * Validate permission level.
     */
    public static function validatePermissionLevel(string $permission): MemberRole
    {
        $validPermissions = [
            MemberRole::MANAGE->value,
            MemberRole::EDITOR->value,
            MemberRole::VIEWER->value,
        ];

        if (! in_array($permission, $validPermissions, true)) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVALID_MEMBER_ROLE, trans('project.invalid_member_role'));
        }

        return MemberRole::from($permission);
    }
}

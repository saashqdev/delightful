<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Share\Entity\ValueObject;

use DateTime;

/**
 * Share status value object
 * Represents the current state of a share (active, expired, deleted, etc.).
 */
class ShareStatus
{
    /**
     * Status: Active.
     */
    public const STATUS_ACTIVE = 'active';

    /**
     * Status: Expired
     */
    public const STATUS_EXPIRED = 'expired';

    /**
     * Status: Deleted.
     */
    public const STATUS_DELETED = 'deleted';

    /**
     * Status: Password error.
     */
    public const STATUS_PASSWORD_ERROR = 'password_error';

    /**
     * Status: No access permission.
     */
    public const STATUS_NO_PERMISSION = 'no_permission';

    /**
     * Current status value
     */
    private string $status;

    /**
     * Expiration time.
     */
    private ?DateTime $expireAt;

    /**
     * Deletion time.
     */
    private ?DateTime $deletedAt;

    /**
     * Constructor.
     */
    private function __construct(string $status, ?DateTime $expireAt = null, ?DateTime $deletedAt = null)
    {
        $this->status = $status;
        $this->expireAt = $expireAt;
        $this->deletedAt = $deletedAt;
    }

    /**
     * Convert to string.
     */
    public function __toString(): string
    {
        return $this->status;
    }

    /**
     * Create active status
     */
    public static function active(?DateTime $expireAt = null): self
    {
        return new self(self::STATUS_ACTIVE, $expireAt, null);
    }

    /**
     * Create expired status
     */
    public static function expired(DateTime $expireAt): self
    {
        return new self(self::STATUS_EXPIRED, $expireAt, null);
    }

    /**
     * Create deleted status
     */
    public static function deleted(DateTime $deletedAt): self
    {
        return new self(self::STATUS_DELETED, null, $deletedAt);
    }

    /**
     * Create password error status
     */
    public static function passwordError(): self
    {
        return new self(self::STATUS_PASSWORD_ERROR);
    }

    /**
     * Create no access permission status
     */
    public static function noPermission(): self
    {
        return new self(self::STATUS_NO_PERMISSION);
    }

    /**
     * Get status value
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get expiration time.
     */
    public function getExpireAt(): ?DateTime
    {
        return $this->expireAt;
    }

    /**
     * Get deletion time.
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    /**
     * Check if status is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if status is expired
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Check if status is deleted.
     */
    public function isDeleted(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    /**
     * Check if status is password error.
     */
    public function isPasswordError(): bool
    {
        return $this->status === self::STATUS_PASSWORD_ERROR;
    }

    /**
     * Check if status is no access permission.
     */
    public function isNoPermission(): bool
    {
        return $this->status === self::STATUS_NO_PERMISSION;
    }

    /**
     * Check if share is accessible.
     */
    public function isAccessible(): bool
    {
        return $this->isActive();
    }
}

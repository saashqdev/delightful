<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Share\Entity;

use App\Infrastructure\Core\AbstractEntity;
use DateTime;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Constant\ShareAccessType;

/**
 * Generic resource share entity.
 */
class ResourceShareEntity extends AbstractEntity
{
    /**
     * @var int Primary key ID
     */
    protected int $id = 0;

    /**
     * @var string Resource ID
     */
    protected string $resourceId = '';

    /**
     * @var int Resource type
     */
    protected int $resourceType = 0;

    /**
     * @var string Resource name
     */
    protected string $resourceName = '';

    /**
     * @var string Share code
     */
    protected string $shareCode = '';

    /**
     * @var int Share type
     */
    protected int $shareType = 0;

    /**
     * @var null|string Access password (optional)
     */
    protected ?string $password = null;

    /**
     * @var bool Whether password protection is enabled
     */
    protected bool $isPasswordEnabled = false;

    /**
     * @var null|string Expiration time (optional)
     */
    protected ?string $expireAt = null;

    /**
     * @var int View count
     */
    protected int $viewCount = 0;

    /**
     * @var string Creator user ID
     */
    protected string $createdUid = '';

    /**
     * @var string Updater user ID
     */
    protected string $updatedUid = '';

    /**
     * @var string Organization code
     */
    protected string $organizationCode = '';

    /**
     * @var string Target IDs (used for SpecificTarget type)
     *             Format: [{"type": 1, "id": "123"}, {"type": 2, "id": "456"}]
     *             type: 1-User, 2-Department, 3-Group
     */
    protected string $targetIds = '';

    /**
     * @var null|array Extra attributes (for storing extended information)
     */
    protected ?array $extra = null;

    /**
     * @var bool Whether enabled (dedicated for invitation links)
     */
    protected bool $isEnabled = true;

    /**
     * @var null|string Creation time
     */
    protected ?string $createdAt = null;

    /**
     * @var null|string Update time
     */
    protected ?string $updatedAt = null;

    /**
     * @var null|string Deletion time
     */
    protected ?string $deletedAt = null;

    /**
     * Constructor.
     */
    public function __construct(array $data = [])
    {
        // Default settings
        $this->id = 0; // Set default ID to 0
        $this->viewCount = 0;
        $this->password = null;
        $this->expireAt = null;
        $this->deletedAt = null;
        $this->targetIds = '[]'; // Store as JSON string
        $this->extra = null;
        $this->isEnabled = true; // Enabled by default
        $this->isPasswordEnabled = false; // Disabled by default

        $this->initProperty($data);
    }

    /**
     * Get resource type enum.
     */
    public function getResourceTypeEnum(): ResourceType
    {
        return ResourceType::from($this->resourceType);
    }

    /**
     * Set resource type enum.
     */
    public function setResourceTypeEnum(ResourceType $resourceType): self
    {
        $this->resourceType = $resourceType->value;
        return $this;
    }

    /**
     * Get share type enum.
     */
    public function getShareTypeEnum(): ShareAccessType
    {
        return ShareAccessType::from($this->shareType);
    }

    /**
     * Set share type enum.
     */
    public function setShareTypeEnum(ShareAccessType $shareType): self
    {
        $this->shareType = $shareType->value;
        return $this;
    }

    /**
     * Get target IDs array.
     */
    public function getTargetIdsArray(): array
    {
        return json_decode($this->targetIds, true) ?: [];
    }

    /**
     * Set target IDs array.
     */
    public function setTargetIdsArray(array $targetIds): self
    {
        $this->targetIds = json_encode($targetIds);
        return $this;
    }

    /**
     * Check if share has expired
     */
    public function isExpired(): bool
    {
        if ($this->expireAt === null) {
            return false;
        }

        $expireDateTime = new DateTime($this->expireAt);
        return $expireDateTime < new DateTime();
    }

    /**
     * Check if share has been deleted.
     */
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    /**
     * Check if share is valid.
     */
    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isDeleted();
    }

    /**
     * Increment view count.
     */
    public function incrementViewCount(): void
    {
        ++$this->viewCount;
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $inputPassword): bool
    {
        if (empty($this->password)) {
            return true;
        }

        // In actual applications, should use PasswordHasher for secure password verification
        return $this->password === $inputPassword;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        $result = [
            'id' => $this->id,
            'resource_id' => $this->resourceId,
            'resource_type' => $this->resourceType,
            'resource_name' => $this->resourceName,
            'share_code' => $this->shareCode,
            'share_type' => $this->shareType,
            'password' => $this->password,
            'is_password_enabled' => $this->isPasswordEnabled,
            'expire_at' => $this->expireAt,
            'view_count' => $this->viewCount,
            'created_uid' => $this->createdUid,
            'updated_uid' => $this->updatedUid,
            'organization_code' => $this->organizationCode,
            'target_ids' => $this->targetIds,
            'extra' => $this->extra,
            'is_enabled' => $this->isEnabled,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];

        // Remove null values
        return array_filter($result, function ($value) {
            return $value !== null;
        });
    }

    // Getters and Setters

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getResourceId(): string
    {
        return $this->resourceId;
    }

    public function setResourceId(string $resourceId): self
    {
        $this->resourceId = $resourceId;
        return $this;
    }

    public function getResourceType(): int
    {
        return $this->resourceType;
    }

    public function setResourceType(int $resourceType): self
    {
        $this->resourceType = $resourceType;
        return $this;
    }

    public function getResourceName(): string
    {
        return $this->resourceName;
    }

    public function setResourceName(string $resourceName): self
    {
        $this->resourceName = $resourceName;
        return $this;
    }

    public function getShareCode(): string
    {
        return $this->shareCode;
    }

    public function setShareCode(string $shareCode): self
    {
        $this->shareCode = $shareCode;
        return $this;
    }

    public function getShareType(): int
    {
        return $this->shareType;
    }

    public function setShareType(int $shareType): self
    {
        $this->shareType = $shareType;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getIsPasswordEnabled(): bool
    {
        return $this->isPasswordEnabled;
    }

    public function setIsPasswordEnabled(bool $isPasswordEnabled): self
    {
        $this->isPasswordEnabled = $isPasswordEnabled;
        return $this;
    }

    public function getExpireAt(): ?string
    {
        return $this->expireAt;
    }

    public function setExpireAt(?string $expireAt): self
    {
        $this->expireAt = $expireAt;
        return $this;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): self
    {
        $this->viewCount = $viewCount;
        return $this;
    }

    public function getCreatedUid(): string
    {
        return $this->createdUid;
    }

    public function setCreatedUid(?string $createdUid): self
    {
        $this->createdUid = $createdUid;
        return $this;
    }

    public function getUpdatedUid(): string
    {
        return $this->updatedUid;
    }

    public function setUpdatedUid(string $updatedUid): self
    {
        $this->updatedUid = $updatedUid;
        return $this;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(string $organizationCode): self
    {
        $this->organizationCode = $organizationCode;
        return $this;
    }

    public function getTargetIds(): string
    {
        return $this->targetIds;
    }

    public function setTargetIds(string $targetIds): self
    {
        $this->targetIds = $targetIds;
        return $this;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function setExtra(?array $extra): self
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     * Get specified extra attribute value.
     */
    public function getExtraAttribute(string $key, mixed $default = null): mixed
    {
        return $this->extra[$key] ?? $default;
    }

    /**
     * Set specified extra attribute value.
     */
    public function setExtraAttribute(string $key, mixed $value): self
    {
        if ($this->extra === null) {
            $this->extra = [];
        }
        $this->extra[$key] = $value;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?string $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getIsEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;
        return $this;
    }

    /**
     * Enable share.
     */
    public function enable(): self
    {
        $this->isEnabled = true;
        return $this;
    }

    /**
     * Disable share.
     */
    public function disable(): self
    {
        $this->isEnabled = false;
        return $this;
    }

    /**
     * Generate random numeric password.
     *
     * @param int $length Password length, default 5 digits
     * @return string Generated random password
     */
    public static function generateRandomPassword(int $length = 5): string
    {
        $maxNumber = (int) str_repeat('9', $length);
        return str_pad((string) random_int(0, $maxNumber), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Generate random password (supports custom seed, mainly for testing).
     *
     * @param int $length Password length
     * @param null|int $seed Random seed, null uses system random
     * @return string Generated random password
     */
    public static function generateRandomPasswordWithSeed(int $length = 5, ?int $seed = null): string
    {
        if ($seed !== null) {
            mt_srand($seed);
            $password = '';
            for ($i = 0; $i < $length; ++$i) {
                $password .= (string) mt_rand(0, 9);
            }
            return $password;
        }

        return self::generateRandomPassword($length);
    }
}

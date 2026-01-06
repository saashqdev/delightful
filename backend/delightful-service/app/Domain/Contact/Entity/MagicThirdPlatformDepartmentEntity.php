<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity;

use App\Domain\Contact\Entity\ValueObject\PlatformType;

class DelightfulThirdPlatformDepartmentEntity extends AbstractEntity
{
    protected string $id;

    protected ?string $delightfulDepartmentId = '';

    protected string $delightfulOrganizationCode;

    protected string $thirdLeaderUserId = '';

    protected string $thirdDepartmentId;

    protected ?string $thirdParentDepartmentId;

    protected string $thirdName;

    protected string $thirdI18nName = '';

    protected PlatformType $thirdPlatformType;

    protected string $thirdPlatformDepartmentsExtra;

    protected string $createdAt;

    protected string $updatedAt;

    protected string $path;

    protected int $level;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(int|string $id): void
    {
        if (is_int($id)) {
            $id = (string) $id;
        }
        $this->id = $id;
    }

    public function getDelightfulDepartmentId(): ?string
    {
        return $this->delightfulDepartmentId;
    }

    public function setDelightfulDepartmentId(null|int|string $delightfulDepartmentId): void
    {
        if (is_int($delightfulDepartmentId)) {
            $delightfulDepartmentId = (string) $delightfulDepartmentId;
        }
        $this->delightfulDepartmentId = $delightfulDepartmentId;
    }

    public function getDelightfulOrganizationCode(): string
    {
        return $this->delightfulOrganizationCode;
    }

    public function setDelightfulOrganizationCode(string $delightfulOrganizationCode): void
    {
        $this->delightfulOrganizationCode = $delightfulOrganizationCode;
    }

    public function getThirdLeaderUserId(): string
    {
        return $this->thirdLeaderUserId;
    }

    public function setThirdLeaderUserId(string $thirdLeaderUserId): void
    {
        $this->thirdLeaderUserId = $thirdLeaderUserId;
    }

    public function getThirdDepartmentId(): string
    {
        return $this->thirdDepartmentId;
    }

    public function setThirdDepartmentId(int|string $thirdDepartmentId): void
    {
        if (is_int($thirdDepartmentId)) {
            $thirdDepartmentId = (string) $thirdDepartmentId;
        }
        $this->thirdDepartmentId = $thirdDepartmentId;
    }

    public function getThirdParentDepartmentId(): ?string
    {
        return $this->thirdParentDepartmentId;
    }

    public function setThirdParentDepartmentId(null|int|string $thirdParentDepartmentId): void
    {
        if (is_int($thirdParentDepartmentId)) {
            $thirdParentDepartmentId = (string) $thirdParentDepartmentId;
        }
        $this->thirdParentDepartmentId = $thirdParentDepartmentId;
    }

    public function getThirdName(): string
    {
        return $this->thirdName;
    }

    public function setThirdName(string $thirdName): void
    {
        $this->thirdName = $thirdName;
    }

    public function getThirdI18nName(): string
    {
        return $this->thirdI18nName;
    }

    public function setThirdI18nName(string $thirdI18nName): void
    {
        $this->thirdI18nName = $thirdI18nName;
    }

    public function getThirdPlatformType(): PlatformType
    {
        return $this->thirdPlatformType;
    }

    public function setThirdPlatformType(PlatformType|string $thirdPlatformType): void
    {
        if (is_string($thirdPlatformType)) {
            $thirdPlatformType = PlatformType::tryFrom($thirdPlatformType);
        }
        $this->thirdPlatformType = $thirdPlatformType;
    }

    public function getThirdPlatformDepartmentsExtra(): string
    {
        return $this->thirdPlatformDepartmentsExtra;
    }

    public function setThirdPlatformDepartmentsExtra(string $thirdPlatformDepartmentsExtra): void
    {
        $this->thirdPlatformDepartmentsExtra = $thirdPlatformDepartmentsExtra;
    }
}

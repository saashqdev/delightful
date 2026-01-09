<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\File\Entity;

class DefaultFileEntity extends AbstractEntity
{
    public int $id;

    public string $businessType;

    public int $fileType;

    public string $key;

    public int $fileSize;

    public string $organization;

    public string $fileExtension;

    public string $userId;

    public ?string $createdAt;

    public ?string $updatedAt;

    public ?string $deletedAt;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * setID.
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * set业务type.
     */
    public function setBusinessType(string $businessType): self
    {
        $this->businessType = $businessType;
        return $this;
    }

    /**
     * get文件type.
     */
    public function getFileType(): int
    {
        return $this->fileType;
    }

    /**
     * set文件type.
     */
    public function setFileType(int $fileType): self
    {
        $this->fileType = $fileType;
        return $this;
    }

    /**
     * get文件key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * set文件key.
     */
    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    /**
     * get文件大小.
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * set文件大小.
     */
    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    /**
     * getorganization编码
     */
    public function getOrganization(): string
    {
        return $this->organization;
    }

    /**
     * setorganization编码
     */
    public function setOrganization(string $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     * get文件后缀
     */
    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    /**
     * set文件后缀
     */
    public function setFileExtension(string $fileExtension): self
    {
        $this->fileExtension = $fileExtension;
        return $this;
    }

    /**
     * get上传者ID.
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * set上传者ID.
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * getcreatetime.
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * setcreatetime.
     */
    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * getupdatetime.
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * setupdatetime.
     */
    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * getdeletetime.
     */
    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    /**
     * setdeletetime.
     */
    public function setDeletedAt(?string $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }
}

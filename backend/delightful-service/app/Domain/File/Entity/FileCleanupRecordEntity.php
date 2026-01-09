<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\File\Entity;

class FileCleanupRecordEntity extends AbstractEntity
{
    public int $id;

    public string $organizationCode;

    public string $fileKey;

    public string $fileName;

    public int $fileSize;

    public string $bucketType;

    public string $sourceType;

    public ?string $sourceId;

    public string $expireAt;

    public int $status;

    public int $retryCount;

    public ?string $errorMessage;

    public ?string $createdAt;

    public ?string $updatedAt;

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
     * getorganization编码.
     */
    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    /**
     * setorganization编码.
     */
    public function setOrganizationCode(string $organizationCode): self
    {
        $this->organizationCode = $organizationCode;
        return $this;
    }

    /**
     * getfilekey.
     */
    public function getFileKey(): string
    {
        return $this->fileKey;
    }

    /**
     * setfilekey.
     */
    public function setFileKey(string $fileKey): self
    {
        $this->fileKey = $fileKey;
        return $this;
    }

    /**
     * getfilename.
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * setfilename.
     */
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * getfile大小.
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * setfile大小.
     */
    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    /**
     * get存储桶type.
     */
    public function getBucketType(): string
    {
        return $this->bucketType;
    }

    /**
     * set存储桶type.
     */
    public function setBucketType(string $bucketType): self
    {
        $this->bucketType = $bucketType;
        return $this;
    }

    /**
     * get来源type.
     */
    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    /**
     * set来源type.
     */
    public function setSourceType(string $sourceType): self
    {
        $this->sourceType = $sourceType;
        return $this;
    }

    /**
     * get来源ID.
     */
    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    /**
     * set来源ID.
     */
    public function setSourceId(?string $sourceId): self
    {
        $this->sourceId = $sourceId;
        return $this;
    }

    /**
     * get过期time.
     */
    public function getExpireAt(): string
    {
        return $this->expireAt;
    }

    /**
     * set过期time.
     */
    public function setExpireAt(string $expireAt): self
    {
        $this->expireAt = $expireAt;
        return $this;
    }

    /**
     * getstatus.
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * setstatus.
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * get重试次数.
     */
    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    /**
     * set重试次数.
     */
    public function setRetryCount(int $retryCount): self
    {
        $this->retryCount = $retryCount;
        return $this;
    }

    /**
     * geterrorinfo.
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * seterrorinfo.
     */
    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
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
     * check是否已过期.
     */
    public function isExpired(): bool
    {
        return strtotime($this->expireAt) <= time();
    }

    /**
     * check是否待清理status.
     */
    public function isPending(): bool
    {
        return $this->status === 0;
    }

    /**
     * check是否已清理.
     */
    public function isCleaned(): bool
    {
        return $this->status === 1;
    }

    /**
     * check是否清理fail.
     */
    public function isFailed(): bool
    {
        return $this->status === 2;
    }

    /**
     * check是否可以重试.
     */
    public function canRetry(int $maxRetries = 3): bool
    {
        return $this->retryCount < $maxRetries;
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\FileType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskFileSource;
use JsonSerializable;

/**
 * Save project file request DTO.
 */
class SaveProjectFileRequestDTO implements JsonSerializable
{
    /**
     * Project ID (optional).
     */
    private ?string $projectId = null;

    /**
     * Topic ID (optional).
     */
    private string $topicId = '';

    /**
     * Task ID (optional).
     */
    private string $taskId = '';

    /**
     * File key (path in OSS).
     */
    private string $fileKey = '';

    /**
     * File name.
     */
    private string $fileName = '';

    /**
     * File size (bytes).
     */
    private int $fileSize = 0;

    /**
     * File type (optional, default is user_upload).
     */
    private string $fileType = FileType::USER_UPLOAD->value;

    /**
     * Is directory (optional, default is false).
     */
    private bool $isDirectory = false;

    /**
     * Sort order (optional, default is 0).
     */
    private int $sort = 0;

    /**
     * Parent ID (optional, default is null).
     */
    private string $parentId = '';

    /**
     * Storage type (optional, default is empty string).
     */
    private string $storageType = StorageType::WORKSPACE->value;

    /**
     * Previous file ID, used to specify insertion position, 0=first position, -1=last position (default).
     */
    private string $preFileId = '-1';

    /**
     * Source field.
     */
    private int $source = 0;

    /**
     * Create DTO from request data.
     */
    public static function fromRequest(array $data): self
    {
        $instance = new self();

        $instance->projectId = $data['project_id'] ?? null;
        $instance->topicId = $data['topic_id'] ?? '';
        $instance->taskId = $data['task_id'] ?? '';
        $instance->source = (int) ($data['source'] ?? 0);
        $instance->fileKey = $data['file_key'] ?? '';
        $instance->fileName = $data['file_name'] ?? '';
        $instance->fileSize = (int) ($data['file_size'] ?? 0);
        $instance->fileType = $data['file_type'] ?? FileType::USER_UPLOAD->value;
        $instance->isDirectory = (bool) ($data['is_directory'] ?? false);
        $instance->sort = (int) ($data['sort'] ?? 0);
        $instance->parentId = isset($data['parent_id']) ? $data['parent_id'] : '';
        $instance->storageType = $data['storage_type'] ?? StorageType::WORKSPACE;
        $instance->preFileId = $data['pre_file_id'] ?? '-1';

        return $instance;
    }

    public function getProjectId(): ?string
    {
        return $this->projectId;
    }

    public function setProjectId(?string $projectId): self
    {
        $this->projectId = $projectId;
        return $this;
    }

    public function getTopicId(): string
    {
        return $this->topicId;
    }

    public function setTopicId(string $topicId): self
    {
        $this->topicId = $topicId;
        return $this;
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function setTaskId(string $taskId): self
    {
        $this->taskId = $taskId;
        return $this;
    }

    public function getFileKey(): string
    {
        return $this->fileKey;
    }

    public function setFileKey(string $fileKey): self
    {
        $this->fileKey = $fileKey;
        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): self
    {
        $this->fileType = $fileType;
        return $this;
    }

    public function getIsDirectory(): bool
    {
        return $this->isDirectory;
    }

    public function setIsDirectory(bool $isDirectory): self
    {
        $this->isDirectory = $isDirectory;
        return $this;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): self
    {
        $this->sort = $sort;
        return $this;
    }

    public function getParentId(): string
    {
        return $this->parentId;
    }

    public function setParentId(string $parentId): self
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function getStorageType(): string
    {
        return $this->storageType;
    }

    public function setStorageType(string $storageType): self
    {
        $this->storageType = $storageType;
        return $this;
    }

    public function getPreFileId(): string
    {
        return $this->preFileId;
    }

    public function setPreFileId(string $preFileId): self
    {
        $this->preFileId = $preFileId;
        return $this;
    }

    public function getSource(): int
    {
        return $this->source;
    }

    public function setSource(int $source): self
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Convert to TaskFileEntity.
     */
    public function toEntity(): TaskFileEntity
    {
        $taskFileEntity = new TaskFileEntity();
        $taskFileEntity->setFileKey($this->fileKey);
        $taskFileEntity->setFileName($this->fileName);
        $taskFileEntity->setFileSize($this->fileSize);
        $taskFileEntity->setFileType($this->fileType);
        $taskFileEntity->setSource($this->source);

        // Set project ID (if exists)
        if (! empty($this->projectId)) {
            $taskFileEntity->setProjectId((int) $this->projectId);
        }
        $taskFileEntity->setSort($this->sort);
        $taskFileEntity->setParentId(! empty($this->parentId) ? (int) $this->parentId : 0);
        $taskFileEntity->setIsDirectory($this->isDirectory);

        // Set storage type
        if (! empty($this->storageType)) {
            $taskFileEntity->setStorageType($this->storageType);
        } else {
            $taskFileEntity->setStorageType(StorageType::WORKSPACE);
        }

        if (! empty($this->source)) {
            $taskFileEntity->setSource($this->source);
        } else {
            $taskFileEntity->setSource(TaskFileSource::DEFAULT);
        }

        $taskFileEntity->setIsHidden(false);

        return $taskFileEntity;
    }

    /**
     * Implement JsonSerializable interface.
     */
    public function jsonSerialize(): array
    {
        return [
            'project_id' => $this->projectId,
            'topic_id' => $this->topicId,
            'task_id' => $this->taskId,
            'source' => $this->source,
            'file_key' => $this->fileKey,
            'file_name' => $this->fileName,
            'file_size' => $this->fileSize,
            'file_type' => $this->fileType,
            'is_directory' => $this->isDirectory,
            'sort' => $this->sort,
            'parent_id' => $this->parentId,
            'storage_type' => $this->storageType,
            'pre_file_id' => $this->preFileId,
        ];
    }
}

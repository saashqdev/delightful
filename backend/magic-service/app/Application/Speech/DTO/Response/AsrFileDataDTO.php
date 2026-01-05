<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO\Response;

use Delightful\SuperMagic\Domain\SuperAgent\Entity\TaskFileEntity;

/**
 * ASR 文件数据传输对象
 * 用于在聊天消息中引用文件.
 */
readonly class AsrFileDataDTO
{
    public function __construct(
        public int $fileId,
        public string $fileName,
        public string $filePath,
        public int $fileSize,
        public string $fileExtension,
        public int $projectId
    ) {
    }

    /**
     * 从 TaskFileEntity 创建 DTO.
     *
     * @param TaskFileEntity $fileEntity 任务文件实体
     * @param string $workspaceRelativePath 工作区相对路径
     */
    public static function fromTaskFileEntity(TaskFileEntity $fileEntity, string $workspaceRelativePath): self
    {
        return new self(
            fileId: $fileEntity->getFileId(),
            fileName: $fileEntity->getFileName(),
            filePath: $workspaceRelativePath,
            fileSize: $fileEntity->getFileSize(),
            fileExtension: $fileEntity->getFileExtension(),
            projectId: $fileEntity->getProjectId()
        );
    }

    /**
     * 转换为数组格式，用于聊天消息.
     */
    public function toArray(): array
    {
        return [
            'file_id' => (string) $this->fileId,
            'file_name' => $this->fileName,
            'file_path' => $this->filePath,
            'file_size' => $this->fileSize,
            'file_extension' => $this->fileExtension,
            'project_id' => (string) $this->projectId,
        ];
    }
}

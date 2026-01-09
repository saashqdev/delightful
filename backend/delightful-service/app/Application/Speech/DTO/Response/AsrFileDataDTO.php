<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO\Response;

use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;

/**
 * ASR filedata传输object
 * 用于在chatmessage中quotefile.
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
     * 从 TaskFileEntity create DTO.
     *
     * @param TaskFileEntity $fileEntity taskfile实体
     * @param string $workspaceRelativePath 工作区相对path
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
     * convert为arrayformat，用于chatmessage.
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

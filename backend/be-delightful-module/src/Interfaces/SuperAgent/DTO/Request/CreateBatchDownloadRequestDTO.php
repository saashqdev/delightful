<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\Exception\ExceptionBuilder;
use BeDelightful\BeDelightful\ErrorCode\BeAgentErrorCode;

class CreateBatchDownloadRequestDTO
{
    /**
     * @var array File ID array
     */
    private array $fileIds = [];

    /**
     * @var string Project ID
     */
    private string $projectId = '';

    /**
     * Get file ID array.
     */
    public function getFileIds(): array
    {
        return $this->fileIds;
    }

    /**
     * Set file ID array.
     */
    public function setFileIds(array $fileIds): self
    {
        $this->fileIds = $fileIds;
        return $this;
    }

    /**
     * Get project ID.
     */
    public function getProjectId(): string
    {
        return $this->projectId;
    }

    /**
     * Set project ID.
     */
    public function setProjectId(string $projectId): self
    {
        $this->projectId = $projectId;
        return $this;
    }

    /**
     * Create DTO from request data.
     *
     * @param array $requestData Request data
     */
    public static function fromRequest(array $requestData): self
    {
        $dto = new self();
        $fileIds = $requestData['file_ids'] ?? [];
        $projectId = $requestData['project_id'] ?? '';

        // Validation for project_id
        if (! is_string($projectId)) {
            ExceptionBuilder::throw(BeAgentErrorCode::VALIDATE_FAILED);
        }

        // Validation for file_ids
        if (! is_array($fileIds)) {
            ExceptionBuilder::throw(BeAgentErrorCode::BATCH_FILE_IDS_INVALID);
        }

        // Either file_ids or topic_id must be provided
        if (empty($fileIds) && empty($projectId)) {
            ExceptionBuilder::throw(BeAgentErrorCode::BATCH_FILE_IDS_OR_TOPIC_ID_REQUIRED);
        }

        // If file_ids is provided, validate it
        if (! empty($fileIds)) {
            if (count($fileIds) > 1000) {
                ExceptionBuilder::throw(BeAgentErrorCode::BATCH_TOO_MANY_FILES);
            }

            foreach ($fileIds as $fileId) {
                if (empty($fileId) || ! is_string($fileId)) {
                    ExceptionBuilder::throw(BeAgentErrorCode::BATCH_FILE_IDS_INVALID);
                }
            }
        }

        $dto->setFileIds($fileIds);
        $dto->setProjectId($projectId);
        return $dto;
    }
}

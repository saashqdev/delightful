<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO;

/**
 * ASR总结requestDTO
 * save总结request的所有必传和optionalparameter.
 */
readonly class SummaryRequestDTO
{
    public function __construct(
        public string $taskKey,
        public string $projectId,
        public string $topicId,
        public string $modelId,
        public ?string $fileId = null,
        public ?NoteDTO $note = null,
        public ?string $asrStreamContent = null,
        public ?string $generatedTitle = null
    ) {
    }

    /**
     * 是否有fileID（场景二：直接upload已有audiofile）.
     */
    public function hasFileId(): bool
    {
        return ! empty($this->fileId);
    }
}

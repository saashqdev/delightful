<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO;

/**
 * ASR总结请求DTO
 * 保存总结请求的所有必传和可选parameter.
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
     * 是否有文件ID（场景二：直接upload已有音频文件）.
     */
    public function hasFileId(): bool
    {
        return ! empty($this->fileId);
    }
}

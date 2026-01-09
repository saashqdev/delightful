<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO;

/**
 * ASRsummaryrequestDTO
 * savesummaryrequest所have必传andoptionalparameter.
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
     * whetherhavefileID(scenariotwo:directlyuploadalreadyhaveaudiofile).
     */
    public function hasFileId(): bool
    {
        return ! empty($this->fileId);
    }
}

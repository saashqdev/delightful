<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\DTO;

readonly class ProcessSummaryTaskDTO
{
    public function __construct(
        public AsrTaskStatusDTO $taskStatus,
        public string $organizationCode,
        public string $projectId,
        public string $userId,
        public string $topicId, // SuperAgent话题ID
        public string $chatTopicId, // Chat话题ID
        public string $conversationId,
        public string $modelId
    ) {
    }
}

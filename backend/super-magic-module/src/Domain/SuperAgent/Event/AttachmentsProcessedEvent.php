<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperMagic\Domain\SuperAgent\Event;

class AttachmentsProcessedEvent
{
    public function __construct(
        public int $parentFileId,
        public int $projectId,
        public int $taskId = 0
    ) {
    }
}

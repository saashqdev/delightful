<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;

/**
 * 文件批量删除事件.
 */
class FilesBatchDeletedEvent extends AbstractEvent
{
    public function __construct(
        private readonly int $projectId,
        private readonly array $fileIds,
        private readonly DelightfulUserAuthorization $userAuthorization
    ) {
        parent::__construct();
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getFileIds(): array
    {
        return $this->fileIds;
    }

    public function getUserAuthorization(): DelightfulUserAuthorization
    {
        return $this->userAuthorization;
    }
}

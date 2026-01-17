<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use App\Interfaces\Authorization\Web\MagicUserAuthorization;

/**
 * 文件批量删除事件.
 */
class FilesBatchDeletedEvent extends AbstractEvent
{
    public function __construct(
        private readonly int $projectId,
        private readonly array $fileIds,
        private readonly MagicUserAuthorization $userAuthorization
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

    public function getUserAuthorization(): MagicUserAuthorization
    {
        return $this->userAuthorization;
    }
}

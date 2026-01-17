<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;

/**
 * 文件已上传事件.
 */
class FileUploadedEvent extends AbstractEvent
{
    public function __construct(
        private readonly TaskFileEntity $fileEntity,
        private readonly string $userId,
        private readonly string $organizationCode,
    ) {
        parent::__construct();
    }

    public function getFileEntity(): TaskFileEntity
    {
        return $this->fileEntity;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }
}

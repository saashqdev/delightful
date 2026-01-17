<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;

/**
 * 文件已移动事件.
 */
class FileMovedEvent extends AbstractEvent
{
    public function __construct(
        private readonly TaskFileEntity $fileEntity,
        private readonly DelightfulUserAuthorization $userAuthorization
    ) {
        parent::__construct();
    }

    public function getFileEntity(): TaskFileEntity
    {
        return $this->fileEntity;
    }

    public function getUserAuthorization(): DelightfulUserAuthorization
    {
        return $this->userAuthorization;
    }
}

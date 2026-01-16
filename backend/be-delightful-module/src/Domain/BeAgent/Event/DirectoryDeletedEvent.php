<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Domain\SuperAgent\Event;

use App\Interfaces\Authorization\Web\MagicUserAuthorization;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskFileEntity;

/**
 * 目录已删除事件.
 */
class DirectoryDeletedEvent extends AbstractEvent
{
    public function __construct(
        private readonly TaskFileEntity $directoryEntity,
        private readonly MagicUserAuthorization $userAuthorization
    ) {
        parent::__construct();
    }

    public function getDirectoryEntity(): TaskFileEntity
    {
        return $this->directoryEntity;
    }

    public function getUserAuthorization(): MagicUserAuthorization
    {
        return $this->userAuthorization;
    }
}

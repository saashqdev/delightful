<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use App\Interfaces\Authorization\Web\MagicUserAuthorization;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectEntity;

/**
 * 项目已创建事件.
 */
class ProjectCreatedEvent extends AbstractEvent
{
    public function __construct(
        private readonly ProjectEntity $projectEntity,
        private readonly MagicUserAuthorization $userAuthorization
    ) {
        parent::__construct();
    }

    public function getProjectEntity(): ProjectEntity
    {
        return $this->projectEntity;
    }

    public function getUserAuthorization(): MagicUserAuthorization
    {
        return $this->userAuthorization;
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectEntity;

/**
 * 项目成员已更新事件.
 */
class ProjectMembersUpdatedEvent extends AbstractEvent
{
    /**
     * @param array $currentMembers 当前成员列表
     */
    public function __construct(
        private readonly ProjectEntity $projectEntity,
        private readonly array $currentMembers,
        private readonly DelightfulUserAuthorization $userAuthorization
    ) {
        parent::__construct();
    }

    public function getProjectEntity(): ProjectEntity
    {
        return $this->projectEntity;
    }

    public function getCurrentMembers(): array
    {
        return $this->currentMembers;
    }

    public function getUserAuthorization(): DelightfulUserAuthorization
    {
        return $this->userAuthorization;
    }
}

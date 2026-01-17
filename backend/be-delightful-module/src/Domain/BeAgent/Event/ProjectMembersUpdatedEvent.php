<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectEntity;

/**
 * Project members updated event.
 */
class ProjectMembersUpdatedEvent extends AbstractEvent
{
    /**
     * @param array $currentMembers Current members list
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

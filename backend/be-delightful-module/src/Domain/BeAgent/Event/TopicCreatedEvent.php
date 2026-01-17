<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;

/**
 * Topic created event.
 */
class TopicCreatedEvent extends AbstractEvent
{
    public function __construct(
        private readonly TopicEntity $topicEntity,
        private readonly DelightfulUserAuthorization $userAuthorization
    ) {
        parent::__construct();
    }

    public function getTopicEntity(): TopicEntity
    {
        return $this->topicEntity;
    }

    public function getUserAuthorization(): DelightfulUserAuthorization
    {
        return $this->userAuthorization;
    }
}

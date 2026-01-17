<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

use Delightful\BeDelightful\Domain\BeAgent\Entity\MessageQueueEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;

/**
 * Message Queue Created Event.
 */
class MessageQueueCreatedEvent extends AbstractEvent
{
    public function __construct(
        private readonly MessageQueueEntity $messageQueueEntity,
        private readonly TopicEntity $topicEntity,
        private readonly string $userId,
        private readonly string $organizationCode,
    ) {
        parent::__construct();
    }

    public function getMessageQueueEntity(): MessageQueueEntity
    {
        return $this->messageQueueEntity;
    }

    public function getTopicEntity(): TopicEntity
    {
        return $this->topicEntity;
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

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\MessageQueueEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;

/**
 * Message Queue Consumed Event.
 */
class MessageQueueConsumedEvent extends AbstractEvent
{
    public function __construct(
        private readonly MessageQueueEntity $messageQueueEntity,
        private readonly TopicEntity $topicEntity,
        private readonly bool $success,
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

    public function isSuccess(): bool
    {
        return $this->success;
    }
}

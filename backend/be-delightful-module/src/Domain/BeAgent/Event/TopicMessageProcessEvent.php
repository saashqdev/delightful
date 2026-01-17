<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Event;

/**
 * Topic message process event (lightweight).
 * Only notifies that there is a message to process, does not pass specific message content.
 */
class TopicMessageProcessEvent extends AbstractEvent
{
    /**
     * Constructor.
     *
     * @param int $topicId Topic ID
     * @param int $taskId Task ID
     */
    public function __construct(
        private readonly int $topicId,
        private readonly int $taskId = 0
    ) {
        // Call parent constructor to generate snowflake ID
        parent::__construct();
    }

    /**
     * Create event from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            topicId: (int) ($data['topic_id'] ?? 0),
            taskId: (int) ($data['task_id'] ?? 0)
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'topic_id' => $this->topicId,
            'task_id' => $this->taskId,
            'event_id' => $this->getEventId(),
        ];
    }

    /**
     * Get topic ID.
     */
    public function getTopicId(): int
    {
        return $this->topicId;
    }

    /**
     * Get task ID.
     */
    public function getTaskId(): int
    {
        return $this->taskId;
    }
}

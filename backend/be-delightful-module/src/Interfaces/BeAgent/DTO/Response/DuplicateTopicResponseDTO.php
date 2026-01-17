<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

/**
 * Topic duplication response DTO.
 */
class DuplicateTopicResponseDTO
{
    /**
     * Task key.
     */
    protected string $taskKey;

    /**
     * Task status
     */
    protected string $status;

    /**
     * New topic ID.
     */
    protected ?string $topicId;

    /**
     * Status message.
     */
    protected string $message;

    /**
     * Constructor.
     */
    public function __construct(
        string $taskKey,
        string $status,
        ?string $topicId = null,
        string $message = ''
    ) {
        $this->taskKey = $taskKey;
        $this->status = $status;
        $this->topicId = $topicId;
        $this->message = $message;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'task_key' => $this->taskKey,
            'status' => $this->status,
            'topic_id' => $this->topicId,
            'message' => $this->message,
        ];
    }

    /**
     * Get task key.
     */
    public function getTaskKey(): string
    {
        return $this->taskKey;
    }

    /**
     * Set task key.
     */
    public function setTaskKey(string $taskKey): self
    {
        $this->taskKey = $taskKey;
        return $this;
    }

    /**
     * Get task status
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set task status
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get new topic ID.
     */
    public function getTopicId(): ?string
    {
        return $this->topicId;
    }

    /**
     * Set new topic ID.
     */
    public function setTopicId(?string $topicId): self
    {
        $this->topicId = $topicId;
        return $this;
    }

    /**
     * Get status message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Set status message.
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }
}

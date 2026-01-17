<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Workspace creation parameters value object.
 * Follows DDD value object specifications, encapsulating related parameters and ensuring immutability.
 */
readonly class WorkspaceCreationParams
{
    /**
     * @param string $chatConversationId Conversation ID
     * @param string $workspaceName Workspace name
     * @param string $chatConversationTopicId Conversation topic ID
     * @param string $topicName Topic name
     */
    public function __construct(
        private string $chatConversationId,
        private string $workspaceName,
        private string $chatConversationTopicId,
        private string $topicName
    ) {
    }

    /**
     * Get conversation ID.
     */
    public function getChatConversationId(): string
    {
        return $this->chatConversationId;
    }

    /**
     * Get workspace name.
     */
    public function getWorkspaceName(): string
    {
        return $this->workspaceName;
    }

    /**
     * Get topic ID.
     */
    public function getChatConversationTopicId(): string
    {
        return $this->chatConversationTopicId;
    }

    /**
     * Get topic name.
     */
    public function getTopicName(): string
    {
        return $this->topicName;
    }

    /**
     * Create a new instance with specified properties modified.
     * Due to value object immutability, we create a new instance rather than modifying the existing one.
     *
     * @param array $params Properties and values to modify
     * @return self New instance
     */
    public function with(array $params): self
    {
        return new self(
            $params['chatConversationId'] ?? $this->chatConversationId,
            $params['workspaceName'] ?? $this->workspaceName,
            $params['chatConversationTopicId'] ?? $this->chatConversationTopicId,
            $params['topicName'] ?? $this->topicName
        );
    }
}

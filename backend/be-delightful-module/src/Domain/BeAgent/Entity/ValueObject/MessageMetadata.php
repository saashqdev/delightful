<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

/**
 * Message metadata value object.
 */
class MessageMetadata
{
    private ?UserInfoValueObject $userInfo = null;

    /**
     * Constructor.
     *
     * @param string $agentUserId Agent user ID
     * @param string $userId User ID
     * @param string $organizationCode Organization code
     * @param string $chatConversationId Chat conversation ID
     * @param string $chatTopicId Chat topic ID
     * @param string $topicId Topic ID
     * @param string $instruction Instruction
     * @param string $sandboxId Sandbox ID
     * @param string $beDelightfulTaskId Super assistant task ID
     * @param string $workspaceId Workspace ID
     * @param string $projectId Project ID
     * @param string $language User language
     * @param null|UserInfoValueObject $userInfo User information object
     * @param bool $skipInitMessages Whether to skip initialization messages
     */
    public function __construct(
        private string $agentUserId = '',
        private string $userId = '',
        private string $organizationCode = '',
        private string $chatConversationId = '',
        private string $chatTopicId = '',
        private string $topicId = '',
        private string $instruction = '',
        private string $sandboxId = '',
        private string $beDelightfulTaskId = '',
        private string $workspaceId = '',
        private string $projectId = '',
        private string $language = '',
        ?UserInfoValueObject $userInfo = null,
        private bool $skipInitMessages = false
    ) {
        $this->userInfo = $userInfo;
    }

    /**
     * Create metadata object from array.
     *
     * @param array $data Metadata array
     */
    public static function fromArray(array $data): self
    {
        $userInfo = null;
        if (isset($data['user']) && is_array($data['user'])) {
            $userInfo = UserInfoValueObject::fromArray($data['user']);
        }

        return new self(
            $data['agent_user_id'] ?? '',
            $data['user_id'] ?? '',
            $data['organization_code'] ?? '',
            $data['chat_conversation_id'] ?? '',
            $data['chat_topic_id'] ?? '',
            $data['topic_id'] ?? '',
            $data['instruction'] ?? '',
            $data['sandbox_id'] ?? '',
            $data['be_delightful_task_id'] ?? '',
            $data['workspace_id'] ?? '',
            $data['project_id'] ?? '',
            $data['language'] ?? '',
            $userInfo,
            $data['skip_init_messages'] ?? false
        );
    }

    /**
     * Convert to array.
     *
     * @return array Metadata array
     */
    public function toArray(): array
    {
        $result = [
            'agent_user_id' => $this->agentUserId,
            'user_id' => $this->userId,
            'organization_code' => $this->organizationCode,
            'chat_conversation_id' => $this->chatConversationId,
            'chat_topic_id' => $this->chatTopicId,
            'topic_id' => $this->topicId,
            'instruction' => $this->instruction,
            'sandbox_id' => $this->sandboxId,
            'be_delightful_task_id' => $this->beDelightfulTaskId,
            'workspace_id' => $this->workspaceId,
            'project_id' => $this->projectId,
            'language' => $this->language,
            'skip_init_messages' => $this->skipInitMessages,
        ];

        // Add user information (if exists)
        if ($this->userInfo !== null) {
            $result['user'] = $this->userInfo->toArray();
        }

        return $result;
    }

    // Getters
    public function getAgentUserId(): string
    {
        return $this->agentUserId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getOrganizationCode(): string
    {
        return $this->organizationCode;
    }

    public function getChatConversationId(): string
    {
        return $this->chatConversationId;
    }

    public function getChatTopicId(): string
    {
        return $this->chatTopicId;
    }

    public function getTopicId(): string
    {
        return $this->topicId;
    }

    public function getInstruction(): string
    {
        return $this->instruction;
    }

    public function getSandboxId(): string
    {
        return $this->sandboxId;
    }

    public function getBeDelightfulTaskId(): string
    {
        return $this->beDelightfulTaskId;
    }

    public function getWorkspaceId(): string
    {
        return $this->workspaceId;
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Get user information.
     *
     * @return null|UserInfoValueObject User information object
     */
    public function getUserInfo(): ?UserInfoValueObject
    {
        return $this->userInfo;
    }

    // Withers for immutability
    public function withAgentUserId(string $agentUserId): self
    {
        $clone = clone $this;
        $clone->agentUserId = $agentUserId;
        return $clone;
    }

    public function withUserId(string $userId): self
    {
        $clone = clone $this;
        $clone->userId = $userId;
        return $clone;
    }

    public function withOrganizationCode(string $organizationCode): self
    {
        $clone = clone $this;
        $clone->organizationCode = $organizationCode;
        return $clone;
    }

    public function withChatConversationId(string $chatConversationId): self
    {
        $clone = clone $this;
        $clone->chatConversationId = $chatConversationId;
        return $clone;
    }

    public function withChatTopicId(string $chatTopicId): self
    {
        $clone = clone $this;
        $clone->chatTopicId = $chatTopicId;
        return $clone;
    }

    public function withTopicId(string $topicId): self
    {
        $clone = clone $this;
        $clone->topicId = $topicId;
        return $clone;
    }

    public function withInstruction(string $instruction): self
    {
        $clone = clone $this;
        $clone->instruction = $instruction;
        return $clone;
    }

    public function withSandboxId(string $sandboxId): self
    {
        $clone = clone $this;
        $clone->sandboxId = $sandboxId;
        return $clone;
    }

    public function withBeDelightfulTaskId(string $beDelightfulTaskId): self
    {
        $clone = clone $this;
        $clone->beDelightfulTaskId = $beDelightfulTaskId;
        return $clone;
    }

    public function withWorkspaceId(string $workspaceId): self
    {
        $clone = clone $this;
        $clone->workspaceId = $workspaceId;
        return $clone;
    }

    public function withProjectId(string $projectId): self
    {
        $clone = clone $this;
        $clone->projectId = $projectId;
        return $clone;
    }

    public function withLanguage(string $language): self
    {
        $clone = clone $this;
        $clone->language = $language;
        return $clone;
    }

    /**
     * Set user information.
     *
     * @param null|UserInfoValueObject $userInfo User information object
     * @return self New instance
     */
    public function withUserInfo(?UserInfoValueObject $userInfo): self
    {
        $clone = clone $this;
        $clone->userInfo = $userInfo;
        return $clone;
    }

    /**
     * Check if has user information.
     *
     * @return bool Whether has user information
     */
    public function hasUserInfo(): bool
    {
        return $this->userInfo !== null;
    }

    /**
     * Get whether to skip initialization messages.
     *
     * @return bool Whether to skip initialization messages
     */
    public function getSkipInitMessages(): bool
    {
        return $this->skipInitMessages;
    }

    /**
     * Set whether to skip initialization messages.
     *
     * @param bool $skipInitMessages Whether to skip initialization messages
     * @return self New instance
     */
    public function withSkipInitMessages(bool $skipInitMessages): self
    {
        $clone = clone $this;
        $clone->skipInitMessages = $skipInitMessages;
        return $clone;
    }
}

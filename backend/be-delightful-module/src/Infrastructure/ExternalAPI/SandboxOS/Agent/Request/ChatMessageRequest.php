<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

use App\Infrastructure\Util\IdGenerator\IdGenerator;

/**
 * Chat message request class
 * Strictly follows the chat message request format in sandbox communication documentation.
 */
class ChatMessageRequest
{
    public function __construct(
        private string $messageId = '',
        private string $userId = '',
        private string $taskId = '',
        private string $prompt = '',
        private string $taskMode = 'chat',
        private string $agentMode = '',
        private array $attachments = [],
        private array $mentions = [],
        private array $mcpConfig = [],
        private string $modelId = '',
        private array $dynamicConfig = [],
    ) {
    }

    /**
     * Create a chat message request object
     */
    public static function create(
        string $messageId,
        string $userId,
        string $taskId,
        string $prompt,
        string $taskMode = 'chat',
        string $agentMode = '',
        array $attachments = [],
        array $mentions = [],
        array $mcpConfig = [],
        string $modelId = '',
        array $dynamicConfig = [],
    ): self {
        return new self(
            $messageId,
            $userId,
            $taskId,
            $prompt,
            $taskMode,
            $agentMode,
            $attachments,
            $mentions,
            $mcpConfig,
            $modelId,
            $dynamicConfig
        );
    }

    public function getMcpConfig(): array
    {
        return $this->mcpConfig;
    }

    public function setMcpConfig(array $mcpConfig): void
    {
        $this->mcpConfig = $mcpConfig;
    }

    /**
     * Get prompt content.
     */
    public function getPrompt(): string
    {
        return $this->prompt;
    }

    /**
     * Set prompt content.
     */
    public function setPrompt(string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    /**
     * Get task mode.
     */
    public function getTaskMode(): string
    {
        return $this->taskMode;
    }

    /**
     * Set task mode.
     */
    public function setTaskMode(string $taskMode): self
    {
        $this->taskMode = $taskMode;
        return $this;
    }

    /**
     * Get Agent mode.
     */
    public function getAgentMode(): string
    {
        return $this->agentMode;
    }

    /**
     * Set Agent mode.
     */
    public function setAgentMode(string $agentMode): self
    {
        $this->agentMode = $agentMode;
        return $this;
    }

    /**
     * Get attachments.
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * Set attachments.
     */
    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    /**
     * Set user ID.
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * Get user ID.
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * Set task ID.
     */
    public function setTaskId(string $taskId): self
    {
        $this->taskId = $taskId;
        return $this;
    }

    /**
     * Get task ID.
     */
    public function getTaskId(): string
    {
        return $this->taskId;
    }

    /**
     * Set message ID.
     */
    public function setMessageId(string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * Get message ID.
     */
    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * Get mentions.
     */
    public function getMentions(): array
    {
        /* @phpstan-ignore-next-line */
        return $this->mentions ?? [];
    }

    /**
     * Set mentions.
     */
    public function setMentions(array $mentions): self
    {
        $this->mentions = $mentions;
        return $this;
    }

    /**
     * Get model ID.
     */
    public function getModelId(): string
    {
        return $this->modelId;
    }

    /**
     * Set model ID.
     */
    public function setModelId(string $modelId): self
    {
        $this->modelId = $modelId;
        return $this;
    }

    /**
     * Get dynamic configuration.
     */
    public function getDynamicConfig(): array
    {
        return $this->dynamicConfig;
    }

    /**
     * Set dynamic configuration.
     */
    public function setDynamicConfig(array $dynamicConfig): self
    {
        $this->dynamicConfig = $dynamicConfig;
        return $this;
    }

    /**
     * Convert to API request array
     * According to chat message request format in sandbox communication documentation.
     */
    public function toArray(): array
    {
        return [
            'message_id' => ! empty($this->messageId) ? $this->messageId : (string) IdGenerator::getSnowId(),
            'user_id' => $this->userId,
            'task_id' => $this->taskId,
            'type' => 'chat',
            'prompt' => $this->prompt,
            'task_mode' => $this->taskMode,
            'agent_mode' => $this->agentMode,
            'attachments' => $this->attachments,
            'mentions' => $this->mentions,
            'mcp_config' => $this->mcpConfig,
            'model_id' => $this->modelId,
            'dynamic_config' => $this->dynamicConfig,
        ];
    }
}

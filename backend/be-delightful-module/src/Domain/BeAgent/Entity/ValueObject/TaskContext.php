<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\BeAgentExtra;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskEntity;

/**
 * Task context object used to encapsulate task-related context parameters.
 *
 * This class is an immutable value object that conforms to DDD design patterns
 */
class TaskContext
{
    /**
     * @param TaskEntity $task Task entity
     * @param DataIsolation $dataIsolation Data isolation object
     * @param string $chatConversationId Chat conversation ID
     * @param string $chatTopicId Chat topic ID
     * @param string $agentUserId Agent user ID
     * @param string $sandboxId Sandbox ID
     * @param string $taskId Task ID
     * @param ChatInstruction $instruction Chat instruction
     * @param string $agentMode Agent mode
     * @param array $mcpConfig MCP configuration
     * @param string $workspaceId Workspace ID
     * @param string $messageId Message ID
     * @param bool $isFirstTask Whether is first task
     * @param null|BeAgentExtra $extra Extension parameters
     */
    public function __construct(
        private readonly TaskEntity $task,
        private readonly DataIsolation $dataIsolation,
        private readonly string $chatConversationId,
        private readonly string $chatTopicId,
        private readonly string $agentUserId,
        private string $sandboxId = '',
        private string $taskId = '',
        private ChatInstruction $instruction = ChatInstruction::Normal,
        private string $agentMode = '',
        private array $mcpConfig = [],
        private string $modelId = '',
        private array $dynamicConfig = [],
        private string $workspaceId = '',
        private string $messageId = '',
        private bool $isFirstTask = false,
        private ?BeAgentExtra $extra = null,
    ) {
    }

    /**
     * Get task entity.
     */
    public function getTask(): TaskEntity
    {
        return $this->task;
    }

    /**
     * Get data isolation object
     */
    public function getDataIsolation(): DataIsolation
    {
        return $this->dataIsolation;
    }

    /**
     * Get chat conversation ID.
     */
    public function getChatConversationId(): string
    {
        return $this->chatConversationId;
    }

    /**
     * Get chat topic ID.
     */
    public function getChatTopicId(): string
    {
        return $this->chatTopicId;
    }

    /**
     * Get Agent user ID.
     */
    public function getAgentUserId(): string
    {
        return $this->agentUserId;
    }

    /**
     * Get sandbox ID.
     */
    public function getSandboxId(): string
    {
        return $this->sandboxId;
    }

    /**
     * Get task ID.
     */
    public function getTaskId(): string
    {
        return $this->taskId ?: $this->task->getTaskId();
    }

    /**
     * Get original task ID (retrieved from task entity).
     */
    public function getOriginalTaskId(): string
    {
        return $this->task->getTaskId();
    }

    /**
     * Get task entity ID.
     */
    public function getTaskEntityId(): int
    {
        return $this->task->getId();
    }

    /**
     * Get task topic ID.
     */
    public function getTopicId(): int
    {
        return $this->task->getTopicId();
    }

    /**
     * Get project ID.
     */
    public function getProjectId(): int
    {
        return $this->task->getProjectId();
    }

    /**
     * Get current user ID.
     */
    public function getCurrentUserId(): string
    {
        return $this->dataIsolation->getCurrentUserId();
    }

    /**
     * Get current organization code
     */
    public function getCurrentOrganizationCode(): string
    {
        return $this->dataIsolation->getCurrentOrganizationCode();
    }

    /**
     * Get chat instruction.
     */
    public function getInstruction(): ChatInstruction
    {
        return $this->instruction;
    }

    /**
     * Get Agent mode.
     */
    public function getAgentMode(): string
    {
        return $this->agentMode;
    }

    public function getMcpConfig(): array
    {
        return $this->mcpConfig;
    }

    /**
     * Create a context with a new task while retaining other parameters.
     */
    public function withTask(TaskEntity $newTask): self
    {
        return new self(
            $newTask,
            $this->dataIsolation,
            $this->chatConversationId,
            $this->chatTopicId,
            $this->agentUserId,
            $this->sandboxId,
            $this->taskId,
            $this->instruction,
            $this->agentMode,
            $this->mcpConfig,
            $this->modelId,
            $this->dynamicConfig,
            $this->workspaceId,
            $this->messageId,
            $this->isFirstTask,
            $this->extra,
        );
    }

    public function setTaskId(string $taskId): self
    {
        $this->taskId = $taskId;
        return $this;
    }

    public function setSandboxId(string $sandboxId): self
    {
        $this->sandboxId = $sandboxId;
        return $this;
    }

    public function setInstruction(ChatInstruction $instruction): self
    {
        $this->instruction = $instruction;
        return $this;
    }

    public function setAgentMode(string $agentMode): self
    {
        $this->agentMode = $agentMode;
        return $this;
    }

    public function setMcpConfig(array $mcpConfig): self
    {
        $this->mcpConfig = $mcpConfig;
        return $this;
    }

    public function getModelId(): string
    {
        return $this->modelId;
    }

    public function setModelId(string $modelId): self
    {
        $this->modelId = $modelId;
        return $this;
    }

    public function getDynamicConfig(): array
    {
        if (! empty($this->modelId) && empty($this->dynamicConfig['models'][$this->getModelId()])) {
            // Add default configuration
            $this->dynamicConfig['models'][$this->getModelId()] = [
                'api_key' => '${MAGIC_API_KEY}',
                'api_base_url' => '${MAGIC_API_BASE_URL}',
                'name' => $this->getModelId(),
            ];
        }

        return $this->dynamicConfig;
    }

    public function setDynamicConfig(array $dynamicConfig): self
    {
        $this->dynamicConfig = $dynamicConfig;
        return $this;
    }

    public function setWorkspaceId(string $workspaceId): self
    {
        $this->workspaceId = $workspaceId;
        return $this;
    }

    public function getWorkspaceId(): string
    {
        return $this->workspaceId;
    }

    /**
     * Get message ID.
     */
    public function getMessageId(): string
    {
        return $this->messageId;
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
     * Get whether is first task.
     */
    public function getIsFirstTask(): bool
    {
        return $this->isFirstTask;
    }

    /**
     * Set whether is first task.
     */
    public function setIsFirstTask(bool $isFirstTask): self
    {
        $this->isFirstTask = $isFirstTask;
        return $this;
    }

    /**
     * Get extension parameters.
     */
    public function getExtra(): ?BeAgentExtra
    {
        return $this->extra;
    }

    /**
     * Set extension parameters.
     */
    public function setExtra(?BeAgentExtra $extra): self
    {
        $this->extra = $extra;
        return $this;
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity;

use App\Infrastructure\Core\AbstractEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Throwable;

/**
 * Topic entity.
 */
class TopicEntity extends AbstractEntity
{
    /**
     * @var int Topic ID
     */
    protected int $id;

    /**
     * @var string User ID
     */
    protected string $userId;

    /**
     * @var string User organization code
     */
    protected string $userOrganizationCode = '';

    /**
     * @var int Workspace ID
     */
    protected int $workspaceId = 0;

    /**
     * @var int Project ID
     */
    protected int $projectId = 0;

    /**
     * @var null|int Copy source topic ID
     */
    protected ?int $fromTopicId = null;

    /**
     * @var string Chat topic ID
     */
    protected string $chatTopicId = '';

    /**
     * @var string Chat conversation ID
     */
    protected string $chatConversationId = '';

    /**
     * @var string Sandbox ID
     */
    protected string $sandboxId = '';

    /**
     * @var null|string Sandbox configuration information (JSON string)
     */
    protected ?string $sandboxConfig = null;

    /**
     * @var string Working directory
     */
    protected string $workDir = '';

    /**
     * @var string Topic name
     */
    protected string $topicName = '';

    /**
     * @var null|string Topic description
     */
    protected ?string $description = null;

    /**
     * @var string Task mode (chat: chat mode, plan: planning mode)
     */
    protected string $taskMode = 'chat';

    /**
     * @var string Topic mode (supports custom string)
     */
    protected string $topicMode = '';

    /**
     * @var float Topic cost
     */
    protected float $cost = 0.0;

    /**
     * @var int Creation source
     */
    protected int $source = 1;

    /**
     * @var null|string Source ID
     */
    protected ?string $sourceId = null;

    /**
     * @var null|int Current task ID
     */
    protected ?int $currentTaskId = null;

    /**
     * @var null|TaskStatus Current task status
     */
    protected ?TaskStatus $currentTaskStatus = null;

    /**
     * @var null|string Creation time
     */
    protected ?string $createdAt = null;

    /**
     * @var null|string Update time
     */
    protected ?string $updatedAt = null;

    /**
     * @var null|string Deletion time
     */
    protected ?string $deletedAt = null;

    /**
     * @var string Creator user ID
     */
    protected string $createdUid = '';

    /**
     * @var string Updater user ID
     */
    protected string $updatedUid = '';

    /**
     * @var string commit hash
     */
    protected ?string $workspaceCommitHash = '';

    protected ?string $chatHistoryCommitHash = '';

    public function __construct(array $data = [])
    {
        $this->initProperty($data);
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        $result = [
            'id' => $this->id ?? 0,
            'user_id' => $this->userId ?? 0,
            'user_organization_code' => $this->userOrganizationCode ?? '',
            'workspace_id' => $this->workspaceId ?? 0,
            'project_id' => $this->projectId ?? 0,
            'from_topic_id' => $this->fromTopicId,
            'chat_topic_id' => $this->chatTopicId ?? '',
            'chat_conversation_id' => $this->chatConversationId ?? '',
            'sandbox_id' => $this->sandboxId ?? '',
            'sandbox_config' => $this->sandboxConfig,
            'work_dir' => $this->workDir ?? '',
            'topic_name' => $this->topicName ?? '',
            'description' => $this->description,
            'task_mode' => $this->taskMode ?? 'chat',
            'topic_mode' => $this->topicMode,
            'cost' => $this->cost ?? 0.0,
            'source' => $this->source,
            'source_id' => $this->sourceId,
            'current_task_id' => $this->currentTaskId,
            'current_task_status' => $this->currentTaskStatus?->value,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
            'created_uid' => $this->createdUid,
            'updated_uid' => $this->updatedUid,
            'workspace_commit_hash' => $this->workspaceCommitHash,
            'chat_history_commit_hash' => $this->chatHistoryCommitHash,
        ];

        // Remove null values
        return array_filter($result, function ($value) {
            return $value !== null;
        });
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId($id): self
    {
        // Convert when input is not an integer
        if (! is_int($id)) {
            $id = (int) $id;
        }

        $this->id = $id;
        return $this;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUserOrganizationCode(): string
    {
        return $this->userOrganizationCode;
    }

    public function setUserOrganizationCode(string $userOrganizationCode): self
    {
        $this->userOrganizationCode = $userOrganizationCode;
        return $this;
    }

    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }

    public function setWorkspaceId($workspaceId): self
    {
        // Convert when input is not an integer
        if (! is_int($workspaceId)) {
            $workspaceId = (int) $workspaceId;
        }

        $this->workspaceId = $workspaceId;
        return $this;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function setProjectId($projectId): self
    {
        // Convert when input is not an integer
        if (! is_int($projectId)) {
            $projectId = (int) $projectId;
        }

        $this->projectId = $projectId;
        return $this;
    }

    public function getFromTopicId(): ?int
    {
        return $this->fromTopicId;
    }

    public function setFromTopicId($fromTopicId): self
    {
        // Convert when input is not an integer, keep null as null
        if ($fromTopicId !== null && ! is_int($fromTopicId)) {
            $fromTopicId = (int) $fromTopicId;
        }

        $this->fromTopicId = $fromTopicId;
        return $this;
    }

    /**
     * Get Chat topic ID.
     */
    public function getChatTopicId(): string
    {
        return $this->chatTopicId;
    }

    /**
     * Set Chat topic ID.
     */
    public function setChatTopicId(string $chatTopicId): self
    {
        $this->chatTopicId = $chatTopicId;
        return $this;
    }

    /**
     * Get Chat conversation ID.
     */
    public function getChatConversationId(): string
    {
        return $this->chatConversationId;
    }

    /**
     * Set Chat conversation ID.
     */
    public function setChatConversationId(string $chatConversationId): self
    {
        $this->chatConversationId = $chatConversationId;
        return $this;
    }

    /**
     * Get sandbox ID.
     */
    public function getSandboxId(): string
    {
        return $this->sandboxId;
    }

    /**
     * Set sandbox ID.
     */
    public function setSandboxId(string $sandboxId): self
    {
        $this->sandboxId = $sandboxId;
        return $this;
    }

    /**
     * Get sandbox configuration.
     */
    public function getSandboxConfig(): ?string
    {
        return $this->sandboxConfig;
    }

    /**
     * Set sandbox configuration.
     */
    public function setSandboxConfig(?string $sandboxConfig): self
    {
        $this->sandboxConfig = $sandboxConfig;
        return $this;
    }

    /**
     * Get working directory.
     */
    public function getWorkDir(): string
    {
        return $this->workDir;
    }

    /**
     * Set working directory.
     */
    public function setWorkDir(string $workDir): self
    {
        $this->workDir = $workDir;
        return $this;
    }

    public function getTopicName(): string
    {
        return $this->topicName;
    }

    public function setTopicName(string $topicName): self
    {
        $this->topicName = $topicName;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCurrentTaskId(): ?int
    {
        return $this->currentTaskId;
    }

    public function setCurrentTaskId($currentTaskId): self
    {
        // Convert when input is not an integer
        if ($currentTaskId !== null && ! is_int($currentTaskId)) {
            $currentTaskId = (int) $currentTaskId;
        }

        $this->currentTaskId = $currentTaskId;
        return $this;
    }

    public function getCurrentTaskStatus(): ?TaskStatus
    {
        return $this->currentTaskStatus;
    }

    public function setCurrentTaskStatus($currentTaskStatus): self
    {
        // If input is not TaskStatus type but not null, try to convert
        if ($currentTaskStatus !== null && ! ($currentTaskStatus instanceof TaskStatus)) {
            try {
                $currentTaskStatus = TaskStatus::from($currentTaskStatus);
            } catch (Throwable $e) {
                // Set to null when conversion fails
                $currentTaskStatus = null;
            }
        }

        $this->currentTaskStatus = $currentTaskStatus;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?string $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    /**
     * Get creator user ID.
     */
    public function getCreatedUid(): string
    {
        return $this->createdUid;
    }

    /**
     * Set creator user ID.
     * @param mixed $createdUid
     */
    public function setCreatedUid($createdUid): self
    {
        // If null is passed, set to empty string
        $this->createdUid = $createdUid === null ? '' : (string) $createdUid;
        return $this;
    }

    /**
     * Get updater user ID.
     */
    public function getUpdatedUid(): string
    {
        return $this->updatedUid;
    }

    /**
     * Set updater user ID.
     * @param mixed $updatedUid
     */
    public function setUpdatedUid($updatedUid): self
    {
        // If null is passed, set to empty string
        $this->updatedUid = $updatedUid === null ? '' : (string) $updatedUid;
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
     * Get topic mode.
     */
    public function getTopicMode(): string
    {
        return $this->topicMode;
    }

    /**
     * Set topic mode.
     */
    public function setTopicMode(string $topicMode): self
    {
        $this->topicMode = $topicMode;
        return $this;
    }

    /**
     * Get topic cost.
     */
    public function getCost(): float
    {
        return $this->cost;
    }

    /**
     * Set topic cost.
     * @param mixed $cost
     */
    public function setCost($cost): self
    {
        // Convert when input is not a float
        if (! is_float($cost)) {
            $cost = (float) $cost;
        }

        $this->cost = $cost;
        return $this;
    }

    /**
     * Get creation source.
     */
    public function getSource(): int
    {
        return $this->source;
    }

    /**
     * Set creation source.
     */
    public function setSource(int $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getWorkspaceCommitHash(): string
    {
        return $this->workspaceCommitHash;
    }

    public function setWorkspaceCommitHash(?string $workspaceCommitHash): self
    {
        $this->workspaceCommitHash = $workspaceCommitHash;
        return $this;
    }

    public function getChatHistoryCommitHash(): string
    {
        return $this->chatHistoryCommitHash;
    }

    public function setChatHistoryCommitHash(?string $chatHistoryCommitHash): self
    {
        $this->chatHistoryCommitHash = $chatHistoryCommitHash;
        return $this;
    }

    /**
     * Get source ID.
     */
    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    /**
     * Set source ID.
     */
    public function setSourceId(?string $sourceId): self
    {
        $this->sourceId = $sourceId;
        return $this;
    }
}

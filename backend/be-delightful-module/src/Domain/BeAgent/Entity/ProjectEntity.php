<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity;

use App\Infrastructure\Core\AbstractEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\ProjectStatus;

/**
 * Project Entity.
 */
class ProjectEntity extends AbstractEntity
{
    /**
     * @var int Project ID
     */
    protected int $id = 0;

    /**
     * @var string User ID
     */
    protected string $userId = '';

    /**
     * @var string User organization code
     */
    protected string $userOrganizationCode = '';

    /**
     * @var int Workspace ID
     */
    protected int $workspaceId = 0;

    /**
     * @var string Project name
     */
    protected string $projectName = '';

    /**
     * @var string Project description
     */
    protected string $projectDescription = '';

    /**
     * @var string Project directory
     */
    protected string $workDir = '';

    /**
     * @var string Project mode
     */
    protected string $projectMode = '';

    /**
     * @var int Creation source
     */
    protected int $source = 1;

    /**
     * @var ProjectStatus Project status
     */
    protected ProjectStatus $projectStatus = ProjectStatus::ACTIVE;

    /**
     * @var MemberRole Default join permission
     */
    protected MemberRole $defaultJoinPermission = MemberRole::EDITOR;

    /**
     * @var null|int Current topic ID
     */
    protected ?int $currentTopicId = null;

    /**
     * @var string Current topic status
     */
    protected string $currentTopicStatus = '';

    /**
     * @var bool Whether collaboration feature is enabled
     */
    protected bool $isCollaborationEnabled = true;

    /**
     * @var string Creator user ID
     */
    protected string $createdUid = '';

    /**
     * @var string Updater user ID
     */
    protected string $updatedUid = '';

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
            'id' => $this->id,
            'user_id' => $this->userId,
            'user_organization_code' => $this->userOrganizationCode,
            'workspace_id' => $this->workspaceId,
            'project_name' => $this->projectName,
            'project_description' => $this->projectDescription,
            'work_dir' => $this->workDir,
            'project_status' => $this->projectStatus->value,
            'current_topic_id' => $this->currentTopicId,
            'current_topic_status' => $this->currentTopicStatus,
            'is_collaboration_enabled' => $this->isCollaborationEnabled,
            'project_mode' => $this->projectMode,
            'source' => $this->source,
            'default_join_permission' => $this->defaultJoinPermission->value,
            'created_uid' => $this->createdUid,
            'updated_uid' => $this->updatedUid,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
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

    public function setId(int|string $id): self
    {
        $this->id = (int) $id;
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

    public function setWorkspaceId(int|string $workspaceId): self
    {
        $this->workspaceId = (int) $workspaceId;
        return $this;
    }

    public function getProjectName(): string
    {
        return $this->projectName;
    }

    public function setProjectName(string $projectName): self
    {
        $this->projectName = $projectName;
        return $this;
    }

    public function getProjectDescription(): string
    {
        return $this->projectDescription;
    }

    public function setProjectDescription(string $projectDescription): self
    {
        $this->projectDescription = $projectDescription;
        return $this;
    }

    public function getWorkDir(): string
    {
        return $this->workDir;
    }

    public function setWorkDir(string $workDir): self
    {
        $this->workDir = $workDir;
        return $this;
    }

    public function getProjectStatus(): ProjectStatus
    {
        return $this->projectStatus;
    }

    public function setProjectStatus(int $projectStatus): self
    {
        $this->projectStatus = ProjectStatus::from($projectStatus);
        return $this;
    }

    public function getCurrentTopicId(): ?int
    {
        return $this->currentTopicId;
    }

    public function setCurrentTopicId(null|int|string $currentTopicId): self
    {
        $this->currentTopicId = $currentTopicId ? (int) $currentTopicId : null;
        return $this;
    }

    public function getCurrentTopicStatus(): string
    {
        return $this->currentTopicStatus;
    }

    public function setCurrentTopicStatus(string $currentTopicStatus): self
    {
        $this->currentTopicStatus = $currentTopicStatus;
        return $this;
    }

    public function getCreatedUid(): string
    {
        return $this->createdUid;
    }

    public function setCreatedUid(string $createdUid): self
    {
        $this->createdUid = $createdUid;
        return $this;
    }

    public function getUpdatedUid(): string
    {
        return $this->updatedUid;
    }

    public function setUpdatedUid(string $updatedUid): self
    {
        $this->updatedUid = $updatedUid;
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
     * Check if project is deleted.
     */
    public function isDeleted(): bool
    {
        return ! empty($this->deletedAt);
    }

    /**
     * Get project status.
     */
    public function getStatus(): ProjectStatus
    {
        // Sync status with deleted_at field
        if ($this->isDeleted() && ! $this->projectStatus->isDeleted()) {
            $this->projectStatus = ProjectStatus::DELETED;
        }
        return $this->projectStatus;
    }

    /**
     * Mark project as deleted.
     */
    public function markAsDeleted(): self
    {
        $this->deletedAt = date('Y-m-d H:i:s');
        $this->projectStatus = ProjectStatus::DELETED;
        return $this;
    }

    /**
     * Restore deleted project.
     */
    public function restore(): self
    {
        $this->deletedAt = null;
        $this->projectStatus = ProjectStatus::ACTIVE;
        return $this;
    }

    /**
     * Archive project.
     */
    public function archive(): self
    {
        $this->projectStatus = ProjectStatus::ARCHIVED;
        return $this;
    }

    /**
     * Activate project.
     */
    public function activate(): self
    {
        $this->projectStatus = ProjectStatus::ACTIVE;
        return $this;
    }

    /**
     * Check if project is active.
     */
    public function isActive(): bool
    {
        return $this->getStatus()->isActive();
    }

    /**
     * Check if project is archived.
     */
    public function isArchived(): bool
    {
        return $this->getStatus()->isArchived();
    }

    /**
     * Get project mode.
     */
    public function getProjectMode(): ?string
    {
        return $this->projectMode;
    }

    /**
     * Set project mode.
     */
    public function setProjectMode(?string $projectMode): self
    {
        $this->projectMode = $projectMode ?? '';
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

    /**
     * Get collaboration feature switch status.
     */
    public function getIsCollaborationEnabled(): bool
    {
        return $this->isCollaborationEnabled;
    }

    /**
     * Set collaboration feature switch status.
     */
    public function setIsCollaborationEnabled(bool|int|string $isCollaborationEnabled): self
    {
        $this->isCollaborationEnabled = (bool) $isCollaborationEnabled;
        return $this;
    }

    public function getDefaultJoinPermission(): MemberRole
    {
        return $this->defaultJoinPermission;
    }

    public function setDefaultJoinPermission(MemberRole $defaultJoinPermission): void
    {
        $this->defaultJoinPermission = $defaultJoinPermission;
    }
}

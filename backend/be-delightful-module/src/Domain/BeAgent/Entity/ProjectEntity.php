<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity;

use App\Infrastructure\Core\AbstractEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\ProjectStatus;
/** * Project Entity. */

class ProjectEntity extends AbstractEntity 
{
 /** * @var int Project ID */ 
    protected int $id = 0; /** * @var string user ID */ 
    protected string $userId = ''; /** * @var string user organization code */ 
    protected string $userOrganizationCode = ''; /** * @var int Workspace ID */ 
    protected int $workspaceId = 0; /** * @var string Project name */ 
    protected string $projectName = ''; /** * @var string ItemDescription */ 
    protected string $projectDescription = ''; /** * @var string ItemDirectory */ 
    protected string $workDir = ''; /** * @var string ItemSchema */ 
    protected string $projectMode = ''; /** * @var int Creation source */ 
    protected int $source = 1; /** * @var ProjectStatus Project status */ 
    protected ProjectStatus $projectStatus = ProjectStatus::ACTIVE; /** * @var MemberRole DefaultJoinpermission */ 
    protected MemberRole $defaultJoinpermission = MemberRole::EDITOR; /** * @var null|int current topic ID */ protected ?int $currentTopicId = null; /** * @var string current topic Status */ 
    protected string $currentTopicStatus = ''; /** * @var bool whether Enabledcollaboration */ 
    protected bool $isCollaborationEnabled = true; /** * @var string creator user ID */ 
    protected string $createdUid = ''; /** * @var string Updateuser ID */ 
    protected string $updatedUid = ''; /** * @var null|string Creation time */ protected ?string $createdAt = null; /** * @var null|string Update time */ protected ?string $updatedAt = null; /** * @var null|string Deletion time */ protected ?string $deletedAt = null; 
    public function __construct(array $data = []) 
{
 $this->initProperty($data); 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 $result = [ 'id' => $this->id, 'user_id' => $this->userId, 'user_organization_code' => $this->userOrganizationCode, 'workspace_id' => $this->workspaceId, 'project_name' => $this->projectName, 'project_description' => $this->projectDescription, 'work_dir' => $this->workDir, 'project_status' => $this->projectStatus->value, 'current_topic_id' => $this->currentTopicId, 'current_topic_status' => $this->currentTopicStatus, 'is_collaboration_enabled' => $this->isCollaborationEnabled, 'project_mode' => $this->projectMode, 'source' => $this->source, 'default_join_permission' => $this->defaultJoinpermission ->value, 'created_uid' => $this->createdUid, 'updated_uid' => $this->updatedUid, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt, 'deleted_at' => $this->deletedAt, ]; // RemovenullValue return array_filter($result, function ($value) 
{
 return $value !== null; 
}
); 
}
 
    public function getId(): int 
{
 return $this->id; 
}
 
    public function setId(int|string $id): self 
{
 $this->id = (int) $id; return $this; 
}
 
    public function getuser Id(): string 
{
 return $this->userId; 
}
 
    public function setuser Id(string $userId): self 
{
 $this->userId = $userId; return $this; 
}
 
    public function getuser OrganizationCode(): string 
{
 return $this->userOrganizationCode; 
}
 
    public function setuser OrganizationCode(string $userOrganizationCode): self 
{
 $this->userOrganizationCode = $userOrganizationCode; return $this; 
}
 
    public function getWorkspaceId(): int 
{
 return $this->workspaceId; 
}
 
    public function setWorkspaceId(int|string $workspaceId): self 
{
 $this->workspaceId = (int) $workspaceId; return $this; 
}
 
    public function getProjectName(): string 
{
 return $this->projectName; 
}
 
    public function setProjectName(string $projectName): self 
{
 $this->projectName = $projectName; return $this; 
}
 
    public function getProjectDescription(): string 
{
 return $this->projectDescription; 
}
 
    public function setProjectDescription(string $projectDescription): self 
{
 $this->projectDescription = $projectDescription; return $this; 
}
 
    public function getWorkDir(): string 
{
 return $this->workDir; 
}
 
    public function setWorkDir(string $workDir): self 
{
 $this->workDir = $workDir; return $this; 
}
 
    public function getProjectStatus(): ProjectStatus 
{
 return $this->projectStatus; 
}
 
    public function setProjectStatus(int $projectStatus): self 
{
 $this->projectStatus = ProjectStatus::from($projectStatus); return $this; 
}
 
    public function getcurrent TopicId(): ?int 
{
 return $this->currentTopicId; 
}
 
    public function setcurrent TopicId(null|int|string $currentTopicId): self 
{
 $this->currentTopicId = $currentTopicId ? (int) $currentTopicId : null; return $this; 
}
 
    public function getcurrent TopicStatus(): string 
{
 return $this->currentTopicStatus; 
}
 
    public function setcurrent TopicStatus(string $currentTopicStatus): self 
{
 $this->currentTopicStatus = $currentTopicStatus; return $this; 
}
 
    public function getCreatedUid(): string 
{
 return $this->createdUid; 
}
 
    public function setCreatedUid(string $createdUid): self 
{
 $this->createdUid = $createdUid; return $this; 
}
 
    public function getUpdatedUid(): string 
{
 return $this->updatedUid; 
}
 
    public function setUpdatedUid(string $updatedUid): self 
{
 $this->updatedUid = $updatedUid; return $this; 
}
 
    public function getCreatedAt(): ?string 
{
 return $this->createdAt; 
}
 
    public function setCreatedAt(?string $createdAt): self 
{
 $this->createdAt = $createdAt; return $this; 
}
 
    public function getUpdatedAt(): ?string 
{
 return $this->updatedAt; 
}
 
    public function setUpdatedAt(?string $updatedAt): self 
{
 $this->updatedAt = $updatedAt; return $this; 
}
 
    public function getdelete dAt(): ?string 
{
 return $this->deletedAt; 
}
 
    public function setdelete dAt(?string $deletedAt): self 
{
 $this->deletedAt = $deletedAt; return $this; 
}
 /** * check Itemwhether delete d. */ 
    public function isdelete d(): bool 
{
 return ! empty($this->deletedAt); 
}
 /** * GetItemStatus */ 
    public function getStatus(): ProjectStatus 
{
 // Sync status with deleted_at field if ($this->isdelete d() && ! $this->projectStatus->isdelete d()) 
{
 $this->projectStatus = ProjectStatus::DELETED; 
}
 return $this->projectStatus; 
}
 /** * mark Itemas delete d. */ 
    public function markAsdelete d(): self 
{
 $this->deletedAt = date('Y-m-d H:i:s'); $this->projectStatus = ProjectStatus::DELETED; return $this; 
}
 /** * Resumedelete dItem. */ 
    public function restore(): self 
{
 $this->deletedAt = null; $this->projectStatus = ProjectStatus::ACTIVE; return $this; 
}
 /** * Item. */ 
    public function archive(): self 
{
 $this->projectStatus = ProjectStatus::ARCHIVED; return $this; 
}
 /** * ActiveItem. */ 
    public function activate(): self 
{
 $this->projectStatus = ProjectStatus::ACTIVE; return $this; 
}
 /** * check Itemwhether active . */ 
    public function isActive(): bool 
{
 return $this->getStatus()->isActive(); 
}
 /** * check Itemwhether Archived. */ 
    public function isArchived(): bool 
{
 return $this->getStatus()->isArchived(); 
}
 /** * GetItemSchema. */ 
    public function getProjectMode(): ?string 
{
 return $this->projectMode; 
}
 /** * Set ItemSchema. */ 
    public function setProjectMode(?string $projectMode): self 
{
 $this->projectMode = $projectMode ?? ''; return $this; 
}
 /** * Get creation source. */ 
    public function getSource(): int 
{
 return $this->source; 
}
 /** * Set creation source. */ 
    public function setSource(int $source): self 
{
 $this->source = $source; return $this; 
}
 /** * Getcollaboration SwitchStatus. */ 
    public function getIsCollaborationEnabled(): bool 
{
 return $this->isCollaborationEnabled; 
}
 /** * Set collaboration SwitchStatus. */ 
    public function setIsCollaborationEnabled(bool|int|string $isCollaborationEnabled): self 
{
 $this->isCollaborationEnabled = (bool) $isCollaborationEnabled; return $this; 
}
 
    public function getDefaultJoinpermission (): MemberRole 
{
 return $this->defaultJoinpermission ; 
}
 
    public function setDefaultJoinpermission (MemberRole $defaultJoinpermission ): void 
{
 $this->defaultJoinpermission = $defaultJoinpermission ; 
}
 
}
 

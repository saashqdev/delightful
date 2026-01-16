<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity;

use App\Infrastructure\Core\AbstractEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Throwable;
/** * topic . */

class TopicEntity extends AbstractEntity 
{
 /** * @var int topic ID */ 
    protected int $id; /** * @var string user ID */ 
    protected string $userId; /** * @var string user groupEncode */ 
    protected string $userOrganizationCode = ''; /** * @var int workspace ID */ 
    protected int $workspaceId = 0; /** * @var int Project ID */ 
    protected int $projectId = 0; /** * @var null|int CopySourcetopic ID */ protected ?int $fromTopicId = null; /** * @var string Chattopic ID */ 
    protected string $chatTopicId = ''; /** * @var string ChatSessionID */ 
    protected string $chatConversationId = ''; /** * @var string Sandbox ID */ 
    protected string $sandboxId = ''; /** * @var null|string sandbox Configurationinfo JSONString */ protected ?string $sandboxConfig = null; /** * @var string working directory */ 
    protected string $workDir = ''; /** * @var string topic Name */ 
    protected string $topicName = ''; /** * @var null|string topic Description */ protected ?string $description = null; /** * @var string TaskSchemachat: Schema, plan: Schema */ 
    protected string $taskMode = 'chat'; /** * @var string topic Schema (SupportCustomString) */ 
    protected string $topicMode = ''; /** * @var float topic */ 
    protected float $cost = 0.0; /** * @var int Creation source */ 
    protected int $source = 1; /** * @var null|string Source ID */ protected ?string $sourceId = null; /** * @var null|int current TaskID */ protected ?int $currentTaskId = null; /** * @var null|TaskStatus current TaskStatus */ protected ?TaskStatus $currentTaskStatus = null; /** * @var null|string Creation time */ protected ?string $createdAt = null; /** * @var null|string Update time */ protected ?string $updatedAt = null; /** * @var null|string Deletion time */ protected ?string $deletedAt = null; /** * @var string creator user ID */ 
    protected string $createdUid = ''; /** * @var string Updateuser ID */ 
    protected string $updatedUid = ''; /** * @var string commit hash */ protected ?string $workspaceCommitHash = ''; protected ?string $chatHistoryCommitHash = ''; 
    public function __construct(array $data = []) 
{
 $this->initProperty($data); 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 $result = [ 'id' => $this->id ?? 0, 'user_id' => $this->userId ?? 0, 'user_organization_code' => $this->userOrganizationCode ?? '', 'workspace_id' => $this->workspaceId ?? 0, 'project_id' => $this->projectId ?? 0, 'from_topic_id' => $this->fromTopicId, 'chat_topic_id' => $this->chatTopicId ?? '', 'chat_conversation_id' => $this->chatConversationId ?? '', 'sandbox_id' => $this->sandboxId ?? '', 'sandbox_config' => $this->sandboxConfig, 'work_dir' => $this->workDir ?? '', 'topic_name' => $this->topicName ?? '', 'description' => $this->description, 'task_mode' => $this->taskMode ?? 'chat', 'topic_mode' => $this->topicMode, 'cost' => $this->cost ?? 0.0, 'source' => $this->source, 'source_id' => $this->sourceId, 'current_task_id' => $this->currentTaskId, 'current_task_status' => $this->currentTaskStatus?->value, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt, 'deleted_at' => $this->deletedAt, 'created_uid' => $this->createdUid, 'updated_uid' => $this->updatedUid, 'workspace_commit_hash' => $this->workspaceCommitHash, 'chat_history_commit_hash' => $this->chatHistoryCommitHash, ]; // RemovenullValue return array_filter($result, function ($value) 
{
 return $value !== null; 
}
); 
}
 
    public function getId(): int 
{
 return $this->id; 
}
 
    public function setId($id): self 
{
 // WhenInputIs notIntegerRowConvert if (! is_int($id)) 
{
 $id = (int) $id; 
}
 $this->id = $id; return $this; 
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
 
    public function setWorkspaceId($workspaceId): self 
{
 // WhenInputIs notIntegerRowConvert if (! is_int($workspaceId)) 
{
 $workspaceId = (int) $workspaceId; 
}
 $this->workspaceId = $workspaceId; return $this; 
}
 
    public function getProjectId(): int 
{
 return $this->projectId; 
}
 
    public function setProjectId($projectId): self 
{
 // WhenInputIs notIntegerRowConvert if (! is_int($projectId)) 
{
 $projectId = (int) $projectId; 
}
 $this->projectId = $projectId; return $this; 
}
 
    public function getFromTopicId(): ?int 
{
 return $this->fromTopicId; 
}
 
    public function setFromTopicId($fromTopicId): self 
{
 // WhenInputIs notIntegerRowConvertnullnull if ($fromTopicId !== null && ! is_int($fromTopicId)) 
{
 $fromTopicId = (int) $fromTopicId; 
}
 $this->fromTopicId = $fromTopicId; return $this; 
}
 /** * GetChattopic ID. */ 
    public function getChatTopicId(): string 
{
 return $this->chatTopicId; 
}
 /** * Set Chattopic ID. */ 
    public function setChatTopicId(string $chatTopicId): self 
{
 $this->chatTopicId = $chatTopicId; return $this; 
}
 /** * GetChatSessionID. */ 
    public function getChatConversationId(): string 
{
 return $this->chatConversationId; 
}
 /** * Set ChatSessionID. */ 
    public function setChatConversationId(string $chatConversationId): self 
{
 $this->chatConversationId = $chatConversationId; return $this; 
}
 /** * Get sandbox ID. */ 
    public function getSandboxId(): string 
{
 return $this->sandboxId; 
}
 /** * Set Sandbox ID. */ 
    public function setSandboxId(string $sandboxId): self 
{
 $this->sandboxId = $sandboxId; return $this; 
}
 /** * Getsandbox Configuration. */ 
    public function getSandboxConfig(): ?string 
{
 return $this->sandboxConfig; 
}
 /** * Set sandbox Configuration. */ 
    public function setSandboxConfig(?string $sandboxConfig): self 
{
 $this->sandboxConfig = $sandboxConfig; return $this; 
}
 /** * Getworking directory . */ 
    public function getWorkDir(): string 
{
 return $this->workDir; 
}
 /** * Set working directory . */ 
    public function setWorkDir(string $workDir): self 
{
 $this->workDir = $workDir; return $this; 
}
 
    public function getTopicName(): string 
{
 return $this->topicName; 
}
 
    public function setTopicName(string $topicName): self 
{
 $this->topicName = $topicName; return $this; 
}
 
    public function getDescription(): ?string 
{
 return $this->description; 
}
 
    public function setDescription(?string $description): self 
{
 $this->description = $description; return $this; 
}
 
    public function getcurrent TaskId(): ?int 
{
 return $this->currentTaskId; 
}
 
    public function setcurrent TaskId($currentTaskId): self 
{
 // WhenInputIs notIntegerRowConvert if ($currentTaskId !== null && ! is_int($currentTaskId)) 
{
 $currentTaskId = (int) $currentTaskId; 
}
 $this->currentTaskId = $currentTaskId; return $this; 
}
 
    public function getcurrent TaskStatus(): ?TaskStatus 
{
 return $this->currentTaskStatus; 
}
 
    public function setcurrent TaskStatus($currentTaskStatus): self 
{
 // IfInputIs notTaskStatusTypeEmptytry Convert if ($currentTaskStatus !== null && ! ($currentTaskStatus instanceof TaskStatus)) 
{
 try 
{
 $currentTaskStatus = TaskStatus::from($currentTaskStatus); 
}
 catch (Throwable $e) 
{
 // Conversion failedas null $currentTaskStatus = null; 
}
 
}
 $this->currentTaskStatus = $currentTaskStatus; return $this; 
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
 /** * Getcreator user ID. */ 
    public function getCreatedUid(): string 
{
 return $this->createdUid; 
}
 /** * Set creator user ID. * @param mixed $createdUid */ 
    public function setCreatedUid($createdUid): self 
{
 // IfnullSet EmptyString $this->createdUid = $createdUid === null ? '' : (string) $createdUid; return $this; 
}
 /** * GetUpdateuser ID. */ 
    public function getUpdatedUid(): string 
{
 return $this->updatedUid; 
}
 /** * Set Updateuser ID. * @param mixed $updatedUid */ 
    public function setUpdatedUid($updatedUid): self 
{
 // IfnullSet EmptyString $this->updatedUid = $updatedUid === null ? '' : (string) $updatedUid; return $this; 
}
 /** * GetTaskSchema. */ 
    public function getTaskMode(): string 
{
 return $this->taskMode; 
}
 /** * Set TaskSchema. */ 
    public function setTaskMode(string $taskMode): self 
{
 $this->taskMode = $taskMode; return $this; 
}
 /** * Gettopic Schema. */ 
    public function getTopicMode(): string 
{
 return $this->topicMode; 
}
 /** * Set topic Schema. */ 
    public function setTopicMode(string $topicMode): self 
{
 $this->topicMode = $topicMode; return $this; 
}
 /** * Gettopic . */ 
    public function getCost(): float 
{
 return $this->cost; 
}
 /** * Set topic . * @param mixed $cost */ 
    public function setCost($cost): self 
{
 // WhenInputIs notFloatRowConvert if (! is_float($cost)) 
{
 $cost = (float) $cost; 
}
 $this->cost = $cost; return $this; 
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
 
    public function getWorkspaceCommitHash(): string 
{
 return $this->workspaceCommitHash; 
}
 
    public function setWorkspaceCommitHash(?string $workspaceCommitHash): self 
{
 $this->workspaceCommitHash = $workspaceCommitHash; return $this; 
}
 
    public function getChatHistoryCommitHash(): string 
{
 return $this->chatHistoryCommitHash; 
}
 
    public function setChatHistoryCommitHash(?string $chatHistoryCommitHash): self 
{
 $this->chatHistoryCommitHash = $chatHistoryCommitHash; return $this; 
}
 /** * Get source ID. */ 
    public function getSourceId(): ?string 
{
 return $this->sourceId; 
}
 /** * Set source ID. */ 
    public function setSourceId(?string $sourceId): self 
{
 $this->sourceId = $sourceId; return $this; 
}
 
}
 

<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity;

use App\Infrastructure\Core\AbstractEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
/** * Task. */

class TaskEntity extends AbstractEntity 
{
 /** * @var int primary key ID */ 
    protected int $id = 0; /** * @var string user ID */ 
    protected string $userId = ''; /** * @var int workspace ID */ 
    protected int $workspaceId = 0; /** * @var int Project ID */ 
    protected int $projectId = 0; /** * @var int topic ID */ 
    protected int $topicId = 0; /** * @var null|int CopySourceTaskID */ protected ?int $fromTaskId = null; /** * @var string TaskIDsandbox ServiceReturn  */ 
    protected string $taskId = ''; /** * @var string Sandbox ID */ 
    protected string $sandboxId = ''; /** * @var string user */ 
    protected string $prompt = ''; /** * @var string user Uploadinfo (JSONFormat) */ 
    protected string $attachments = ''; /** * @var string info (JSONFormat) */ protected ?string $mentions; /** * @var string TaskStatus */ 
    protected string $taskStatus = ''; /** * @var string workspace Directory */ 
    protected string $workDir = ''; /** * @var string TaskSchemachat: Schema, plan: Schema */ 
    protected string $taskMode = 'chat'; /** * @var null|string Error message */ protected ?string $errMsg = null; /** * @var null|string SessionID */ protected ?string $conversationId = null; /** * @var null|string TaskStart time */ protected ?string $startedAt = null; /** * @var null|string TaskEnd time */ protected ?string $finishedAt = null; /** * @var null|string Creation time */ protected ?string $createdAt = null; /** * @var null|string Update time */ protected ?string $updatedAt = null; /** * @var null|string Deletion time */ protected ?string $deletedAt = null; 
    public function __construct(array $data = []) 
{
 // DefaultSet $this->taskStatus = TaskStatus::WAITING->value; parent::__construct($data); 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 $result = [ 'id' => $this->id, 'user_id' => $this->userId, 'workspace_id' => $this->workspaceId, 'project_id' => $this->projectId, 'topic_id' => $this->topicId, 'from_task_id' => $this->fromTaskId, 'task_id' => $this->taskId, 'sandbox_id' => $this->sandboxId, 'prompt' => $this->prompt, 'attachments' => $this->attachments, 'mentions' => $this->getMentions(), 'task_status' => $this->taskStatus, 'work_dir' => $this->workDir, 'task_mode' => $this->taskMode, 'err_msg' => $this->errMsg, 'conversation_id' => $this->conversationId, 'started_at' => $this->startedAt, 'finished_at' => $this->finishedAt, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt, 'deleted_at' => $this->deletedAt, ]; // RemovenullValue return array_filter($result, function ($value) 
{
 return $value !== null; 
}
); 
}
 /** * Create DTO from array. */ 
    public 
    static function fromArray(array $data): self 
{
 return new self([ 'id' => $data['id'] ?? $data['Id'] ?? 0, 'user_id' => $data['user_id'] ?? $data['userId'] ?? '', 'workspace_id' => $data['workspace_id'] ?? $data['workspaceId'] ?? 0, 'project_id' => $data['project_id'] ?? $data['projectId'] ?? 0, 'topic_id' => $data['topic_id'] ?? $data['topicId'] ?? 0, 'from_task_id' => $data['from_task_id'] ?? $data['fromTaskId'] ?? null, 'task_id' => $data['task_id'] ?? $data['taskId'] ?? '', 'sandbox_id' => $data['sandbox_id'] ?? $data['sandboxId'] ?? '', 'prompt' => $data['prompt'] ?? '', 'attachments' => $data['attachments'] ?? '', 'mentions' => $data['mentions'] ?? null, 'task_status' => $data['task_status'] ?? $data['taskStatus'] ?? TaskStatus::WAITING->value, 'work_dir' => $data['work_dir'] ?? $data['workDir'] ?? '', 'task_mode' => $data['task_mode'] ?? $data['taskMode'] ?? 'chat', 'err_msg' => $data['err_msg'] ?? $data['errMsg'] ?? null, 'conversation_id' => $data['conversation_id'] ?? $data['conversationId'] ?? null, 'started_at' => $data['started_at'] ?? $data['startedAt'] ?? null, 'finished_at' => $data['finished_at'] ?? $data['finishedAt'] ?? null, 'created_at' => $data['created_at'] ?? $data['createdAt'] ?? null, 'updated_at' => $data['updated_at'] ?? $data['updatedAt'] ?? null, 'deleted_at' => $data['deleted_at'] ?? $data['deletedAt'] ?? null, ]); 
}
 
    public function getId(): int 
{
 return $this->id; 
}
 
    public function setId(int $id): self 
{
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
 
    public function getWorkspaceId(): int 
{
 return $this->workspaceId; 
}
 
    public function setWorkspaceId(int $workspaceId): self 
{
 $this->workspaceId = $workspaceId; return $this; 
}
 
    public function getProjectId(): int 
{
 return $this->projectId; 
}
 
    public function setProjectId(int $projectId): self 
{
 $this->projectId = $projectId; return $this; 
}
 
    public function getTopicId(): int 
{
 return $this->topicId; 
}
 
    public function setTopicId(int $topicId): self 
{
 $this->topicId = $topicId; return $this; 
}
 
    public function getFromTaskId(): ?int 
{
 return $this->fromTaskId; 
}
 
    public function setFromTaskId(?int $fromTaskId): self 
{
 $this->fromTaskId = $fromTaskId; return $this; 
}
 
    public function getTaskId(): string 
{
 return $this->taskId; 
}
 
    public function setTaskId(string $taskId): self 
{
 $this->taskId = $taskId; return $this; 
}
 
    public function getSandboxId(): string 
{
 return $this->sandboxId; 
}
 
    public function setSandboxId(string $sandboxId): self 
{
 $this->sandboxId = $sandboxId; return $this; 
}
 
    public function getPrompt(): string 
{
 return $this->prompt; 
}
 
    public function setPrompt(string $prompt): self 
{
 $this->prompt = $prompt; return $this; 
}
 
    public function getAttachments(): string 
{
 return $this->attachments; 
}
 
    public function setAttachments(string $attachments): self 
{
 $this->attachments = $attachments; return $this; 
}
 
    public function getMentions(): ?string 
{
 return $this->mentions ?? null; 
}
 
    public function setMentions(?string $mentions): self 
{
 $this->mentions = $mentions; return $this; 
}
 
    public function getTaskStatus(): string 
{
 return $this->taskStatus; 
}
 
    public function setTaskStatus(string $taskStatus): self 
{
 $this->taskStatus = $taskStatus; return $this; 
}
 /** * Set TaskStatusTaskStatus EnumType. */ 
    public function setStatus(TaskStatus $status): self 
{
 $this->taskStatus = $status->value; return $this; 
}
 /** * GetTaskStatusTaskStatus EnumType. */ 
    public function getStatus(): TaskStatus 
{
 return TaskStatus::from($this->taskStatus); 
}
 
    public function getWorkDir(): string 
{
 return $this->workDir; 
}
 
    public function setWorkDir(string $workDir): self 
{
 $this->workDir = $workDir; return $this; 
}
 
    public function getConversationId(): ?string 
{
 return $this->conversationId; 
}
 
    public function setConversationId(?string $conversationId): self 
{
 $this->conversationId = $conversationId; return $this; 
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
 /** * GetError message. */ 
    public function getErrMsg(): ?string 
{
 return $this->errMsg; 
}
 /** * Set Error message. */ 
    public function setErrMsg(?string $errMsg): self 
{
 $this->errMsg = $errMsg; return $this; 
}
 /** * GetTaskStart time. */ 
    public function getStartedAt(): ?string 
{
 return $this->startedAt; 
}
 /** * Set TaskStart time. */ 
    public function setStartedAt(?string $startedAt): self 
{
 $this->startedAt = $startedAt; return $this; 
}
 /** * GetTaskEnd time. */ 
    public function getFinishedAt(): ?string 
{
 return $this->finishedAt; 
}
 /** * Set TaskEnd time. */ 
    public function setFinishedAt(?string $finishedAt): self 
{
 $this->finishedAt = $finishedAt; return $this; 
}
 
}
 

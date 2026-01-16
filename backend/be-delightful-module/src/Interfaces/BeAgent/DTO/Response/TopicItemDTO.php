<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;

class TopicItemDTO extends AbstractDTO 
{
 /** * @var string topic ID */ 
    protected string $id = ''; /** * @var string user ID */ 
    protected string $userId = ''; /** * @var string topic ID */ 
    protected string $chatTopicId = ''; /** * @var string SessionID */ 
    protected string $chatConversationId = ''; /** * @var string topic Name */ 
    protected string $topicName = ''; /** * @var string TaskStatus */ 
    protected string $taskStatus = ''; /** * @var string Project ID */ 
    protected string $projectId = ''; /** * @var string workspace ID */ 
    protected string $workspaceId = ''; /** * @var string topic Schema */ 
    protected string $topicMode = ''; /** * @var string Sandbox ID */ 
    protected string $sandboxId = ''; /** * @var string Update time */ 
    protected string $updatedAt = ''; /** * FromCreate DTO. */ 
    public 
    static function fromEntity(TopicEntity $entity): self 
{
 $dto = new self(); $dto->setId((string) $entity->getId()); $dto->setuser Id($entity->getuser Id() ? (string) $entity->getuser Id() : ''); $dto->setChatTopicId($entity->getChatTopicId()); $dto->setChatConversationId($entity->getChatConversationId()); $dto->setTopicName($entity->getTopicName()); $dto->setTaskStatus($entity->getcurrent TaskStatus()->value); $dto->setProjectId($entity->getProjectId() ? (string) $entity->getProjectId() : ''); $dto->setWorkspaceId($entity->getWorkspaceId() ? (string) $entity->getWorkspaceId() : ''); $dto->setTopicMode($entity->getTopicMode()); $dto->setSandboxId($entity->getSandboxId()); $dto->setUpdatedAt($entity->getUpdatedAt()); return $dto; 
}
 
    public function getId(): string 
{
 return $this->id; 
}
 
    public function setId(string $id): self 
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
 
    public function getChatTopicId(): string 
{
 return $this->chatTopicId; 
}
 
    public function setChatTopicId(string $chatTopicId): self 
{
 $this->chatTopicId = $chatTopicId; return $this; 
}
 
    public function getChatConversationId(): string 
{
 return $this->chatConversationId; 
}
 
    public function setChatConversationId(string $chatConversationId): self 
{
 $this->chatConversationId = $chatConversationId; return $this; 
}
 
    public function getTopicName(): string 
{
 return $this->topicName; 
}
 
    public function setTopicName(string $topicName): self 
{
 $this->topicName = $topicName; return $this; 
}
 
    public function getTaskStatus(): string 
{
 return $this->taskStatus; 
}
 
    public function setTaskStatus(string $taskStatus): self 
{
 $this->taskStatus = $taskStatus; return $this; 
}
 
    public function getProjectId(): string 
{
 return $this->projectId; 
}
 
    public function setProjectId(string $projectId): self 
{
 $this->projectId = $projectId; return $this; 
}
 
    public function getWorkspaceId(): string 
{
 return $this->workspaceId; 
}
 
    public function setWorkspaceId(string $workspaceId): self 
{
 $this->workspaceId = $workspaceId; return $this; 
}
 
    public function getTopicMode(): string 
{
 return $this->topicMode; 
}
 
    public function setTopicMode(string $topicMode): self 
{
 $this->topicMode = $topicMode; return $this; 
}
 
    public function getSandboxId(): string 
{
 return $this->sandboxId; 
}
 
    public function setSandboxId(string $sandboxId): self 
{
 $this->sandboxId = $sandboxId; return $this; 
}
 
    public function getUpdatedAt(): string 
{
 return $this->updatedAt; 
}
 
    public function setUpdatedAt(string $updatedAt): self 
{
 $this->updatedAt = $updatedAt; return $this; 
}
 /** * FromArrayCreateDTO. */ 
    public 
    static function fromArray(array $data): self 
{
 $dto = new self(); $dto->id = (string) $data['id']; $dto->userId = isset($data['user_id']) ? (string) $data['user_id'] : ''; $dto->chatTopicId = $data['chat_topic_id'] ?? ''; $dto->chatConversationId = $data['chat_conversation_id'] ?? ''; $dto->topicName = $data['topic_name'] ?? $data['name'] ?? ''; $dto->taskStatus = $data['task_status'] ?? $data['current_task_status'] ?? ''; $dto->projectId = isset($data['project_id']) ? (string) $data['project_id'] : ''; $dto->workspaceId = isset($data['workspace_id']) ? (string) $data['workspace_id'] : ''; $dto->topicMode = $data['topic_mode'] ?? 'general'; $dto->sandboxId = $data['sandbox_id'] ?? ''; $dto->updatedAt = $data['updated_at'] ?? ''; return $dto; 
}
 /** * Convert toArray. * OutputUnderlineAPICompatible. */ 
    public function toArray(): array 
{
 return [ 'id' => $this->id, 'user_id' => $this->userId, 'chat_topic_id' => $this->chatTopicId, 'chat_conversation_id' => $this->chatConversationId, 'topic_name' => $this->topicName, 'task_status' => $this->taskStatus, 'project_id' => $this->projectId, 'workspace_id' => $this->workspaceId, 'topic_mode' => $this->topicMode, 'sandbox_id' => $this->sandboxId, 'updated_at' => $this->updatedAt, ]; 
}
 
}
 

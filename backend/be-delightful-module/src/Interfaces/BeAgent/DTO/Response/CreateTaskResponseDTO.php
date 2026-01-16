<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskEntity;

class CreateTaskResponseDTO 
{
 /** * @var string TaskID */ 
    protected string $taskId; /** * @var string TaskStatus */ 
    protected string $status; /** * @var string Creation time */ 
    protected string $createdAt; /** * Function. */ 
    public function __construct(int $taskId, string $status, string $createdAt) 
{
 $this->taskId = (string) $taskId; $this->status = $status; $this->createdAt = $createdAt; 
}
 /** * FromTaskCreateResponseDTO. */ 
    public 
    static function fromEntity(TaskEntity $entity): self 
{
 return new self( $entity->getId(), $entity->getStatus()->value, $entity->getCreatedAt() ); 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'task_id' => $this->taskId, 'status' => $this->status, 'created_at' => $this->createdAt, ]; 
}
 /** * GetTaskID. */ 
    public function getTaskId(): string 
{
 return $this->taskId; 
}
 /** * GetTaskStatus */ 
    public function getStatus(): string 
{
 return $this->status; 
}
 /** * GetCreation time. */ 
    public function getCreatedAt(): string 
{
 return $this->createdAt; 
}
 
}
 

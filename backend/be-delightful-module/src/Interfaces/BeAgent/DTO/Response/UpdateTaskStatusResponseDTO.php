<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

class UpdateTaskStatusResponseDTO 
{
 /** * @var string TaskID */ 
    protected string $taskId; /** * @var string TaskStatus */ 
    protected string $status; /** * Function. */ 
    public function __construct(int $taskId, string $status) 
{
 $this->taskId = (string) $taskId; $this->status = $status; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'task_id' => $this->taskId, 'status' => $this->status, ]; 
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
 
}
 

<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

/** * topic CopyResponseDTO. */

class DuplicateTopicResponseDTO 
{
 /** * TaskKey. */ 
    protected string $taskKey; /** * TaskStatus */ 
    protected string $status; /** * Newtopic ID. */ protected ?string $topicId; /** * StatusMessage. */ 
    protected string $message; /** * Function. */ 
    public function __construct( string $taskKey, string $status, ?string $topicId = null, string $message = '' ) 
{
 $this->taskKey = $taskKey; $this->status = $status; $this->topicId = $topicId; $this->message = $message; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'task_key' => $this->taskKey, 'status' => $this->status, 'topic_id' => $this->topicId, 'message' => $this->message, ]; 
}
 /** * GetTaskKey. */ 
    public function getTaskKey(): string 
{
 return $this->taskKey; 
}
 /** * Set TaskKey. */ 
    public function setTaskKey(string $taskKey): self 
{
 $this->taskKey = $taskKey; return $this; 
}
 /** * GetTaskStatus */ 
    public function getStatus(): string 
{
 return $this->status; 
}
 /** * Set TaskStatus */ 
    public function setStatus(string $status): self 
{
 $this->status = $status; return $this; 
}
 /** * GetNewtopic ID. */ 
    public function getTopicId(): ?string 
{
 return $this->topicId; 
}
 /** * Set Newtopic ID. */ 
    public function setTopicId(?string $topicId): self 
{
 $this->topicId = $topicId; return $this; 
}
 /** * GetStatusMessage. */ 
    public function getMessage(): string 
{
 return $this->message; 
}
 /** * Set StatusMessage. */ 
    public function setMessage(string $message): self 
{
 $this->message = $message; return $this; 
}
 
}
 

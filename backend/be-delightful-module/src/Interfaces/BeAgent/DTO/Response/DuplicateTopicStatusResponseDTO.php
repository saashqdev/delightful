<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

/** * topic CopyStatusResponseDTO. */

class DuplicateTopicStatusResponseDTO 
{
 /** * TaskID. */ 
    protected string $taskId; /** * TaskStatus (running, completed, failed). */ 
    protected string $status; /** * StatusMessage. */ 
    protected string $message; /** * info . */ protected ?array $progress = null; /** * Resultinfo Taskcomplete . */ protected ?array $result = null; /** * Error messageTaskFailed. */ protected ?string $error = null; /** * Function. */ 
    public function __construct( string $taskId, string $status, string $message = '', ?array $progress = null, ?array $result = null, ?string $error = null ) 
{
 $this->taskId = $taskId; $this->status = $status; $this->message = $message; $this->progress = $progress; $this->result = $result; $this->error = $error; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 $result = [ 'task_id' => $this->taskId, 'status' => $this->status, 'message' => $this->message, ]; if ($this->progress !== null) 
{
 $result['progress'] = $this->progress; 
}
 if ($this->result !== null) 
{
 $result['result'] = $this->result; 
}
 if ($this->error !== null) 
{
 $result['error'] = $this->error; 
}
 return $result; 
}
 /** * FromArrayCreateDTOInstance. */ 
    public 
    static function fromArray(array $data): self 
{
 return new self( $data['task_id'], $data['status'], $data['message'] ?? '', $data['progress'] ?? null, $data['result'] ?? null, $data['error'] ?? null ); 
}
 /** * GetTaskID. */ 
    public function getTaskId(): string 
{
 return $this->taskId; 
}
 /** * Set TaskID. */ 
    public function setTaskId(string $taskId): self 
{
 $this->taskId = $taskId; return $this; 
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
 /** * Getinfo . */ 
    public function getProgress(): ?array 
{
 return $this->progress; 
}
 /** * Set info . */ 
    public function setProgress(?array $progress): self 
{
 $this->progress = $progress; return $this; 
}
 /** * GetResultinfo . */ 
    public function getResult(): ?array 
{
 return $this->result; 
}
 /** * Set Resultinfo . */ 
    public function setResult(?array $result): self 
{
 $this->result = $result; return $this; 
}
 /** * GetError message. */ 
    public function getError(): ?string 
{
 return $this->error; 
}
 /** * Set Error message. */ 
    public function setError(?string $error): self 
{
 $this->error = $error; return $this; 
}
 
}
 

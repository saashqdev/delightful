<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use Hyperf\Validate \Request\FormRequest;

class UpdateTaskStatusRequestDTO extends FormRequest 
{
 /** * @var string TaskID */ 
    protected string $taskId = ''; /** * @var string TaskStatus */ 
    protected string $status = ''; /** * Validate Rule. */ 
    public function rules(): array 
{
 return [ 'task_id' => 'required|string', 'status' => 'required|string|in:waiting,running,finished,error', ]; 
}
 /** * PropertyName. */ 
    public function attributes(): array 
{
 return [ 'task_id' => 'TaskID', 'status' => 'TaskStatus', ]; 
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
 
    public function setTaskId(string $taskId): self 
{
 $this->taskId = $taskId; return $this; 
}
 
    public function setStatus(string $status): self 
{
 $this->status = $status; return $this; 
}
 /** * Data. */ 
    protected function prepareForValidate (): void 
{
 $this->taskId = (string) $this->input('task_id', ''); $this->status = (string) $this->input('status', ''); 
}
 
}
 

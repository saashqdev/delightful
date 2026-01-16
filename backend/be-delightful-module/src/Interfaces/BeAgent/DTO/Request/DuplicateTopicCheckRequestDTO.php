<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;
/** * check topic CopyStatusRequestDTO. */

class DuplicateTopiccheck RequestDTO extends AbstractRequestDTO 
{
 /** * TaskKey. */ 
    public string $taskKey = ''; /** * GetTaskKey. */ 
    public function getTaskKey(): string 
{
 return $this->taskKey; 
}
 /** * Get validation rules. */ 
    protected 
    static function getHyperfValidate Rules(): array 
{
 return [ 'task_key' => 'required|string', ]; 
}
 /** * Get custom error messages for validation failures. */ 
    protected 
    static function getHyperfValidate Message(): array 
{
 return [ 'task_key.required' => 'Task key is required', 'task_key.string' => 'Task key must be a string', ]; 
}
 
}
 

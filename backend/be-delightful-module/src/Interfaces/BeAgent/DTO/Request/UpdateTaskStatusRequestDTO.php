<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use Hyperf\Validation\Request\FormRequest;

class UpdateTaskStatusRequestDTO extends FormRequest
{
    /**
     * @var string Task ID
     */
    protected string $taskId = '';

    /**
     * @var string Task status
     */
    protected string $status = '';

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'task_id' => 'required|string',
            'status' => 'required|string|in:waiting,running,finished,error',
        ];
    }

    /**
     * Attribute names.
     */
    public function attributes(): array
    {
        return [
            'task_id' => 'Task ID',
            'status' => 'Task status',
        ];
    }

    /**
     * Get task ID.
     */
    public function getTaskId(): string
    {
        return $this->taskId;
    }

    /**
     * Get task status
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setTaskId(string $taskId): self
    {
        $this->taskId = $taskId;
        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Prepare data.
     */
    protected function prepareForValidation(): void
    {
        $this->taskId = (string) $this->input('task_id', '');
        $this->status = (string) $this->input('status', '');
    }
}

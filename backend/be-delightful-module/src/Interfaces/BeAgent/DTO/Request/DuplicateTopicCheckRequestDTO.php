<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

/**
 * Check topic duplication status request DTO.
 */
class DuplicateTopicCheckRequestDTO extends AbstractRequestDTO
{
    /**
     * Task key.
     */
    public string $taskKey = '';

    /**
     * Get task key.
     */
    public function getTaskKey(): string
    {
        return $this->taskKey;
    }

    /**
     * Get validation rules.
     */
    protected static function getHyperfValidationRules(): array
    {
        return [
            'task_key' => 'required|string',
        ];
    }

    /**
     * Get custom error messages for validation failures.
     */
    protected static function getHyperfValidationMessage(): array
    {
        return [
            'task_key.required' => 'Task key is required',
            'task_key.string' => 'Task key must be a string',
        ];
    }
}

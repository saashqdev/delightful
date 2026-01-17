<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

class CreateScriptTaskRequestDTO extends AbstractRequestDTO
{
    protected string $taskId = '';

    protected string $scriptName = '';

    protected array $arguments = [];

    protected string $sandboxId = '';

    /**
     * Validation rules.
     */
    public static function getHyperfValidationRules(): array
    {
        return [
            'task_id' => 'string',
            'script_name' => 'required|string',
            'arguments' => 'required|array',
        ];
    }

    public static function getHyperfValidationMessage(): array
    {
        return [
            'task_id.string' => 'Task ID cannot be empty',
            'script_name.required' => 'Script name cannot be empty',
            'arguments.required' => 'Script arguments cannot be empty',
        ];
    }

    /**
     * Attribute names.
     */
    public function attributes(): array
    {
        return [
            'task_id' => 'Task ID',
            'script_name' => 'Script name',
            'arguments' => 'Script arguments',
        ];
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function getScriptName(): string
    {
        return $this->scriptName;
    }

    public function setScriptName(string $scriptName): void
    {
        $this->scriptName = $scriptName;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function getSandboxId(): string
    {
        return $this->sandboxId;
    }

    public function setSandboxId(string $sandboxId): void
    {
        $this->sandboxId = $sandboxId;
    }
}

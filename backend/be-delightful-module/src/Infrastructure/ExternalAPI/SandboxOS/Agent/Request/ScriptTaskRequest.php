<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

/**
 * Script task request class
 * Follows the sandbox communication script task request format.
 */
class ScriptTaskRequest
{
    public function __construct(
        private string $taskId = '',
        private array $arguments = [],
        private string $scriptName = '',
    ) {
    }

    /**
     * Create a script task request
     */
    public static function create(string $taskId, array $arguments, string $scriptName): self
    {
        return new self($taskId, $arguments, $scriptName);
    }

    /**
     * Set task ID.
     */
    public function setTaskId(string $taskId): self
    {
        $this->taskId = $taskId;
        return $this;
    }

    /**
     * Get task ID.
     */
    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setScriptName(string $scriptName): self
    {
        $this->scriptName = $scriptName;
        return $this;
    }

    public function getScriptName(): string
    {
        return $this->scriptName;
    }

    /**
     * Convert to API request array
     * Matches the sandbox communication script task request format.
     */
    public function toArray(): array
    {
        return [
            'task_id' => $this->taskId,
            'arguments' => $this->arguments,
            'script_name' => $this->scriptName,
        ];
    }
}

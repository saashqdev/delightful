<?php

declare(strict_types=1);
/**
 * This file is part of Dtyq.
 */

namespace Dtyq\CodeExecutor;

class ExecutionRequest implements \JsonSerializable
{
    /**
     * @param Language $language The language to execute
     * @param string $code The code to execute
     * @param array<string, mixed> $args Parameters passed to code execution
     * @param int $timeout Execution timeout (seconds)
     */
    public function __construct(
        private Language $language,
        private string $code,
        private array $args = [],
        private int $timeout = 30
    ) {}

    public function getLanguage(): Language
    {
        return $this->language;
    }

    public function setLanguage(Language $language): ExecutionRequest
    {
        $this->language = $language;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): ExecutionRequest
    {
        $this->code = $code;
        return $this;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function setArgs(array $args): ExecutionRequest
    {
        $this->args = $args;
        return $this;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): ExecutionRequest
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}

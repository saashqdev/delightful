<?php

declare(strict_types=1);
/**
 * This file is part of Delightful.
 */

namespace Delightful\CodeExecutor;

class ExecutionResult
{
    /**
     * @param string $output Execution output
     * @param int $duration Execution time (milliseconds)
     * @param array<string, mixed> $result Execution result data
     */
    public function __construct(
        private readonly string $output = '',
        private readonly int $duration = 0,
        private readonly array $result = []
    ) {}

    /**
     * Get execution output.
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * Get execution time (milliseconds).
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * Get execution result data.
     *
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * Check if execution was successful
     */
    public function isSuccessful(): bool
    {
        return $this->code === 0;
    }
}

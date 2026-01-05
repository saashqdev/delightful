<?php

declare(strict_types=1);
/**
 * This file is part of Dtyq.
 */

namespace Delightful\CodeExecutor\Contract;

use Delightful\CodeExecutor\ExecutionRequest;
use Delightful\CodeExecutor\ExecutionResult;
use Delightful\CodeExecutor\Language;

interface ExecutorInterface
{
    /**
     * Initialize the executor.
     */
    public function initialize(): void;

    /**
     * Execute code
     *
     * @param ExecutionRequest $request Execution request
     * @return ExecutionResult Execution result
     */
    public function execute(ExecutionRequest $request): ExecutionResult;

    /**
     * Get the list of supported languages.
     *
     * @return array<int, Language> List of supported languages
     */
    public function getSupportedLanguages(): array;
}

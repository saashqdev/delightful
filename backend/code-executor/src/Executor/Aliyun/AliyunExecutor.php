<?php

declare(strict_types=1);
/**
 * This file is part of Dtyq.
 */

namespace Delightful\CodeExecutor\Executor\Aliyun;

use Delightful\CodeExecutor\AbstractExecutor;
use Delightful\CodeExecutor\Exception\ExecuteException;
use Delightful\CodeExecutor\Exception\InvalidArgumentException;
use Delightful\CodeExecutor\ExecutionRequest;
use Delightful\CodeExecutor\ExecutionResult;
use Delightful\CodeExecutor\Executor\Aliyun\Exception\CreateFunctionException;
use Delightful\CodeExecutor\Executor\Aliyun\Exception\GetFunctionException;
use Delightful\CodeExecutor\Executor\Aliyun\Exception\UpdateFunctionException;

class AliyunExecutor extends AbstractExecutor
{
    public function __construct(protected AliyunRuntimeClient $client) {}

    /**
     * @throws CreateFunctionException
     * @throws GetFunctionException
     * @throws InvalidArgumentException
     * @throws UpdateFunctionException
     */
    public function initialize(): void
    {
        $this->client->initialize();
    }

    public function getSupportedLanguages(): array
    {
        return $this->client->getSupportedLanguages();
    }

    /**
     * @throws ExecuteException
     * @throws InvalidArgumentException
     */
    protected function doExecute(ExecutionRequest $request): ExecutionResult
    {
        return $this->client->invoke($request);
    }
}

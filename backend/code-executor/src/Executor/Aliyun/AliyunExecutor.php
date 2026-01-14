<?php

declare(strict_types=1);
/**
 * This file is part of Delightful.
 */

namespace Delightful\CodeExecutor\Executor\Aliyun;

use BeDelightful\CodeExecutor\AbstractExecutor;
use BeDelightful\CodeExecutor\Exception\ExecuteException;
use BeDelightful\CodeExecutor\Exception\InvalidArgumentException;
use BeDelightful\CodeExecutor\ExecutionRequest;
use BeDelightful\CodeExecutor\ExecutionResult;
use BeDelightful\CodeExecutor\Executor\Aliyun\Exception\CreateFunctionException;
use BeDelightful\CodeExecutor\Executor\Aliyun\Exception\GetFunctionException;
use BeDelightful\CodeExecutor\Executor\Aliyun\Exception\UpdateFunctionException;

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

<?php

declare(strict_types=1);
/**
 * This file is part of Delightful.
 */

namespace Delightful\CodeExecutor\Utils;

use BeDelightful\CodeExecutor\Enums\StatusCode;
use BeDelightful\CodeExecutor\Exception\ExecuteException;
use BeDelightful\CodeExecutor\Exception\ExecuteFailedException;
use BeDelightful\CodeExecutor\Exception\ExecuteTimeoutException;
use BeDelightful\CodeExecutor\Exception\InvalidArgumentException;
use BeDelightful\CodeExecutor\ExecutionResult;
use Hyperf\Codec\Json;

/**
 * Remove opening and closing tags from PHP code.
 * Only processes tags at the beginning and end of the string, does not affect content in the middle.
 *
 * @param string $code PHP code
 * @return string Code with tags removed
 */
function stripPHPTags(string $code): string
{
    // Remove opening PHP tag
    $code = preg_replace('/^\s*<\?(php)?/i', '', $code);

    // Remove closing PHP tag
    return preg_replace('/\?>\s*$/', '', $code);
}

/**
 * @throws ExecuteException
 * @throws InvalidArgumentException
 * @throws ExecuteTimeoutException
 * @throws ExecuteFailedException
 */
function parseExecutionResult(string $response): ExecutionResult
{
    if (empty($contents = Json::decode($response))) {
        throw new ExecuteFailedException('Failed to decode the result of the function call: ' . $response);
    }

    $code = StatusCode::tryFrom($contents['code'] ?? '');
    if (empty($code) || $code !== StatusCode::OK) {
        if (empty($message = $contents['message'] ?? null)) {
            // try to get aliyun error message
            $message = $contents['errorMessage'] ?? Json::encode($contents);
        }
        match ($code) {
            StatusCode::INVALID_PARAMS => throw new InvalidArgumentException($message),
            StatusCode::EXECUTE_FAILED => throw new ExecuteFailedException($message),
            StatusCode::EXECUTE_TIMEOUT => throw new ExecuteTimeoutException($message),
            default => throw new ExecuteException($message),
        };
    }

    return new ExecutionResult(
        output: strval($contents['data']['output'] ?? ''),
        duration: intval($contents['data']['duration'] ?? 0),
        result: (array) ($contents['data']['result'] ?? [])
    );
}

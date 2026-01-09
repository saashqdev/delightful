<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Exception\Handler;

use App\Infrastructure\Core\MCP\Exception\MCPException;
use App\Infrastructure\Core\MCP\Types\Message\ErrorResponse;
use Hyperf\Logger\LoggerFactory;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class MCPExceptionHandler
{
    private LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('MCPException');
    }

    /**
     * processexception并转换为标准errorresponse.
     */
    public function handle(Throwable $exception, int $id = 0, string $jsonrpc = '2.0'): ErrorResponse
    {
        $code = $this->getErrorCode($exception);
        $message = $this->getErrorMessage($exception);

        $this->logError($exception, [
            'id' => $id,
            'jsonrpc' => $jsonrpc,
            'code' => $code,
            'message' => $message,
        ]);

        return new ErrorResponse(
            id: $id,
            jsonrpc: $jsonrpc,
            throwable: $exception
        );
    }

    /**
     * geterror代码.
     */
    protected function getErrorCode(Throwable $exception): int
    {
        if ($exception instanceof MCPException) {
            return $exception->getRpcCode();
        }

        // 对于其他type的exception，use标准映射
        return match (true) {
            $exception instanceof InvalidArgumentException => -32602, // Invalid params
            $exception instanceof RuntimeException => -32603, // Internal error
            default => -32000, // Server error
        };
    }

    /**
     * geterrormessage.
     */
    protected function getErrorMessage(Throwable $exception): string
    {
        return $exception->getMessage() ?: '未知error';
    }

    /**
     * 记录详细errorinformation.
     */
    private function logError(Throwable $exception, array $context = []): void
    {
        $this->logger->error('MCPExceptionOccurred', array_merge([
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ], $context));
    }
}

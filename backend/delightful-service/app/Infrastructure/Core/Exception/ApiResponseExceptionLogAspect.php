<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Exception;

use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Throwable;

#[Aspect]
/**
 * 1.为了不让user看到一些sql/代码exception,因此will在 config/api-response.php 的 error_exception configuration中,将意外的exception转换为统一的系统内部errorexception.
 * 2.logrecordexceptioninfo,便于排查issue.
 */
class ApiResponseExceptionLogAspect extends AbstractAspect
{
    // 优先级,value越小优先级越高
    public ?int $priority = 1;

    public array $annotations = [
        ApiResponse::class,
    ];

    public function __construct(private readonly StdoutLoggerInterface $logger)
    {
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        try {
            return $proceedingJoinPoint->process();
        } catch (Throwable $exception) {
            // 一些兜底exception的log打印,可能存在重复log打印,但是为了保证exceptioninfo不丢失,所以这里不做判断.
            $this->logger->error(
                __CLASS__ . ' 发生exception message:{message}, code:{code}, file:{file}, line:{line}, trace:{trace}',
                [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );
            throw $exception;
        }
    }
}

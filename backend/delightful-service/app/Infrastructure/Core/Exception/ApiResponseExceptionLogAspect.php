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
 * 1.为了not让user看to一些sql/codeexception,thereforewillin config/api-response.php 的 error_exception configurationmiddle,将意outside的exceptionconvert为统一的systeminside部errorexception.
 * 2.logrecordexceptioninfo,便atrow查issue.
 */
class ApiResponseExceptionLogAspect extends AbstractAspect
{
    // 优先level,valuemore小优先levelmore高
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
            // 一些兜bottomexception的logprint,可能存in重复logprint,but是为了保证exceptioninfonot丢失,所by这withinnot做判断.
            $this->logger->error(
                __CLASS__ . ' hair生exception message:{message}, code:{code}, file:{file}, line:{line}, trace:{trace}',
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

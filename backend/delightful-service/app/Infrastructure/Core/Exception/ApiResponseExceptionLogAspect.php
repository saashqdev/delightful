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
 * 1.fornotletuser看toonethesesql/codeexception,thereforewillin config/api-response.php  error_exception configurationmiddle,will意outsideexceptionconvertfor统onesysteminside部errorexception.
 * 2.logrecordexceptioninfo,便atrow查issue.
 */
class ApiResponseExceptionLogAspect extends AbstractAspect
{
    // prioritylevel,valuemoresmallprioritylevelmorehigh
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
            // onethese兜bottomexceptionlogprint,maybe存induplicatelogprint,butisforguaranteeexceptioninfonot丢失,所bythiswithinnot做judge.
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

<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Exception;

use RuntimeException;
use Throwable;
/** * sandbox ExceptionClass * for process sandbox related Exception. */

class SandboxOperationException extends RuntimeException 
{
 
    public function __construct(string $operation, string $message, int $code = 0, ?Throwable $previous = null) 
{
 parent::__construct(sprintf('%s failed: %s', $operation, $message), $code, $previous); 
}
 
}
 

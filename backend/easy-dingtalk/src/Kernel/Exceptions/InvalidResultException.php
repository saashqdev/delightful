<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\Kernel\Exceptions;

use BeDelightful\EasyDingTalk\Kernel\Constants\ErrorCode;
use Throwable;

class InvalidResultException extends EasyDingTalkException
{
    public function __construct(string $message = 'Invalid response', int $code = ErrorCode::INVALID_RESULT, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

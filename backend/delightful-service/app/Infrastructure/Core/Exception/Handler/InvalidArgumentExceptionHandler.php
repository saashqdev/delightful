<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Exception\Handler;

use App\Infrastructure\Util\SSRF\Exception\SSRFException;
use Delightful\FlowExprEngine\Exception\FlowExprEngineException;
use Throwable;

class InvalidArgumentExceptionHandler extends BusinessExceptionHandler
{
    public function isValid(Throwable $throwable): bool
    {
        if ($throwable->getPrevious() instanceof FlowExprEngineException || $throwable instanceof FlowExprEngineException) {
            return true;
        }
        if ($throwable instanceof SSRFException) {
            return true;
        }
        return false;
    }
}

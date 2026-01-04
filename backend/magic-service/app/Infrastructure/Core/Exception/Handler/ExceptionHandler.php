<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Infrastructure\Core\Exception\Handler;

use Throwable;

class ExceptionHandler extends BusinessExceptionHandler
{
    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}

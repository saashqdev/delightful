<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Test\Mock;

use Psr\Log\AbstractLogger;

class EchoLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        echo "[{$level}]{$message} " . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}

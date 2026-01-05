<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Tests;

use Psr\Log\AbstractLogger;

class EchoLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        echo "[{$level}]" . $message . ' ' . json_encode($context) . PHP_EOL;
    }
}

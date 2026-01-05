<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\PHPCodeExecutor;

class PHPExecuteResult
{
    private mixed $result;

    private string $debug;

    public function __construct(mixed $result, string $debug)
    {
        $this->result = $result;
        $this->debug = $debug;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getDebug(): string
    {
        return $this->debug;
    }
}

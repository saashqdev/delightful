<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Exception;

use Throwable;

class MethodNotFoundException extends MCPException
{
    /**
     * JSON-RPCerror码.
     */
    protected int $rpcCode = -32601;

    public function __construct(string $message = 'Method not found', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

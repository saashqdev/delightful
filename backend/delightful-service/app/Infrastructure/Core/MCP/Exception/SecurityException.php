<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Exception;

use Throwable;

class SecurityException extends MCPException
{
    /**
     * JSON-RPCerror码.
     * usecustomizeerror码范围: -32000 到 -32099.
     */
    protected int $rpcCode = -32050;

    public function __construct(string $message = 'Security constraint violation', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

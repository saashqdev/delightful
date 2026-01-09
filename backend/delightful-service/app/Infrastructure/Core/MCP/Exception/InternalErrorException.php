<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Exception;

use Throwable;

class InternalErrorException extends MCPException
{
    /**
     * JSON-RPCerror码.
     */
    protected int $rpcCode = -32603;

    public function __construct(string $message = 'Internal error', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

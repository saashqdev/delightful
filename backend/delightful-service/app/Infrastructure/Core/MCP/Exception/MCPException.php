<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Exception;

use RuntimeException;

class MCPException extends RuntimeException
{
    /**
     * JSON-RPCerrorcode.
     */
    protected int $rpcCode = -32000;

    /**
     * getJSON-RPCerrorcode.
     */
    public function getRpcCode(): int
    {
        return $this->rpcCode;
    }

    /**
     * settingJSON-RPCerrorcode.
     */
    public function setRpcCode(int $code): self
    {
        $this->rpcCode = $code;
        return $this;
    }
}

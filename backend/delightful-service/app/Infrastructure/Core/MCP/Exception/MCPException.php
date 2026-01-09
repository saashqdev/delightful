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
     * JSON-RPCerror码.
     */
    protected int $rpcCode = -32000;

    /**
     * getJSON-RPCerror码.
     */
    public function getRpcCode(): int
    {
        return $this->rpcCode;
    }

    /**
     * settingJSON-RPCerror码.
     */
    public function setRpcCode(int $code): self
    {
        $this->rpcCode = $code;
        return $this;
    }
}

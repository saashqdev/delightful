<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\MCP\Utils\MCPExecutor;

use BeDelightful\PhpMcp\Types\Responses\ListToolsResult;
use Hyperf\Odin\Mcp\McpServerConfig;

interface MCPServerExecutorInterface
{
    public function getListToolsResult(McpServerConfig $mcpServerConfig): ?ListToolsResult;
}

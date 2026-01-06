<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\MCP\SupperDelightfulMCP;

use App\Domain\MCP\Entity\ValueObject\MCPDataIsolation;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskContext;

interface SupperDelightfulAgentMCPInterface
{
    public function createChatMessageRequestMcpConfig(MCPDataIsolation $dataIsolation, TaskContext $taskContext, array $agentIds = [], array $mcpIds = [], array $toolIds = []): ?array;
}

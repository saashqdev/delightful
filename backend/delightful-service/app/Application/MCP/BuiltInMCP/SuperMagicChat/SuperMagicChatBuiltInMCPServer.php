<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\MCP\BuiltInMCP\BeDelightfulChat;

use App\Domain\MCP\Entity\MCPServerEntity;
use App\Domain\MCP\Entity\ValueObject\MCPDataIsolation;
use App\Domain\MCP\Entity\ValueObject\ServiceType;
use App\Infrastructure\Core\Collector\BuiltInMCP\Annotation\BuiltInMCPServerDefine;
use App\Infrastructure\Core\Contract\MCP\BuiltInMCPServerInterface;

#[BuiltInMCPServerDefine(serverCode: 'super_delightful_chat', enabled: true, priority: 1)]
class BeDelightfulChatBuiltInMCPServer implements BuiltInMCPServerInterface
{
    private static string $codePrefix = 'super-delightful-chat-';

    private static string $serverName = 'BeDelightfulChat';

    public function __construct()
    {
    }

    public static function createByChatParams(MCPDataIsolation $MCPDataIsolation, array $agentIds = [], array $toolIds = []): ?MCPServerEntity
    {
        if (empty($agentIds) && empty($toolIds)) {
            return null;
        }
        $mcpServerCode = uniqid(self::$codePrefix);
        BeDelightfulChatManager::createByChatParams($MCPDataIsolation, $mcpServerCode, $agentIds, $toolIds);
        $MCPServerEntity = new MCPServerEntity();
        $MCPServerEntity->setBuiltIn(true);
        $MCPServerEntity->setCode($mcpServerCode);
        $MCPServerEntity->setEnabled(true);
        $MCPServerEntity->setType(ServiceType::SSE);
        $MCPServerEntity->setName(self::$serverName);
        $MCPServerEntity->setServiceConfig($MCPServerEntity->getType()->createServiceConfig([]));
        return $MCPServerEntity;
    }

    public static function match(string $mcpServerCode): bool
    {
        return str_starts_with($mcpServerCode, self::$codePrefix);
    }

    public function getServerCode(): string
    {
        return 'super_delightful_chat';
    }

    public function getServerName(): string
    {
        return self::$serverName;
    }

    public function getRegisteredTools(string $mcpServerCode): array
    {
        return BeDelightfulChatManager::getRegisteredTools($mcpServerCode);
    }
}

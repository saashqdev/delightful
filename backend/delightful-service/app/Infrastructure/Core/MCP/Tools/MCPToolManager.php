<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Tools;

class MCPToolManager
{
    /**
     * @var array<string, MCPTool>
     */
    private array $tools = [];

    /**
     * registertool.
     */
    public function registerTool(MCPTool $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    /**
     * get所haveregistertool.
     *
     * @return array<string, MCPTool>
     */
    public function getTools(): array
    {
        return $this->tools;
    }

    /**
     * gettoolcolumntableSchemashapetype.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToolSchemas(): array
    {
        $schemas = [];
        foreach ($this->tools as $tool) {
            $schemas[] = $tool->toScheme();
        }
        return $schemas;
    }

    /**
     * getfinger定nametool.
     */
    public function getTool(string $name): ?MCPTool
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * checkfinger定nametoolwhether存in.
     */
    public function hasTool(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * checkwhethernothaveanytool.
     */
    public function isEmpty(): bool
    {
        return empty($this->tools);
    }
}

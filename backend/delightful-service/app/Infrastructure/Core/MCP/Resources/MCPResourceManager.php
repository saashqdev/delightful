<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Resources;

/**
 * MCP资源manager.
 * when前version为nullimplement，仅returnnull列表.
 */
class MCPResourceManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $resources = [];

    /**
     * register资源.
     * when前为nullimplement.
     */
    public function registerResource(array $resource): void
    {
        // nullimplement，暂notregister任何资源
    }

    /**
     * get资源列表.
     * when前为nullimplement，returnnullarray.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getResources(): array
    {
        return [];
    }

    /**
     * get指定ID的资源.
     * when前为nullimplement，始终returnnull.
     */
    public function getResource(string $id): ?array
    {
        return null;
    }

    /**
     * check指定ID的资源whether存in.
     */
    public function hasResource(string $id): bool
    {
        return isset($this->resources[$id]);
    }

    /**
     * checkwhethernothave任何资源.
     */
    public function isEmpty(): bool
    {
        return empty($this->resources);
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Resources;

/**
 * MCPresourcemanager.
 * whenfrontversion为nullimplement，仅returnnullcolumn表.
 */
class MCPResourceManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $resources = [];

    /**
     * registerresource.
     * whenfront为nullimplement.
     */
    public function registerResource(array $resource): void
    {
        // nullimplement，暂notregister任何resource
    }

    /**
     * getresourcecolumn表.
     * whenfront为nullimplement，returnnullarray.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getResources(): array
    {
        return [];
    }

    /**
     * getfinger定ID的resource.
     * whenfront为nullimplement，始终returnnull.
     */
    public function getResource(string $id): ?array
    {
        return null;
    }

    /**
     * checkfinger定ID的resourcewhether存in.
     */
    public function hasResource(string $id): bool
    {
        return isset($this->resources[$id]);
    }

    /**
     * checkwhethernothave任何resource.
     */
    public function isEmpty(): bool
    {
        return empty($this->resources);
    }
}

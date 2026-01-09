<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Resources;

/**
 * MCPresourcemanager.
 * whenfrontversionfornullimplement,onlyreturnnullcolumn表.
 */
class MCPResourceManager
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $resources = [];

    /**
     * registerresource.
     * whenfrontfornullimplement.
     */
    public function registerResource(array $resource): void
    {
        // nullimplement,暂notregisteranyresource
    }

    /**
     * getresourcecolumn表.
     * whenfrontfornullimplement,returnnullarray.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getResources(): array
    {
        return [];
    }

    /**
     * getfinger定IDresource.
     * whenfrontfornullimplement,始终returnnull.
     */
    public function getResource(string $id): ?array
    {
        return null;
    }

    /**
     * checkfinger定IDresourcewhether存in.
     */
    public function hasResource(string $id): bool
    {
        return isset($this->resources[$id]);
    }

    /**
     * checkwhethernothaveanyresource.
     */
    public function isEmpty(): bool
    {
        return empty($this->resources);
    }
}

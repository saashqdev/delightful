<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Resources;

/**
 * MCPresourcemanager.
 * whenfrontversionfornullimplement,onlyreturnnullcolumntable.
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
        // nullimplement,temporarynotregisteranyresource
    }

    /**
     * getresourcecolumntable.
     * whenfrontfornullimplement,returnnullarray.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getResources(): array
    {
        return [];
    }

    /**
     * getfingersetIDresource.
     * whenfrontfornullimplement,alwaysreturnnull.
     */
    public function getResource(string $id): ?array
    {
        return null;
    }

    /**
     * checkfingersetIDresourcewhetherexistsin.
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

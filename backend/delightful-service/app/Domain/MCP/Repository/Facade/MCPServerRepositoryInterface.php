<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\MCP\Repository\Facade;

use App\Domain\MCP\Entity\MCPServerEntity;
use App\Domain\MCP\Entity\ValueObject\MCPDataIsolation;
use App\Domain\MCP\Entity\ValueObject\Query\MCPServerQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface MCPServerRepositoryInterface
{
    public function getById(MCPDataIsolation $dataIsolation, int $id): ?MCPServerEntity;

    /**
     * @param array<int> $ids
     * @return array<int, MCPServerEntity> returnbyid为key的实体objectarray
     */
    public function getByIds(MCPDataIsolation $dataIsolation, array $ids): array;

    public function getByCode(MCPDataIsolation $dataIsolation, string $code): ?MCPServerEntity;

    public function getOrgCodes(MCPDataIsolation $dataIsolation): array;

    /**
     * @return array{total: int, list: array<MCPServerEntity>}
     */
    public function queries(MCPDataIsolation $dataIsolation, MCPServerQuery $query, Page $page): array;

    /**
     * saveMCPservice
     */
    public function save(MCPDataIsolation $dataIsolation, MCPServerEntity $entity): MCPServerEntity;

    /**
     * deleteMCPservice
     */
    public function delete(MCPDataIsolation $dataIsolation, string $code): bool;
}

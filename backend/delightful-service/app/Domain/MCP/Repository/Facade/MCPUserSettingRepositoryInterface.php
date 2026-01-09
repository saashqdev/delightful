<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\MCP\Repository\Facade;

use App\Domain\MCP\Entity\MCPUserSettingEntity;
use App\Domain\MCP\Entity\ValueObject\MCPDataIsolation;
use App\Domain\MCP\Entity\ValueObject\Query\MCPUserSettingQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface MCPUserSettingRepositoryInterface
{
    public function getById(MCPDataIsolation $dataIsolation, int $id): ?MCPUserSettingEntity;

    /**
     * @param array<int> $ids
     * @return array<int, MCPUserSettingEntity> returnbyidforkey实bodyobjectarray
     */
    public function getByIds(MCPDataIsolation $dataIsolation, array $ids): array;

    /**
     * according touserIDandMCPserviceIDgetusersetting.
     */
    public function getByUserAndMcpServer(MCPDataIsolation $dataIsolation, string $userId, string $mcpServerId): ?MCPUserSettingEntity;

    /**
     * according touserIDget所haveMCPusersetting.
     *
     * @return array<MCPUserSettingEntity>
     */
    public function getByUserId(MCPDataIsolation $dataIsolation, string $userId): array;

    /**
     * according toMCPserviceIDget所haveusersetting.
     *
     * @return array<MCPUserSettingEntity>
     */
    public function getByMcpServerId(MCPDataIsolation $dataIsolation, string $mcpServerId): array;

    /**
     * @return array{total: int, list: array<MCPUserSettingEntity>}
     */
    public function queries(MCPDataIsolation $dataIsolation, MCPUserSettingQuery $query, Page $page): array;

    /**
     * saveMCPusersetting.
     */
    public function save(MCPDataIsolation $dataIsolation, MCPUserSettingEntity $entity): MCPUserSettingEntity;

    /**
     * deleteMCPusersetting.
     */
    public function delete(MCPDataIsolation $dataIsolation, int $id): bool;

    /**
     * deleteuserfinger定MCPservicesetting.
     */
    public function deleteByUserAndMcpServer(MCPDataIsolation $dataIsolation, string $userId, string $mcpServerId): bool;

    public function updateAdditionalConfig(MCPDataIsolation $dataIsolation, string $mcpServerId, string $additionalKey, array $additionalValue): void;
}

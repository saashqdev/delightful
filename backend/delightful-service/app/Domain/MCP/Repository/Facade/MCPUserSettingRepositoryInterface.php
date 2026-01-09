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
     * @return array<int, MCPUserSettingEntity> return以id为key的实体objectarray
     */
    public function getByIds(MCPDataIsolation $dataIsolation, array $ids): array;

    /**
     * 根据userID和MCP服务IDgetusersetting.
     */
    public function getByUserAndMcpServer(MCPDataIsolation $dataIsolation, string $userId, string $mcpServerId): ?MCPUserSettingEntity;

    /**
     * 根据userIDget所有MCPusersetting.
     *
     * @return array<MCPUserSettingEntity>
     */
    public function getByUserId(MCPDataIsolation $dataIsolation, string $userId): array;

    /**
     * 根据MCP服务IDget所有usersetting.
     *
     * @return array<MCPUserSettingEntity>
     */
    public function getByMcpServerId(MCPDataIsolation $dataIsolation, string $mcpServerId): array;

    /**
     * @return array{total: int, list: array<MCPUserSettingEntity>}
     */
    public function queries(MCPDataIsolation $dataIsolation, MCPUserSettingQuery $query, Page $page): array;

    /**
     * 保存MCPusersetting.
     */
    public function save(MCPDataIsolation $dataIsolation, MCPUserSettingEntity $entity): MCPUserSettingEntity;

    /**
     * deleteMCPusersetting.
     */
    public function delete(MCPDataIsolation $dataIsolation, int $id): bool;

    /**
     * deleteuser的指定MCP服务setting.
     */
    public function deleteByUserAndMcpServer(MCPDataIsolation $dataIsolation, string $userId, string $mcpServerId): bool;

    public function updateAdditionalConfig(MCPDataIsolation $dataIsolation, string $mcpServerId, string $additionalKey, array $additionalValue): void;
}

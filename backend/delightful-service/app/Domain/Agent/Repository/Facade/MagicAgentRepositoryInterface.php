<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Repository\Facade;

use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulAgentQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulAgentRepositoryInterface
{
    /**
     * @return array{total: int, list: array<DelightfulAgentEntity>}
     */
    public function queries(DelightfulAgentQuery $query, Page $page): array;

    public function getByFlowCode(string $flowCode): ?DelightfulAgentEntity;

    /**
     * @return DelightfulAgentEntity[]
     */
    public function getByFlowCodes(array $flowCodes): array;

    public function insert(DelightfulAgentEntity $agentEntity);

    public function updateById(DelightfulAgentEntity $agentEntity): DelightfulAgentEntity;

    public function updateStatus(string $agentId, int $status);

    public function getAgentsByUserId(string $userId, int $page, int $pageSize, string $agentName): array;

    public function getAgentsByUserIdCount(string $userId, string $agentName): int;

    public function deleteAgentById(string $id, string $organizationCode);

    public function getAgentById(string $agentId): DelightfulAgentEntity;
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Repository\Facade;

use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;

interface DelightfulAgentVersionRepositoryInterface
{
    public function insert(DelightfulAgentVersionEntity $agentVersionEntity): DelightfulAgentVersionEntity;

    public function getAgentById(string $id): ?DelightfulAgentVersionEntity;

    public function getAgentsByOrganization(string $organizationCode, array $agentIds, int $page, int $pageSize, string $agentName): array;

    public function getAgentsByOrganizationCount(string $organizationCode, array $agentIds, string $agentName): int;

    public function getAgentsFromMarketplace(array $agentIds, int $page, int $pageSize): array;

    public function getAgentsFromMarketplaceCount(array $agentIds): int;
}

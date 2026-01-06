<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Service;

use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Domain\Agent\Entity\ValueObject\AgentDataIsolation;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulAgentQuery;
use App\Domain\Agent\Repository\Facade\AgentRepositoryInterface;
use App\Domain\Agent\Repository\Facade\AgentVersionRepositoryInterface;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowVersionQuery;
use App\Infrastructure\Core\ValueObject\Page;

readonly class AgentDomainService
{
    public function __construct(
        private AgentRepositoryInterface $agentRepository,
        private AgentVersionRepositoryInterface $agentVersionRepository
    ) {
    }

    /**
     * 查询 Agent 列表.
     *
     * @return array{total: int, list: array<DelightfulAgentEntity>}
     */
    public function queries(AgentDataIsolation $agentDataIsolation, DelightfulAgentQuery $agentQuery, Page $page): array
    {
        return $this->agentRepository->queries($agentDataIsolation, $agentQuery, $page);
    }

    /**
     * 获取组织内可用的 Agent 版本.
     *
     * @return array{total: int, list: array<DelightfulAgentVersionEntity>}
     */
    public function getOrgAvailableAgentIds(AgentDataIsolation $dataIsolation, DelightfulFLowVersionQuery $query, Page $page): array
    {
        return $this->agentVersionRepository->getOrgAvailableAgents($dataIsolation, $query, $page);
    }
}

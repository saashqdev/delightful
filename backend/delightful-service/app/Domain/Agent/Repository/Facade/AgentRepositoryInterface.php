<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Repository\Facade;

use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\ValueObject\AgentDataIsolation;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulAgentQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface AgentRepositoryInterface
{
    /**
     * 查询 Agent 列table.
     *
     * @return array{total: int, list: array<DelightfulAgentEntity>}
     */
    public function queries(AgentDataIsolation $agentDataIsolation, DelightfulAgentQuery $agentQuery, Page $page): array;
}

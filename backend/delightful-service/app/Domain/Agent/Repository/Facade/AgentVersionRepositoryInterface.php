<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Repository\Facade;

use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Domain\Agent\Entity\ValueObject\AgentDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowVersionQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface AgentVersionRepositoryInterface
{
    /**
     * getorganization内可use的 Agent version.
     *
     * @return array{total: int, list: array<DelightfulAgentVersionEntity>}
     */
    public function getOrgAvailableAgents(AgentDataIsolation $dataIsolation, DelightfulFLowVersionQuery $query, Page $page): array;
}

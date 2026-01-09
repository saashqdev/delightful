<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Repository\Persistence;

use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\ValueObject\AgentDataIsolation;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulAgentQuery;
use App\Domain\Agent\Factory\DelightfulAgentFactory;
use App\Domain\Agent\Repository\Facade\AgentRepositoryInterface;
use App\Domain\Agent\Repository\Persistence\Model\DelightfulAgentModel;
use App\Infrastructure\Core\AbstractRepository;
use App\Infrastructure\Core\ValueObject\Page;

class AgentRepository extends AbstractRepository implements AgentRepositoryInterface
{
    protected bool $filterOrganizationCode = true;

    /**
     * query Agent columntable.
     *
     * @return array{total: int, list: array<DelightfulAgentEntity>}
     */
    public function queries(AgentDataIsolation $agentDataIsolation, DelightfulAgentQuery $agentQuery, Page $page): array
    {
        $builder = $this->createBuilder($agentDataIsolation, DelightfulAgentModel::query());

        // settingqueryitemitem
        if (! is_null($agentQuery->getIds())) {
            if (empty($agentQuery->getIds())) {
                return ['total' => 0, 'list' => []];
            }
            $builder->whereIn('id', $agentQuery->getIds());
        }
        if ($agentQuery->getStatus()) {
            $builder->where('status', '=', $agentQuery->getStatus());
        }
        if ($agentQuery->getAgentName()) {
            $builder->where('robot_name', 'like', '%' . $agentQuery->getAgentName() . '%');
        }

        // paginationquery
        $data = $this->getByPage($builder, $page, $agentQuery);
        $list = [];
        /** @var DelightfulAgentModel $agent */
        foreach ($data['list'] as $agent) {
            $list[] = DelightfulAgentFactory::modelToEntity($agent);
        }
        $data['list'] = $list;
        return $data;
    }
}

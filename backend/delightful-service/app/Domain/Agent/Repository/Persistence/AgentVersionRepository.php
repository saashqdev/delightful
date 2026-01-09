<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Repository\Persistence;

use App\Domain\Agent\Constant\DelightfulAgentReleaseStatus;
use App\Domain\Agent\Constant\DelightfulAgentVersionStatus;
use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Domain\Agent\Entity\ValueObject\AgentDataIsolation;
use App\Domain\Agent\Factory\DelightfulAgentVersionFactory;
use App\Domain\Agent\Repository\Facade\AgentVersionRepositoryInterface;
use App\Domain\Agent\Repository\Persistence\Model\DelightfulAgentModel;
use App\Domain\Agent\Repository\Persistence\Model\DelightfulAgentVersionModel;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowVersionQuery;
use App\Infrastructure\Core\AbstractRepository;
use App\Infrastructure\Core\ValueObject\Page;

class AgentVersionRepository extends AbstractRepository implements AgentVersionRepositoryInterface
{
    protected bool $filterOrganizationCode = true;

    /**
     * getorganization内可use的 Agent version.
     *
     * @return array{total: int, list: array<DelightfulAgentVersionEntity>}
     */
    public function getOrgAvailableAgents(AgentDataIsolation $dataIsolation, DelightfulFLowVersionQuery $query, Page $page): array
    {
        $builder = $this->createBuilder($dataIsolation, DelightfulAgentModel::query());
        $versionBuilder = $this->createBuilder($dataIsolation, DelightfulAgentVersionModel::query());

        // query所have的enableversion id
        $botVersionIds = $builder
            ->where('status', '=', DelightfulAgentVersionStatus::ENTERPRISE_ENABLED->value)
            ->whereNotNull('bot_version_id')
            ->pluck('bot_version_id')->toArray();
        if (empty($botVersionIds)) {
            return ['total' => 0, 'list' => []];
        }

        $versionBuilder->whereIn('id', $botVersionIds);
        $versionBuilder->where('release_scope', '=', DelightfulAgentReleaseStatus::PUBLISHED_TO_ENTERPRISE->value);
        $data = $this->getByPage($versionBuilder, $page, $query);
        $list = [];
        /** @var DelightfulAgentVersionModel $item */
        foreach ($data['list'] as $item) {
            $list[] = DelightfulAgentVersionFactory::toEntity($item->toArray());
        }
        $data['list'] = $list;
        return $data;
    }
}

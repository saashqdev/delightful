<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Repository\Persistence;

use App\Domain\Agent\Constant\DelightfulAgentVersionStatus;
use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulAgentQuery;
use App\Domain\Agent\Factory\DelightfulAgentFactory;
use App\Domain\Agent\Repository\Facade\DelightfulAgentRepositoryInterface;
use App\Domain\Agent\Repository\Persistence\Model\DelightfulAgentModel;
use App\Domain\Agent\Repository\Persistence\Model\UserDefaultAssistantConversationRecordModel;
use App\ErrorCode\AgentErrorCode;
use App\Infrastructure\Core\AbstractRepository;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Admin\DTO\Request\QueryPageAgentDTO;
use Hyperf\Codec\Json;
use Hyperf\DbConnection\Db;

class DelightfulAgentRepository extends AbstractRepository implements DelightfulAgentRepositoryInterface
{
    public function __construct(public DelightfulAgentModel $agentModel)
    {
    }

    /**
     * @return array{total: int, list: array<DelightfulAgentEntity>}
     */
    public function queries(DelightfulAgentQuery $query, Page $page): array
    {
        // todo thiswithinat leastneedorganizationisolation
        $builder = DelightfulAgentModel::query();

        if (! is_null($query->getIds())) {
            $builder->whereIn('id', $query->getIds());
        }
        if (! is_null($query->getAgentName()) && strlen($query->getAgentName()) > 0) {
            $builder->where('robot_name', 'like', "%{$query->getAgentName()}%");
        }
        if (! empty($query->getCreatedUid())) {
            $builder->where('created_uid', $query->getCreatedUid());
        }

        if ($query->isWithLastVersionInfo()) {
            $builder->with(['lastVersionInfo']);
        }

        $data = $this->getByPage($builder, $page, $query);
        if (! empty($data['list'])) {
            $list = [];
            foreach ($data['list'] as $model) {
                $list[] = DelightfulAgentFactory::modelToEntity($model);
            }
            $data['list'] = $list;
        }

        return $data;
    }

    public function insert(DelightfulAgentEntity $agentEntity): DelightfulAgentEntity
    {
        $agentEntity->setCreatedAt(date('Y-m-d H:i:s'));
        $agentEntity->setUpdatedAt(date('Y-m-d H:i:s'));

        $toArray = $agentEntity->toArray();
        /** @var DelightfulAgentModel $model */
        $model = $this->agentModel::query()->create($toArray);

        $agentEntity->setId($model->id);
        return $agentEntity;
    }

    public function updateStatus(string $agentId, int $status): void
    {
        $this->agentModel::query()->where('id', $agentId)
            ->update(['status' => $status]);
    }

    public function updateById(DelightfulAgentEntity $agentEntity): DelightfulAgentEntity
    {
        $agentEntity->setUpdatedAt(date('Y-m-d H:i:s'));
        $agentArray = $agentEntity->toArray();

        unset($agentArray['id'], $agentArray['user_operation'],
            $agentArray['last_version_info'], $agentArray['instructs'],
            $agentArray['agent_version_id'],$agentArray['agent_name'],
            $agentArray['agent_avatar'],$agentArray['agent_description']);
        $this->agentModel::query()
            ->where('id', $agentEntity->getId())
            ->where('created_uid', $agentEntity->getCreatedUid())
            ->update($agentArray);
        return $agentEntity;
    }

    /**
     * @return DelightfulAgentEntity[]
     */
    public function getAgentsByUserId(string $userId, int $page, int $pageSize, string $agentName): array
    {
        $offset = ($page - 1) * $pageSize;
        $builder = $this->agentModel::query()->where('created_uid', $userId);
        if (! empty($agentName)) {
            $builder->where('robot_name', 'like', "%{$agentName}%");
        }
        $query = $builder
            ->orderByDesc('id')
            ->skip($offset)
            ->take($pageSize);

        $result = Db::select($query->toSql(), $query->getBindings());
        return DelightfulAgentFactory::toEntities($result);
    }

    public function getAgentsByUserIdCount(string $userId, string $agentName): int
    {
        $builder = $this->agentModel::query()->where('created_uid', $userId);
        if (! empty($agentName)) {
            $builder->where('robot_name', 'like', "%{$agentName}%");
        }
        return $builder->count();
    }

    public function deleteAgentById(string $id, string $organizationCode): void
    {
        $this->agentModel::query()->where('id', $id)->where('organization_code', $organizationCode)->delete();
    }

    public function getAgentById(string $agentId): DelightfulAgentEntity
    {
        // querydatabase,getfinger定 agentId and userId data
        $agent = $this->agentModel::query()
            ->where('id', $agentId)
            ->first();

        // ifqueryresultforempty,throwexceptionorreturn null,according tobusinessrequirementprocess
        if (! $agent) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_not_exist');
        }

        return DelightfulAgentFactory::toEntity($agent->toArray());
    }

    public function updateDefaultVersion(string $agentId, string $versionId): void
    {
        $this->agentModel::query()->where('id', $agentId)
            ->update(['bot_version_id' => $versionId]);
    }

    /**
     * @return DelightfulAgentEntity[]
     */
    public function getEnabledAgents(): array
    {
        $query = $this->agentModel::query()->where('status', DelightfulAgentVersionStatus::ENTERPRISE_ENABLED->value);
        $result = Db::select($query->toSql(), $query->getBindings());
        return DelightfulAgentFactory::toEntities($result);
    }

    public function getById(string $agentId): DelightfulAgentEntity
    {
        $result = $this->agentModel::query()
            ->where('id', $agentId)
            ->first();

        if ($result === null) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_not_exist');
        }
        $agentArray = $result->toArray();
        return DelightfulAgentFactory::toEntity($result->toArray());
    }

    public function getAgentDetail(string $agentId, string $userId): DelightfulAgentEntity
    {
        $result = $this->agentModel::query()
            ->where('id', $agentId)
            ->where('created_uid', $userId)
            ->first();
        if ($result === null) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_not_exist');
        }

        return DelightfulAgentFactory::toEntity($result->toArray());
    }

    public function getByFlowCode(string $flowCode): ?DelightfulAgentEntity
    {
        $result = $this->agentModel::query()
            ->where('flow_code', $flowCode)
            ->first();
        if ($result === null) {
            return null;
        }

        return DelightfulAgentFactory::toEntity($result->toArray());
    }

    /**
     * @return DelightfulAgentEntity[]
     */
    public function getByFlowCodes(array $flowCodes): array
    {
        $result = $this->agentModel::query()
            ->whereIn('flow_code', $flowCodes)
            ->get();
        if ($result->isEmpty()) {
            return [];
        }

        $agents = [];
        foreach ($result as $agent) {
            $entity = new DelightfulAgentEntity($agent->toArray());
            $agents[$entity->getId()] = $entity;
        }
        return $agents;
    }

    public function insertDefaultAssistantConversation(string $userId, string $aiCode): void
    {
        UserDefaultAssistantConversationRecordModel::query()->create([
            'user_id' => $userId,
            'ai_code' => $aiCode,
        ]);
    }

    public function isDefaultAssistantConversationExist(string $userId, string $aiCode): bool
    {
        return UserDefaultAssistantConversationRecordModel::query()
            ->where('user_id', $userId)
            ->where('ai_code', $aiCode)
            ->exists();
    }

    /**
     * @return DelightfulAgentEntity[]
     */
    public function getAgentByIds(array $agentIds): array
    {
        $query = $this->agentModel::query()->whereIn('id', $agentIds);
        $result = Db::select($query->toSql(), $query->getBindings());
        return DelightfulAgentFactory::toEntities($result);
    }

    public function updateInstruct(string $getOrganizationCode, string $agentId, array $instructs, $updatedUid = ''): void
    {
        $this->agentModel::query()
            ->where('id', $agentId)
            ->where('organization_code', $getOrganizationCode)
            ->update([
                'instructs' => Json::encode($instructs),
                'updated_uid' => $updatedUid,
            ]);
    }

    /**
     * minutebatchget助理list.
     * @param int $offset offsetquantity
     * @param int $limit eachbatchquantity
     * @return array 助理list
     */
    public function getAgentsByBatch(int $offset, int $limit): array
    {
        return $this->agentModel->newQuery()
            ->select(['id', 'created_uid', 'organization_code', 'instructs'])
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function updateFlowCode(string $agentId, string $flowCode)
    {
        $this->agentModel::query()->newQuery()->where('id', $agentId)->update(['flow_code' => $flowCode]);
    }

    /**
     * query企业down所have助理,itemitemquery:status,createperson,search.
     * @return array<DelightfulAgentEntity>
     */
    public function queriesAgents(string $organizationCode, QueryPageAgentDTO $queryPageAgentDTO): array
    {
        $query = $this->agentModel->newQuery()
            ->where('organization_code', $organizationCode)
            ->limit($queryPageAgentDTO->getPageSize())
            ->offset($queryPageAgentDTO->getPage() - 1)
            ->orderByDesc('id');

        if ($queryPageAgentDTO->getCreatedUid()) {
            $query->where('created_uid', $queryPageAgentDTO->getCreatedUid());
        }

        if ($queryPageAgentDTO->getQuery()) {
            // nameor者description
            $query->where('robot_name', 'like', "%{$queryPageAgentDTO->getQuery()}%")
                ->orWhere('robot_description', 'like', "%{$queryPageAgentDTO->getQuery()}%");
        }

        if ($queryPageAgentDTO->getStatus()) {
            $query->where('status', $queryPageAgentDTO->getStatus());
        }
        $result = Db::select($query->toSql(), $query->getBindings());
        return DelightfulAgentFactory::toEntities($result);
    }

    public function queriesAgentsCount(string $organizationCode, QueryPageAgentDTO $queryPageAgentDTO): int
    {
        $query = $this->agentModel->newQuery()
            ->where('organization_code', $organizationCode);

        if ($queryPageAgentDTO->getCreatedUid()) {
            $query->where('created_uid', $queryPageAgentDTO->getCreatedUid());
        }

        if ($queryPageAgentDTO->getQuery()) {
            // nameor者description
            $query->where('robot_name', 'like', "%{$queryPageAgentDTO->getQuery()}%")
                ->orWhere('robot_description', 'like', "%{$queryPageAgentDTO->getQuery()}%");
        }

        if ($queryPageAgentDTO->getStatus()) {
            $query->where('status', $queryPageAgentDTO->getStatus());
        }
        return $query->count();
    }

    /**
     * get企业down所have助理create者.
     * @return array<string>
     */
    public function getOrganizationAgentsCreators(string $organizationCode): array
    {
        $query = $this->agentModel->newQuery()
            ->where('organization_code', $organizationCode)
            ->select('created_uid')
            ->distinct();

        return $query->get()
            ->pluck('created_uid')
            ->toArray();
    }
}

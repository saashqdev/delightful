<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Repository\Persistence;

use App\Domain\Agent\Constant\DelightfulAgentReleaseStatus;
use App\Domain\Agent\Constant\DelightfulAgentVersionStatus;
use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Domain\Agent\Factory\DelightfulAgentVersionFactory;
use App\Domain\Agent\Repository\Facade\DelightfulAgentVersionRepositoryInterface;
use App\Domain\Agent\Repository\Persistence\Model\DelightfulAgentVersionModel;
use App\ErrorCode\AgentErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Hyperf\Codec\Json;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Db;

class DelightfulAgentVersionRepository implements DelightfulAgentVersionRepositoryInterface
{
    public function __construct(public DelightfulAgentVersionModel $agentVersionModel)
    {
    }

    /**
     * getassistantversion.
     */
    public function getAgentById(string $id): DelightfulAgentVersionEntity
    {
        $model = $this->agentVersionModel::query()
            ->where('id', $id)
            ->first();
        if (! $model) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_does_not_exist');
        }
        return DelightfulAgentVersionFactory::toEntity($model->toArray());
    }

    /**
     * @return DelightfulAgentVersionEntity[]
     */
    public function getAgentsByOrganization(string $organizationCode, array $agentIds, int $page, int $pageSize, string $agentName, ?string $descriptionKeyword = null): array
    {
        $offset = ($page - 1) * $pageSize;

        $builder = $this->agentVersionModel::query();
        $query = $builder
            ->where('organization_code', $organizationCode)
            ->where('enterprise_release_status', DelightfulAgentVersionStatus::ENTERPRISE_PUBLISHED->value)
            ->whereIn('id', $agentIds)
            ->where(function (Builder $query) use ($agentName, $descriptionKeyword) {
                $query
                    ->when(! empty($agentName), function (Builder $query) use ($agentName) {
                        $query->orWhere('robot_name', 'like', "%{$agentName}%");
                    })
                    ->when(! empty($descriptionKeyword), function (Builder $query) use ($descriptionKeyword) {
                        $query->orWhere('robot_description', 'like', "%{$descriptionKeyword}%");
                    });
            })
            ->orderByDesc('id')
            ->skip($offset)
            ->take($pageSize);

        $result = Db::select($query->toSql(), $query->getBindings());
        return DelightfulAgentVersionFactory::toEntities($result);
    }

    public function getAgentsByOrganizationCount(string $organizationCode, array $agentIds, string $agentName): int
    {
        $builder = $this->agentVersionModel::query();
        if (! empty($agentName)) {
            $builder->where('robot_name', 'like', "%{$agentName}%");
        }
        return $builder
            ->where('organization_code', $organizationCode)
            ->where('enterprise_release_status', DelightfulAgentVersionStatus::ENTERPRISE_PUBLISHED->value) // ensureoutsidelayerfilterenablestatus
            ->whereIn('id', $agentIds)
            ->count();
    }

    /**
     * optimizeversion:directlypassJOINquerygetenableassistantversion,avoidpass inbigquantityID.
     * @return DelightfulAgentVersionEntity[]
     */
    public function getEnabledAgentsByOrganization(string $organizationCode, int $page, int $pageSize, string $agentName): array
    {
        $offset = ($page - 1) * $pageSize;

        $query = $this->agentVersionModel::query()
            ->select('delightful_bot_versions.*')
            ->join('delightful_bots', 'delightful_bots.bot_version_id', '=', 'delightful_bot_versions.id')
            ->where('delightful_bot_versions.organization_code', $organizationCode)
            ->where('delightful_bot_versions.enterprise_release_status', DelightfulAgentVersionStatus::ENTERPRISE_PUBLISHED->value)
            ->where('delightful_bots.organization_code', $organizationCode)
            ->where('delightful_bots.status', DelightfulAgentVersionStatus::ENTERPRISE_ENABLED->value)
            ->when(! empty($agentName), function ($query) use ($agentName) {
                $query->where('delightful_bot_versions.robot_name', 'like', "%{$agentName}%");
            })
            ->orderByDesc('delightful_bot_versions.id')
            ->skip($offset)
            ->take($pageSize);

        $result = Db::select($query->toSql(), $query->getBindings());
        return DelightfulAgentVersionFactory::toEntities($result);
    }

    /**
     * optimizeversion:getenableassistanttotal.
     */
    public function getEnabledAgentsByOrganizationCount(string $organizationCode, string $agentName): int
    {
        return $this->agentVersionModel::query()
            ->join('delightful_bots', 'delightful_bots.bot_version_id', '=', 'delightful_bot_versions.id')
            ->where('delightful_bot_versions.organization_code', $organizationCode)
            ->where('delightful_bot_versions.enterprise_release_status', DelightfulAgentVersionStatus::ENTERPRISE_PUBLISHED->value)
            ->where('delightful_bots.organization_code', $organizationCode)
            ->where('delightful_bots.status', DelightfulAgentVersionStatus::ENTERPRISE_ENABLED->value)
            ->when(! empty($agentName), function ($query) use ($agentName) {
                $query->where('delightful_bot_versions.robot_name', 'like', "%{$agentName}%");
            })
            ->count();
    }

    /**
     * @return DelightfulAgentVersionEntity[]
     */
    public function getAgentsFromMarketplace(array $agentIds, int $page, int $pageSize): array
    {
        $offset = ($page - 1) * $pageSize;
        $query = $this->agentVersionModel::query()
            ->whereIn('id', $agentIds)
            ->where('app_market_status', DelightfulAgentVersionStatus::APP_MARKET_LISTED)
            ->orderByDesc('id')
            ->skip($offset)
            ->take($pageSize);

        $result = Db::select($query->toSql(), $query->getBindings());
        return DelightfulAgentVersionFactory::toEntities($result);
    }

    public function getAgentsFromMarketplaceCount(array $agentIds): int
    {
        // use count() methodcomestatisticsconformitemitemrecordcount
        return $this->agentVersionModel::query()
            ->whereIn('id', $agentIds)
            ->where('app_market_status', DelightfulAgentVersionStatus::APP_MARKET_LISTED)
            ->orderByDesc('id')
            ->count();
    }

    public function insert(DelightfulAgentVersionEntity $agentVersionEntity): DelightfulAgentVersionEntity
    {
        $agentVersionEntity->setCreatedAt(date('Y-m-d H:i:s'));
        $agentVersionEntity->setUpdatedAt(date('Y-m-d H:i:s'));
        $entityArray = $agentVersionEntity->toArray();
        $entityArray['visibility_config'] = Json::encode($agentVersionEntity->getVisibilityConfig());
        $model = $this->agentVersionModel::query()->create($entityArray);
        $agentVersionEntity->setId($model->id);
        return $agentVersionEntity;
    }

    /**
     * @return DelightfulAgentVersionEntity[]
     */
    public function getReleaseAgentVersions(string $agentId): array
    {
        $query = $this->agentVersionModel::query()
            ->where('root_id', $agentId)
            ->orderByDesc('id');

        $result = Db::select($query->toSql(), $query->getBindings());
        return DelightfulAgentVersionFactory::toEntities($result);
    }

    public function setEnterpriseStatus(string $id, int $status): void
    {
        // tryupdatefingerset ID record
        $this->agentVersionModel::query()
            ->where('id', $id)
            ->update(['enterprise_release_status' => $status]);
    }

    // according toassistantidgetmostbig version_number
    public function getAgentMaxVersion(string $agentId): string
    {
        // queryfingerset agent_id and user_id downmostbigversionnumber,thiswithinnotcanuse max get version,因forwilloutshow 0.3 greater than 0.10situation,butisactualis 0.10greater than 0.3
        // whileversionnumberonlycanincrement,thereforeusetimereverse ordergetfirst即can
        $maxVersion = $this->agentVersionModel::query()
            ->where('root_id', $agentId)
            ->orderByDesc('id')
            ->limit(1)->first();

        // ifnothavefindtorecord,return 0.0 asfordefaultvalue
        if ($maxVersion === null) {
            return '0.0.0';
        }

        return $maxVersion->toArray()['version_number'];
    }

    public function deleteByAgentId(string $agentId, string $organizationCode): void
    {
        // queryfingerset agent_id and user_id downmostbigversionnumber
        $this->agentVersionModel::query()
            ->where('root_id', $agentId)
            ->where('organization_code', $organizationCode)
            ->delete();
    }

    public function getDefaultVersions(array $agentIds): void
    {
    }

    /**
     * @return DelightfulAgentVersionEntity[]
     */
    public function listAgentVersionsByIds(array $agentVersionIds): array
    {
        $query = $this->agentVersionModel::query()->whereIn('id', $agentVersionIds);
        $result = Db::select($query->toSql(), $query->getBindings());
        return DelightfulAgentVersionFactory::toEntities($result);
    }

    public function updateAgentEnterpriseStatus(string $agentVersionId, int $status): void
    {
        $this->agentVersionModel::query()
            ->where('id', $agentVersionId)
            ->update(['enterprise_release_status' => $status]);
    }

    public function getNewestAgentVersionEntity(string $agentId): ?DelightfulAgentVersionEntity
    {
        // get $agentId pass
        $model = $this->agentVersionModel::query()
            ->where('root_id', $agentId)
            ->orderByDesc('id')
            ->limit(1)->first();
        if ($model === null) {
            return $model;
        }
        return DelightfulAgentVersionFactory::toEntity($model->toArray());
    }

    public function getAgentByFlowCode(string $flowCode): ?DelightfulAgentVersionEntity
    {
        // get $agentId pass
        $model = $this->agentVersionModel::query()
            ->where('flow_code', $flowCode)
            ->orderByDesc('id')
            ->limit(1)->first();
        if ($model === null) {
            return null;
        }
        return DelightfulAgentVersionFactory::toEntity($model->toArray());
    }

    public function getEnterpriseAvailableAgentIds(string $organizationCode): array
    {
        return $this->agentVersionModel::query()
            ->where('organization_code', $organizationCode)
            ->where('release_scope', DelightfulAgentReleaseStatus::PUBLISHED_TO_ENTERPRISE->value)
            ->groupBy('root_id')
            ->pluck('root_id')->toArray();
    }

    public function getAgentVersionsByBatch(int $offset, int $limit): array
    {
        return $this->agentVersionModel::query()
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function updateById(DelightfulAgentVersionEntity $agentVersionEntity): DelightfulAgentVersionEntity
    {
        $model = $this->agentVersionModel::query()
            ->where('id', $agentVersionEntity->getId())
            ->first();
        if (! $model) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_does_not_exist');
        }
        $model->fill($agentVersionEntity->toArray());
        $model->save();
        unset($model['agent_id'],$model['agent_name'],$model['agent_avatar'],$model['agent_description']);
        return DelightfulAgentVersionFactory::toEntity($model->toArray());
    }

    /**
     * based oncursorpaginationgetfingersetorganizationassistantversionlist.
     * @param string $organizationCode organizationcode
     * @param array $agentVersionIds assistantversionIDlist
     * @param string $cursor cursorID,ifforemptystringthenfrommostnewstart
     * @param int $pageSize eachpagequantity
     */
    public function getAgentsByOrganizationWithCursor(string $organizationCode, array $agentVersionIds, string $cursor, int $pageSize): array
    {
        $query = $this->agentVersionModel::query()
            ->where('organization_code', $organizationCode)
            ->whereIn('id', $agentVersionIds)
            ->orderBy('id', 'desc')
            ->limit($pageSize);

        if ($cursor !== '') {
            $query->where('id', '<', (int) $cursor);
        }

        return Db::select($query->toSql(), $query->getBindings());
    }

    /**
     * according toidsgetassistantversion.
     * @return array<DelightfulAgentVersionEntity>
     */
    public function getAgentByIds(array $ids)
    {
        $model = $this->agentVersionModel::query()
            ->whereIn('id', $ids)
            ->get();
        return DelightfulAgentVersionFactory::toEntities($model->toArray());
    }
}

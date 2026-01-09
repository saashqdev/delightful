<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Service;

use App\Domain\Agent\Constant\DelightfulAgentVersionStatus;
use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\ValueObject\AgentDataIsolation;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulAgentQuery;
use App\Domain\Agent\Entity\ValueObject\Visibility\VisibilityType;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowVersionQuery;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use Delightful\CloudFile\Kernel\Struct\FileLink;
use Hyperf\Codec\Json;
use Qbhy\HyperfAuth\Authenticatable;

class AgentAppService extends AbstractAppService
{
    /**
     * query Agent list.
     *
     * @param Authenticatable $authorization authorizationuser
     * @param DelightfulAgentQuery $query query条件
     * @param Page $page paginationinfo
     * @return array{total: int, list: array<DelightfulAgentEntity>, icons: array<string,FileLink>}
     */
    public function queriesAvailable(Authenticatable $authorization, DelightfulAgentQuery $query, Page $page, bool $containOfficialOrganization = false): array
    {
        $agentDataIsolation = $this->createAgentDataIsolation($authorization);
        $agentDataIsolation->setContainOfficialOrganization($containOfficialOrganization);

        // generatecache key
        $cacheKey = sprintf('queriesAvailableAgents:user:%s:official:%s', $authorization->getId(), $containOfficialOrganization ? '1' : '0');

        // 尝试从cacheget agentIds
        $agentIds = $this->redis->get($cacheKey);
        if ($agentIds !== false) {
            $agentIds = Json::decode($agentIds);
        } else {
            // getorganization内可用的 Agent Ids
            $orgAgentIds = $this->getOrgAvailableAgentIds($agentDataIsolation, $containOfficialOrganization);

            // get自己有permission的 id
            $permissionDataIsolation = new PermissionDataIsolation($agentDataIsolation->getCurrentOrganizationCode(), $agentDataIsolation->getCurrentUserId());
            $agentResources = $this->operationPermissionAppService->getResourceOperationByUserIds(
                $permissionDataIsolation,
                ResourceType::AgentCode,
                [$agentDataIsolation->getCurrentUserId()]
            )[$agentDataIsolation->getCurrentUserId()] ?? [];
            $selfAgentIds = array_keys($agentResources);

            // 合并
            $agentIds = array_unique(array_merge($orgAgentIds, $selfAgentIds));

            // cacheresult（仅当不为空时）
            if (! empty($agentIds)) {
                $this->redis->setex($cacheKey, 180, Json::encode($agentIds)); // cache 3 分钟
            }
        }

        if (empty($agentIds)) {
            return ['total' => 0, 'list' => [], 'icons' => []];
        }
        $query->setIds($agentIds);
        $query->setStatus(DelightfulAgentVersionStatus::ENTERPRISE_ENABLED->value);
        $query->setSelect(['id', 'robot_name', 'robot_avatar', 'robot_description', 'created_at', 'flow_code', 'organization_code']);

        $data = $this->agentDomainService->queries($agentDataIsolation, $query, $page);

        // 如果contain官方organization，按照传入的ID顺序重新sortresult，保持官方organization助理在前
        if ($containOfficialOrganization) {
            $data['list'] = $this->sortAgentsByIdOrder($data['list'], $agentIds);
        }

        $icons = [];
        foreach ($data['list'] as $agent) {
            if ($agent->getAgentAvatar()) {
                $icons[] = $agent->getAgentAvatar();
            }
        }

        $data['icons'] = $this->getIcons($agentDataIsolation->getCurrentOrganizationCode(), $icons);
        return $data;
    }

    private function getOrgAvailableAgentIds(AgentDataIsolation $agentDataIsolation, bool $containOfficialOrganization = false): array
    {
        $query = new DelightfulFLowVersionQuery();
        $query->setSelect(['id', 'root_id', 'visibility_config', 'organization_code']);
        $page = Page::createNoPage();
        $data = $this->agentDomainService->getOrgAvailableAgentIds($agentDataIsolation, $query, $page);

        $contactDataIsolation = $this->createContactDataIsolationByBase($agentDataIsolation);
        $userDepartmentIds = $this->delightfulDepartmentUserDomainService->getDepartmentIdsByUserId($contactDataIsolation, $agentDataIsolation->getCurrentUserId(), true);

        // 如果needcontain官方organization，则将官方organization的助理排在最前面
        if ($containOfficialOrganization) {
            $officialAgents = [];
            $nonOfficialAgents = [];

            foreach ($data['list'] as $agentVersion) {
                if (OfficialOrganizationUtil::isOfficialOrganization($agentVersion->getOrganizationCode())) {
                    $officialAgents[] = $agentVersion;
                } else {
                    $nonOfficialAgents[] = $agentVersion;
                }
            }

            // 重新sort：官方organization的助理在前
            $data['list'] = array_merge($officialAgents, $nonOfficialAgents);
        }
        $visibleAgents = [];
        foreach ($data['list'] as $agentVersion) {
            $visibilityConfig = $agentVersion->getVisibilityConfig();

            // 全部可见或无可见性configuration
            if ($visibilityConfig === null || $visibilityConfig->getVisibilityType() === VisibilityType::All->value) {
                $visibleAgents[] = $agentVersion->getAgentId();
                continue;
            }

            // 是否在个人可见中
            foreach ($visibilityConfig->getUsers() as $visibleUser) {
                if ($visibleUser->getId() === $agentDataIsolation->getCurrentUserId()) {
                    $visibleAgents[] = $agentVersion->getAgentId();
                }
            }

            // 是否在department可见中
            foreach ($visibilityConfig->getDepartments() as $visibleDepartment) {
                if (in_array($visibleDepartment->getId(), $userDepartmentIds)) {
                    $visibleAgents[] = $agentVersion->getAgentId();
                }
            }
        }
        return $visibleAgents;
    }

    /**
     * 按照指定的ID顺序对助理list进行sort.
     *
     * @param array<DelightfulAgentEntity> $agents 助理实体array
     * @param array $sortedIds sort的IDarray
     * @return array sort后的助理array
     */
    private function sortAgentsByIdOrder(array $agents, array $sortedIds): array
    {
        if (empty($agents) || empty($sortedIds)) {
            return $agents;
        }

        // 快速create ID 到实体的映射
        $agentMap = [];
        foreach ($agents as $agent) {
            $agentMap[$agent->getId()] = $agent;
        }

        // 按照指定顺序重新organizationarray
        $sortedAgents = [];
        foreach ($sortedIds as $id) {
            if (isset($agentMap[$id])) {
                $sortedAgents[] = $agentMap[$id];
            }
        }

        return $sortedAgents;
    }
}

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
     * @param DelightfulAgentQuery $query queryitemitem
     * @param Page $page paginationinfo
     * @return array{total: int, list: array<DelightfulAgentEntity>, icons: array<string,FileLink>}
     */
    public function queriesAvailable(Authenticatable $authorization, DelightfulAgentQuery $query, Page $page, bool $containOfficialOrganization = false): array
    {
        $agentDataIsolation = $this->createAgentDataIsolation($authorization);
        $agentDataIsolation->setContainOfficialOrganization($containOfficialOrganization);

        // generatecache key
        $cacheKey = sprintf('queriesAvailableAgents:user:%s:official:%s', $authorization->getId(), $containOfficialOrganization ? '1' : '0');

        // 尝试fromcacheget agentIds
        $agentIds = $this->redis->get($cacheKey);
        if ($agentIds !== false) {
            $agentIds = Json::decode($agentIds);
        } else {
            // getorganizationinsidecanuse Agent Ids
            $orgAgentIds = $this->getOrgAvailableAgentIds($agentDataIsolation, $containOfficialOrganization);

            // getfrom己havepermission id
            $permissionDataIsolation = new PermissionDataIsolation($agentDataIsolation->getCurrentOrganizationCode(), $agentDataIsolation->getCurrentUserId());
            $agentResources = $this->operationPermissionAppService->getResourceOperationByUserIds(
                $permissionDataIsolation,
                ResourceType::AgentCode,
                [$agentDataIsolation->getCurrentUserId()]
            )[$agentDataIsolation->getCurrentUserId()] ?? [];
            $selfAgentIds = array_keys($agentResources);

            // merge
            $agentIds = array_unique(array_merge($orgAgentIds, $selfAgentIds));

            // cacheresult（仅whennotforemptyo clock）
            if (! empty($agentIds)) {
                $this->redis->setex($cacheKey, 180, Json::encode($agentIds)); // cache 3 minute钟
            }
        }

        if (empty($agentIds)) {
            return ['total' => 0, 'list' => [], 'icons' => []];
        }
        $query->setIds($agentIds);
        $query->setStatus(DelightfulAgentVersionStatus::ENTERPRISE_ENABLED->value);
        $query->setSelect(['id', 'robot_name', 'robot_avatar', 'robot_description', 'created_at', 'flow_code', 'organization_code']);

        $data = $this->agentDomainService->queries($agentDataIsolation, $query, $page);

        // ifcontain官方organization，按照传入IDorder重新sortresult，maintain官方organization助理infront
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

        // ifneedcontain官方organization，thenwill官方organization助理rowinmostfrontsurface
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

            // 重新sort：官方organization助理infront
            $data['list'] = array_merge($officialAgents, $nonOfficialAgents);
        }
        $visibleAgents = [];
        foreach ($data['list'] as $agentVersion) {
            $visibilityConfig = $agentVersion->getVisibilityConfig();

            // all部visibleornovisiblepropertyconfiguration
            if ($visibilityConfig === null || $visibilityConfig->getVisibilityType() === VisibilityType::All->value) {
                $visibleAgents[] = $agentVersion->getAgentId();
                continue;
            }

            // whetherinpersonvisiblemiddle
            foreach ($visibilityConfig->getUsers() as $visibleUser) {
                if ($visibleUser->getId() === $agentDataIsolation->getCurrentUserId()) {
                    $visibleAgents[] = $agentVersion->getAgentId();
                }
            }

            // whetherindepartmentvisiblemiddle
            foreach ($visibilityConfig->getDepartments() as $visibleDepartment) {
                if (in_array($visibleDepartment->getId(), $userDepartmentIds)) {
                    $visibleAgents[] = $agentVersion->getAgentId();
                }
            }
        }
        return $visibleAgents;
    }

    /**
     * 按照finger定IDorderto助理listconductsort.
     *
     * @param array<DelightfulAgentEntity> $agents 助理实bodyarray
     * @param array $sortedIds sortIDarray
     * @return array sortback助理array
     */
    private function sortAgentsByIdOrder(array $agents, array $sortedIds): array
    {
        if (empty($agents) || empty($sortedIds)) {
            return $agents;
        }

        // 快speedcreate ID to实bodymapping
        $agentMap = [];
        foreach ($agents as $agent) {
            $agentMap[$agent->getId()] = $agent;
        }

        // 按照finger定order重新organizationarray
        $sortedAgents = [];
        foreach ($sortedIds as $id) {
            if (isset($agentMap[$id])) {
                $sortedAgents[] = $agentMap[$id];
            }
        }

        return $sortedAgents;
    }
}

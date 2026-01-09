<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Admin\Agent\Service;

use App\Application\Admin\Agent\Assembler\AgentAssembler;
use App\Application\Admin\Agent\DTO\AdminAgentDetailDTO;
use App\Application\Admin\Agent\Service\Extra\Factory\ExtraDetailAppenderFactory;
use App\Application\Kernel\AbstractKernelAppService;
use App\Domain\Admin\Entity\AdminGlobalSettingsEntity;
use App\Domain\Admin\Entity\ValueObject\AdminGlobalSettingsName;
use App\Domain\Admin\Entity\ValueObject\AdminGlobalSettingsType;
use App\Domain\Admin\Entity\ValueObject\AgentFilterType;
use App\Domain\Admin\Entity\ValueObject\Extra\AbstractSettingExtra;
use App\Domain\Admin\Entity\ValueObject\Extra\DefaultFriendExtra;
use App\Domain\Admin\Service\AdminGlobalSettingsDomainService;
use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Agent\Service\DelightfulAgentVersionDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\DataIsolation as ContactDataIsolation;
use App\Domain\Contact\Service\DelightfulDepartmentDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentUserDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\Group\Service\DelightfulGroupDomainService;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\TargetType;
use App\Domain\Permission\Service\OperationPermissionDomainService;
use App\Infrastructure\Core\PageDTO;
use App\Interfaces\Admin\DTO\AgentGlobalSettingsDTO;
use App\Interfaces\Admin\DTO\Extra\AbstractSettingExtraDTO;
use App\Interfaces\Admin\DTO\Extra\Item\AgentItemDTO;
use App\Interfaces\Admin\DTO\Request\QueryPageAgentDTO;
use App\Interfaces\Admin\DTO\Response\GetPublishedAgentsResponseDTO;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Permission\Assembler\OperationPermissionAssembler;
use App\Interfaces\Permission\DTO\ResourceAccessDTO;
use Delightful\CloudFile\Kernel\Struct\FileLink;
use Qbhy\HyperfAuth\Authenticatable;

use function Hyperf\Collection\last;
use function Hyperf\Translation\__;

class AdminAgentAppService extends AbstractKernelAppService
{
    public function __construct(
        private readonly AdminGlobalSettingsDomainService $globalSettingsDomainService,
        private readonly DelightfulAgentDomainService $delightfulAgentDomainService,
        private readonly DelightfulAgentVersionDomainService $delightfulAgentVersionDomainService,
        private readonly FileDomainService $fileDomainService,
        private readonly DelightfulUserDomainService $userDomainService,
        private readonly OperationPermissionDomainService $operationPermissionDomainService,
        private readonly DelightfulDepartmentDomainService $delightfulDepartmentDomainService,
        private readonly DelightfulDepartmentUserDomainService $delightfulDepartmentUserDomainService,
        private readonly DelightfulGroupDomainService $delightfulGroupDomainService,
    ) {
    }

    /**
     * delete助理.
     */
    public function deleteAgent(DelightfulUserAuthorization $authenticatable, string $agentId)
    {
        $this->delightfulAgentDomainService->deleteAgentById($agentId, $authenticatable->getOrganizationCode());
    }

    /**
     * get助理detail.
     */
    public function getAgentDetail(DelightfulUserAuthorization $authorization, string $agentId): AdminAgentDetailDTO
    {
        $agentEntity = $this->delightfulAgentDomainService->getAgentById($agentId);
        $adminAgentDetail = new AdminAgentDetailDTO();

        $agentVersionEntity = new DelightfulAgentVersionEntity();
        if ($agentEntity->getAgentVersionId()) {
            $agentVersionEntity = $this->delightfulAgentVersionDomainService->getAgentById($agentEntity->getAgentVersionId());
            // onlypublish助理才willhavepermission管控
            $resourceAccessDTO = $this->getAgentResource($authorization, $agentId);
            $adminAgentDetail->setResourceAccess($resourceAccessDTO);
        } else {
            $agentVersionEntity->setAgentName($agentEntity->getAgentName());
            $agentVersionEntity->setAgentDescription($agentEntity->getAgentDescription());
            $agentVersionEntity->setVersionNumber(__('agent.no_version'));
            $agentVersionEntity->setAgentAvatar($agentEntity->getAgentAvatar());
            $agentVersionEntity->setCreatedAt($agentEntity->getCreatedAt());
        }
        $adminAgentDetailDTO = AgentAssembler::toAdminAgentDetail($agentEntity, $agentVersionEntity);
        $fileLink = $this->fileDomainService->getLink($authorization->getOrganizationCode(), $agentVersionEntity->getAgentAvatar());
        if ($fileLink) {
            $adminAgentDetailDTO->setAgentAvatar($fileLink->getUrl());
        }

        $delightfulUserEntity = $this->userDomainService->getUserById($agentEntity->getCreatedUid());
        $adminAgentDetailDTO->setCreatedName($delightfulUserEntity->getNickname());

        return $adminAgentDetailDTO;
    }

    /**
     * get企业down所have助理create者.
     * @return array<array{user_id:string,nickname:string,avatar:string}>
     */
    public function getOrganizationAgentsCreators(DelightfulUserAuthorization $authorization): array
    {
        // get所have助理
        $agentCreators = $this->delightfulAgentDomainService->getOrganizationAgentsCreators($authorization->getOrganizationCode());
        $dataIsolation = DataIsolation::create($authorization->getOrganizationCode(), $authorization->getId());
        $userMap = $this->userDomainService->getByUserIds($dataIsolation, $agentCreators);

        // 收collectionuseravatarkey
        $avatars = array_filter(array_map(function ($user) {
            return $user->getAvatarUrl();
        }, $userMap), fn ($avatar) => ! empty($avatar));

        // getavatarURL
        $fileLinks = $this->fileDomainService->getLinks($authorization->getOrganizationCode(), $avatars);

        $result = [];
        foreach ($userMap as $user) {
            $avatarKey = $user->getAvatarUrl();
            $avatarUrl = '';
            if (! empty($avatarKey) && isset($fileLinks[$avatarKey])) {
                $avatarUrl = $fileLinks[$avatarKey]->getUrl();
            }

            $result[] = [
                'user_id' => $user->getUserId(),
                'nickname' => $user->getNickname(),
                'avatar' => $avatarUrl,
            ];
        }
        return $result;
    }

    /**
     * query企业down所have助理,itemitemquery：status，createperson，search.
     */
    public function queriesAgents(DelightfulUserAuthorization $authorization, QueryPageAgentDTO $query): PageDTO
    {
        $delightfulAgentEntities = $this->delightfulAgentDomainService->queriesAgents($authorization->getOrganizationCode(), $query);
        if (empty($delightfulAgentEntities)) {
            return new PageDTO();
        }
        $delightfulAgentEntityCount = $this->delightfulAgentDomainService->queriesAgentsCount($authorization->getOrganizationCode(), $query);
        // get所have avatar
        $avatars = array_filter(array_column($delightfulAgentEntities, 'agent_avatar'), fn ($avatar) => ! empty($avatar));
        $fileLinks = $this->fileDomainService->getLinks($authorization->getOrganizationCode(), $avatars);
        // get助理createperson
        $createdUids = array_column($delightfulAgentEntities, 'created_uid');
        $createdUsers = $this->userDomainService->getUserByIdsWithoutOrganization($createdUids);
        $agentVersionIds = array_filter(array_column($delightfulAgentEntities, 'agent_version_id'), fn ($agentVersionId) => $agentVersionId !== null);
        $agentVersions = $this->delightfulAgentVersionDomainService->getAgentByIds($agentVersionIds);

        // buildcreatepersonmapping
        $createdUserMap = [];
        foreach ($createdUsers as $user) {
            $createdUserMap[$user->getUserId()] = $user;
        }

        // build助理versionmapping
        $agentVersionMap = [];
        foreach ($agentVersions as $version) {
            $agentVersionMap[$version->getId()] = $version;
        }

        // aggregatedata
        $items = [];
        foreach ($delightfulAgentEntities as $agent) {
            $adminAgentDTO = AgentAssembler::entityToDTO($agent);

            // setavatar
            $avatar = $fileLinks[$agent->getAgentAvatar()] ?? null;
            $adminAgentDTO->setAgentAvatar($avatar?->getUrl() ?? '');

            // setcreatepersoninfo
            $createdUser = $createdUserMap[$agent->getCreatedUid()] ?? null;
            if ($createdUser) {
                $adminAgentDTO->setCreatedName($createdUser->getNickname());
            }

            // setversioninfo
            $versionId = $agent->getAgentVersionId();
            if ($versionId && isset($agentVersionMap[$versionId])) {
                $version = $agentVersionMap[$versionId];
                $adminAgentDTO->setReleaseScope($version->getReleaseScope());
                $adminAgentDTO->setReviewStatus($version->getReviewStatus());
                $adminAgentDTO->setApprovalStatus($version->getApprovalStatus());
            }

            $items[] = $adminAgentDTO;
        }
        $pageDTO = new PageDTO();
        $pageDTO->setPage($query->getPage());
        $pageDTO->setTotal($delightfulAgentEntityCount);
        $pageDTO->setList($items);
        return $pageDTO;
    }

    /**
     * @param DelightfulUserAuthorization $authorization
     * @return AgentGlobalSettingsDTO[]
     */
    public function getGlobalSettings(Authenticatable $authorization): array
    {
        $dataIsolation = $this->createAdminDataIsolation($authorization);
        $allSettings = [];

        // get所have Agent 相closesettype
        $agentSettingsTypes = AdminGlobalSettingsType::getAssistantGlobalSettingsType();

        // onetimepropertyget所haveset
        $settings = $this->globalSettingsDomainService->getSettingsByTypes(
            $agentSettingsTypes,
            $dataIsolation
        );

        // process所haveset
        foreach ($settings as $setting) {
            $settingDTO = (new AgentGlobalSettingsDTO($setting->toArray()));
            ExtraDetailAppenderFactory::createStrategy($settingDTO->getExtra())->appendExtraDetail($settingDTO->getExtra(), $authorization);
            $settingName = AdminGlobalSettingsName::getByType($setting->getType());
            $allSettings[$settingName] = $settingDTO;
        }

        return $allSettings;
    }

    /**
     * @param AgentGlobalSettingsDTO[] $settings
     * @return AgentGlobalSettingsDTO[]
     */
    public function updateGlobalSettings(
        Authenticatable $authorization,
        array $settings
    ): array {
        $dataIsolation = $this->createAdminDataIsolation($authorization);
        $agentSettingsTypes = array_map(fn ($type) => $type->value, AdminGlobalSettingsType::getAssistantGlobalSettingsType());
        $agentSettingsTypes = array_flip($agentSettingsTypes);

        // filteroutneedupdateset
        $settingsToUpdate = array_filter($settings, function ($setting) use ($agentSettingsTypes) {
            return isset($agentSettingsTypes[$setting->getType()->value]);
        });

        // convertfor实bodyobject
        $entities = array_map(function ($setting) {
            /** @var AbstractSettingExtraDTO $extra */
            $extra = $setting->getExtra();
            return (new AdminGlobalSettingsEntity())
                ->setType($setting->getType())
                ->setStatus($setting->getStatus())
                ->setExtra(AbstractSettingExtra::fromDataByType($extra->toArray(), $setting->getType()));
        }, $settingsToUpdate);

        // onetimepropertyupdate所haveset
        $updatedSettings = $this->globalSettingsDomainService->updateSettingsBatch($entities, $dataIsolation);

        // convertforDTOreturn
        return array_map(fn ($setting) => new AgentGlobalSettingsDTO($setting->toArray()), $updatedSettings);
    }

    public function getPublishedAgents(Authenticatable $authorization, string $pageToken, int $pageSize, AgentFilterType $type): GetPublishedAgentsResponseDTO
    {
        // getdata隔离objectandgetcurrentorganizationorganizationcode
        /** @var DelightfulUserAuthorization $authorization */
        $organizationCode = $authorization->getOrganizationCode();

        // getenable机器personlist
        $enabledAgents = $this->delightfulAgentDomainService->getEnabledAgents();

        // according tofiltertypefilter
        $enabledAgents = $this->filterEnableAgentsByType($authorization, $enabledAgents, $type);

        // extractenable机器personlistmiddle agent_version_id
        $agentVersionIds = array_column($enabledAgents, 'agent_version_id');

        // getfinger定organizationand机器personversion机器persondataand其total
        $agentVersions = $this->delightfulAgentVersionDomainService->getAgentsByOrganizationWithCursor(
            $organizationCode,
            $agentVersionIds,
            $pageToken,
            $pageSize
        );

        if (empty($agentVersions)) {
            return new GetPublishedAgentsResponseDTO();
        }

        // getavatarurl
        $avatars = array_column($agentVersions, 'agent_avatar');
        $fileLinks = $this->fileDomainService->getLinks($organizationCode, $avatars);

        // convertforAgentItemDTOformat
        /** @var array<AgentItemDTO> $result */
        $result = [];
        foreach ($agentVersions as $agent) {
            /** @var ?FileLink $avatar */
            $avatar = $fileLinks[$agent->getAgentAvatar()] ?? null;
            $item = new AgentItemDTO();
            $item->setAgentId($agent->getAgentId());
            $item->setName($agent->getAgentName());
            $item->setAvatar($avatar?->getUrl() ?? '');
            $result[] = $item;
        }
        /** @var AgentItemDTO $lastAgent */
        $lastAgent = last($result);
        $hasMore = count($agentVersions) === $pageSize;
        return new GetPublishedAgentsResponseDTO([
            'items' => $result,
            'has_more' => $hasMore,
            'page_token' => $lastAgent->getAgentId(),
        ]);
    }

    private function getAgentResource(DelightfulUserAuthorization $authorization, string $agentId): ResourceAccessDTO
    {
        $dataIsolation = $this->createPermissionDataIsolation($authorization);
        $operationPermissionEntities = $this->operationPermissionDomainService->listByResource($dataIsolation, ResourceType::AgentCode, $agentId);
        $userIds = [];
        $departmentIds = [];
        $groupIds = [];
        foreach ($operationPermissionEntities as $item) {
            if ($item->getTargetType() === TargetType::UserId) {
                $userIds[] = $item->getTargetId();
            }
            if ($item->getTargetType() === TargetType::DepartmentId) {
                $departmentIds[] = $item->getTargetId();
            }
            if ($item->getTargetType() === TargetType::GroupId) {
                $groupIds[] = $item->getTargetId();
            }
        }
        $contactDataIsolation = ContactDataIsolation::simpleMake($dataIsolation->getCurrentOrganizationCode(), $dataIsolation->getCurrentUserId());
        // according to userid getuserinfo
        $users = $this->userDomainService->getByUserIds($contactDataIsolation, $userIds);
        // getuser departmentId
        $userDepartmentList = $this->delightfulDepartmentUserDomainService->getDepartmentIdsByUserIds($contactDataIsolation, $userIds);
        foreach ($userDepartmentList as $userDepartmentIds) {
            $departmentIds = array_merge($departmentIds, $userDepartmentIds);
        }
        $departments = $this->delightfulDepartmentDomainService->getDepartmentByIds($contactDataIsolation, $departmentIds, true);
        // getgroupinfo
        $groups = $this->delightfulGroupDomainService->getGroupsInfoByIds($groupIds, $contactDataIsolation, true);
        return OperationPermissionAssembler::createResourceAccessDTO(ResourceType::AgentCode, $agentId, $operationPermissionEntities, $users, $departments, $groups);
    }

    /**
     * @param array<DelightfulAgentEntity> $enabledAgents
     * @return array<DelightfulAgentEntity>
     */
    private function filterEnableAgentsByType(Authenticatable $authorization, array $enabledAgents, AgentFilterType $type): array
    {
        if ($type === AgentFilterType::ALL) {
            return $enabledAgents;
        }

        $selectedDefaultFriendRootIds = array_flip($this->getSelectedDefaultFriendRootIds($authorization));
        // iftypeforSELECTED_DEFAULT_FRIEND，then只return选middledefault好友
        if ($type === AgentFilterType::SELECTED_DEFAULT_FRIEND) {
            return array_filter($enabledAgents, function ($agent) use ($selectedDefaultFriendRootIds) {
                return isset($selectedDefaultFriendRootIds[$agent->getId()]);
            });
        }
        // iftypeforNOT_SELECTED_DEFAULT_FRIEND，then只return未选middledefault好友
        /* @phpstan-ignore-next-line */
        if ($type === AgentFilterType::NOT_SELECTED_DEFAULT_FRIEND) {
            return array_filter($enabledAgents, function ($agent) use ($selectedDefaultFriendRootIds) {
                return ! isset($selectedDefaultFriendRootIds[$agent->getId()]);
            });
        }
        /* @phpstan-ignore-next-line */
        return $enabledAgents;
    }

    /**
     * @return array<string>
     */
    private function getSelectedDefaultFriendRootIds(Authenticatable $authorization): array
    {
        $dataIsolation = $this->createAdminDataIsolation($authorization);
        $settings = $this->globalSettingsDomainService->getSettingsByType(AdminGlobalSettingsType::DEFAULT_FRIEND, $dataIsolation);
        /** @var ?DefaultFriendExtra $extra */
        $extra = $settings->getExtra();
        return $extra ? $extra->getSelectedAgentIds() : [];
    }
}

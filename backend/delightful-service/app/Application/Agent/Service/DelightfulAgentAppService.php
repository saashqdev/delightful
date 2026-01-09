<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Agent\Service;

use App\Application\Chat\Service\DelightfulUserContactAppService;
use App\Application\Flow\Service\DelightfulFlowAIModelAppService;
use App\Domain\Agent\Constant\DelightfulAgentQueryStatus;
use App\Domain\Agent\Constant\DelightfulAgentReleaseStatus;
use App\Domain\Agent\Constant\DelightfulAgentVersionStatus;
use App\Domain\Agent\DTO\DelightfulAgentDTO;
use App\Domain\Agent\DTO\DelightfulAgentVersionDTO;
use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Domain\Agent\Entity\DelightfulBotThirdPlatformChatEntity;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulAgentQuery;
use App\Domain\Agent\Entity\ValueObject\Visibility\User;
use App\Domain\Agent\Entity\ValueObject\Visibility\VisibilityConfig;
use App\Domain\Agent\Entity\ValueObject\Visibility\VisibilityType;
use App\Domain\Agent\Factory\DelightfulAgentVersionFactory;
use App\Domain\Agent\VO\DelightfulAgentVO;
use App\Domain\Chat\Event\Agent\DelightfulAgentInstructEvent;
use App\Domain\Contact\DTO\FriendQueryDTO;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\DataIsolation as ContactDataIsolation;
use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\DelightfulFlowVersionEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\Operation;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\ErrorCode\AgentErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Flow\Assembler\Flow\DelightfulFlowAssembler;
use App\Interfaces\Flow\DTO\Flow\DelightfulFlowDTO;
use App\Interfaces\Kernel\Assembler\FileAssembler;
use Delightful\AsyncEvent\AsyncEventUtil;
use Delightful\CloudFile\Kernel\Struct\FileLink;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\DbConnection\Db;
use InvalidArgumentException;
use JetBrains\PhpStorm\Deprecated;
use Psr\Log\LoggerInterface;
use Qbhy\HyperfAuth\Authenticatable;
use Throwable;

class DelightfulAgentAppService extends AbstractAppService
{
    protected LoggerInterface $logger;

    /**
     * @param DelightfulUserAuthorization $authorization
     * @return array{total: int, list: array<DelightfulAgentEntity>, avatars: array<FileLink>}
     */
    public function queries(Authenticatable $authorization, DelightfulAgentQuery $query, Page $page): array
    {
        $permissionDataIsolation = new PermissionDataIsolation($authorization->getOrganizationCode(), $authorization->getId());

        $agentResources = $this->operationPermissionAppService->getResourceOperationByUserIds(
            $permissionDataIsolation,
            ResourceType::AgentCode,
            [$authorization->getId()]
        )[$authorization->getId()] ?? [];
        $agentIds = array_keys($agentResources);

        $query->setIds($agentIds);
        $query->setWithLastVersionInfo(true);

        // querycurrent具有permission的
        $data = $this->delightfulAgentDomainService->queries($query, $page);
        $avatars = [];
        foreach ($data['list'] as $agent) {
            $avatars[] = $agent->getAgentAvatar();
            $operation = $agentResources[$agent->getId()] ?? Operation::None;
            $agent->setUserOperation($operation->value);
        }
        $data['avatars'] = $this->fileDomainService->getLinks($authorization->getOrganizationCode(), $avatars);
        return $data;
    }

    // create/修改assistant
    #[Transactional]
    /**
     * @param DelightfulUserAuthorization $authorization
     */
    public function saveAgent(Authenticatable $authorization, DelightfulAgentDTO $delightfulAgentDTO): DelightfulAgentEntity
    {
        $delightfulAgentEntity = $delightfulAgentDTO->toEntity();
        $delightfulAgentEntity->setAgentAvatar(FileAssembler::formatPath($delightfulAgentEntity->getAgentAvatar()));
        if (empty($delightfulAgentEntity->getId())) {
            $delightfulFlowEntity = new DelightfulFlowEntity();
            $delightfulFlowEntity->setName($delightfulAgentEntity->getAgentName());
            $delightfulFlowEntity->setDescription($delightfulAgentEntity->getAgentDescription());
            $delightfulFlowEntity->setIcon($delightfulAgentEntity->getAgentAvatar());
            $delightfulFlowEntity->setType(Type::Main);
            $delightfulFlowEntity->setOrganizationCode($delightfulAgentEntity->getOrganizationCode());
            $delightfulFlowEntity->setCreator($delightfulAgentEntity->getCreatedUid());
            $flowDataIsolation = new FlowDataIsolation($delightfulAgentEntity->getOrganizationCode(), $delightfulAgentEntity->getCreatedUid());
            $delightfulFlowEntity = $this->delightfulFlowDomainService->createByAgent($flowDataIsolation, $delightfulFlowEntity);

            $delightfulAgentEntity->setFlowCode($delightfulFlowEntity->getCode());
            $delightfulAgentEntity->setStatus(DelightfulAgentVersionStatus::ENTERPRISE_ENABLED->value);
        } else {
            // 修改得checkpermission
            $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $delightfulAgentEntity->getId())->validate('edit', $delightfulAgentEntity->getId());
        }

        $delightfulAgentEntity = $this->delightfulAgentDomainService->saveAgent($delightfulAgentEntity);
        $fileLink = $this->fileDomainService->getLink($delightfulAgentDTO->getCurrentOrganizationCode(), $delightfulAgentEntity->getAgentAvatar());
        if ($fileLink) {
            $delightfulAgentEntity->setAgentAvatar($fileLink->getUrl());
        }

        return $delightfulAgentEntity;
    }

    // deleteassistant

    /**
     * @param DelightfulUserAuthorization $authorization
     */
    public function deleteAgentById(Authenticatable $authorization, string $id): bool
    {
        $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $id)->validate('d', $id);
        return $this->delightfulAgentDomainService->deleteAgentById($id, $authorization->getOrganizationCode());
    }

    // get指定user的assistant
    #[Deprecated]
    public function getAgentsByUserIdPage(string $userId, int $page, int $pageSize, string $agentName, DelightfulAgentQueryStatus $queryStatus): array
    {
        $query = new DelightfulAgentQuery();
        $query->setCreatedUid($userId);
        $query->setAgentName($agentName);
        $query->setOrder(['id' => 'desc']);

        // settingversionstatusfilter
        if ($queryStatus === DelightfulAgentQueryStatus::PUBLISHED) {
            $query->setHasVersion(true);
        } elseif ($queryStatus === DelightfulAgentQueryStatus::UNPUBLISHED) {
            $query->setHasVersion(false);
        }

        $pageObj = new Page($page, $pageSize);

        $data = $this->delightfulAgentDomainService->queries($query, $pageObj);
        if (empty($data['list'])) {
            return [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => 0,
                'list' => [],
            ];
        }

        $agentVersionIds = array_filter(array_map(static function ($agent) {
            return $agent->getAgentVersionId();
        }, $data['list']));

        $agentVersions = empty($agentVersionIds) ? [] : $this->delightfulAgentVersionDomainService->listAgentVersionsByIds($agentVersionIds);

        $result = array_map(function ($agent) use ($agentVersions) {
            $agentData = $agent->toArray();

            $fileLink = $this->fileDomainService->getLink($agent->getOrganizationCode(), $agent->getAgentAvatar());
            if ($fileLink !== null) {
                $agentData['agent_avatar'] = $fileLink->getUrl();
            }

            $agentVersionId = $agent->getAgentVersionId();
            $agentData['agent_version'] = empty($agentVersionId) ? null : ($agentVersions[$agentVersionId] ?? null);

            return $agentData;
        }, $data['list']);

        return [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $data['total'],
            'list' => $result,
        ];
    }

    public function getAgentById(string $agentVersionId, DelightfulUserAuthorization $authorization): DelightfulAgentVersionEntity
    {
        try {
            // 首先尝试作为 agent_version_id 从已publishversion中get
            $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getAgentById($agentVersionId);
        } catch (Throwable $e) {
            // 如果fail，从 delightful_bots 表getoriginalassistantdata，并convert为 DelightfulAgentVersionEntity（version号为 null）
            try {
                $delightfulAgentEntity = $this->delightfulAgentDomainService->getById($agentVersionId);
                $delightfulAgentVersionEntity = $this->convertAgentToAgentVersion($delightfulAgentEntity);
            } catch (Throwable) {
                // 如果都fail，throworiginalexception
                throw $e;
            }
        }

        $fileLink = $this->fileDomainService->getLink($authorization->getOrganizationCode(), $delightfulAgentVersionEntity->getAgentAvatar());
        if ($fileLink !== null) {
            $delightfulAgentVersionEntity->setAgentAvatar($fileLink->getUrl());
        }

        $delightfulAgentVersionEntity->setInstructs($this->processInstructionsImages($delightfulAgentVersionEntity->getInstructs(), $delightfulAgentVersionEntity->getOrganizationCode()));

        return $delightfulAgentVersionEntity;
    }

    // getpublishversion的assistant,对于user的
    public function getAgentVersionByIdForUser(string $agentVersionId, DelightfulUserAuthorization $authorization): DelightfulAgentVO
    {
        $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getAgentById($agentVersionId);
        $organizationCode = $authorization->getOrganizationCode();

        $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $delightfulAgentVersionEntity->getAgentId())->validate('r', $delightfulAgentVersionEntity->getAgentId());

        $fileLink = $this->fileDomainService->getLink($organizationCode, $delightfulAgentVersionEntity->getAgentAvatar());
        if ($fileLink !== null) {
            $delightfulAgentVersionEntity->setAgentAvatar($fileLink->getUrl());
        }

        $delightfulAgentVersionEntity->setInstructs($this->processInstructionsImages($delightfulAgentVersionEntity->getInstructs(), $delightfulAgentVersionEntity->getOrganizationCode()));

        $delightfulAgentEntity = $this->delightfulAgentDomainService->getById($delightfulAgentVersionEntity->getAgentId());

        $delightfulAgentEntity->setInstructs($this->processInstructionsImages($delightfulAgentEntity->getInstructs(), $delightfulAgentEntity->getOrganizationCode()));
        if ($delightfulAgentEntity->getStatus() === DelightfulAgentVersionStatus::ENTERPRISE_DISABLED->value) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_does_not_exist');
        }
        $fileLink = $this->fileDomainService->getLink($organizationCode, $delightfulAgentEntity->getAgentAvatar());
        if ($fileLink !== null) {
            $delightfulAgentEntity->setAgentAvatar($fileLink->getUrl());
        }
        $delightfulAgentVO = new DelightfulAgentVO();
        $delightfulAgentVO->setAgentEntity($delightfulAgentEntity);
        $delightfulAgentVO->setAgentVersionEntity($delightfulAgentVersionEntity);
        $createdUid = $delightfulAgentVersionEntity->getCreatedUid();
        $delightfulUserEntity = $this->delightfulUserDomainService->getUserById($createdUid);
        if ($delightfulUserEntity !== null) {
            $userDto = new DelightfulUserEntity();
            $userDto->setAvatarUrl($delightfulUserEntity->getAvatarUrl());
            $userDto->setNickname($delightfulUserEntity->getNickname());
            $userDto->setUserId($delightfulUserEntity->getUserId());
            $delightfulAgentVO->setDelightfulUserEntity($userDto);
        }
        // according toworkflowidgetworkflowinfo
        $flowDataIsolation = new FlowDataIsolation($authorization->getOrganizationCode(), $authorization->getId());
        $delightfulFlowVersionEntity = $this->delightfulFlowVersionDomainService->show($flowDataIsolation, $delightfulAgentVersionEntity->getFlowCode(), $delightfulAgentVersionEntity->getFlowVersion());
        $delightfulFlowEntity = $delightfulFlowVersionEntity->getDelightfulFlow();

        $delightfulFlowEntity->setUserOperation($delightfulAgentEntity->getUserOperation());
        $delightfulAgentVO->setDelightfulFlowEntity($delightfulFlowEntity);
        $friendQueryDTO = new FriendQueryDTO();
        $friendQueryDTO->setAiCodes([$delightfulAgentVersionEntity->getFlowCode()]);

        // data隔离handle
        $friendDataIsolation = new ContactDataIsolation();
        $friendDataIsolation->setCurrentUserId($authorization->getId());
        $friendDataIsolation->setCurrentOrganizationCode($organizationCode);

        // getuser代理的好友列表
        $userAgentFriends = $this->delightfulUserDomainService->getUserAgentFriendsList($friendQueryDTO, $friendDataIsolation);

        $delightfulAgentVO->setIsAdd(isset($userAgentFriends[$delightfulAgentVersionEntity->getFlowCode()]));

        $visibilityConfig = $delightfulAgentVersionEntity->getVisibilityConfig();

        $this->setVisibilityConfigDetails($visibilityConfig, $authorization);
        return $delightfulAgentVO;
    }

    /**
     * get企业内部的assistant.
     * @param DelightfulUserAuthorization $authorization
     */
    public function getAgentsByOrganizationPage(Authenticatable $authorization, int $page, int $pageSize, string $agentName): array
    {
        if (! $authorization instanceof DelightfulUserAuthorization) {
            return [];
        }

        $organizationCode = $authorization->getOrganizationCode();
        $currentUserId = $authorization->getId();

        // getenable的assistantversion列表
        $agentVersions = $this->getEnabledAgentVersions($organizationCode, $page, $pageSize, $agentName);
        if (empty($agentVersions)) {
            return $this->getEmptyPageResult($page, $pageSize);
        }

        // according to可见性configurationfilterassistant
        $visibleAgentVersions = $this->filterVisibleAgents($agentVersions, $currentUserId, $organizationCode);
        if (empty($visibleAgentVersions)) {
            return $this->getEmptyPageResult($page, $pageSize);
        }

        // convert为arrayformat
        $agentVersions = DelightfulAgentVersionFactory::toArrays($visibleAgentVersions);

        // getassistanttotal
        $totalAgentsCount = $this->getTotalAgentsCount($organizationCode, $agentName);

        // handlecreate者info
        $this->enrichCreatorInfo($agentVersions);

        // handleavatar和好友status
        $this->enrichAgentAvatarAndFriendStatus($agentVersions, $authorization);

        return [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $totalAgentsCount,
            'list' => $agentVersions,
        ];
    }

    /**
     * getchat模式可用assistant列表（全量data，不pagination）.
     * @param Authenticatable $authorization userauthorization
     * @param DelightfulAgentQuery $query querycondition
     * @return array assistant列表及conversationID
     */
    public function getChatModeAvailableAgents(Authenticatable $authorization, DelightfulAgentQuery $query): array
    {
        if (! $authorization instanceof DelightfulUserAuthorization) {
            return ['total' => 0, 'list' => []];
        }

        // 1. use queriesAvailable query官方+userorganization的assistant（全量data）
        $fullQuery = clone $query;
        $fullPage = Page::createNoPage(); // get全量data
        $agentAppService = di(AgentAppService::class);
        $fullData = $agentAppService->queriesAvailable($authorization, $fullQuery, $fullPage, true);

        if (empty($fullData['list'])) {
            return ['total' => 0, 'list' => []];
        }

        // get全量assistant实体
        $totalCount = $fullData['total'];
        /** @var DelightfulAgentEntity[] $agentEntities */
        $agentEntities = $fullData['list'];

        // getassistantconversationmapping
        [$flowCodeToUserIdMap, $conversationMap] = $this->getAgentConversationMapping($agentEntities, $authorization);

        // 批量getavatarlink
        $avatarUrlMap = $this->batchGetAvatarUrls($agentEntities, $authorization);

        // convert为arrayformat并添加conversationID
        $result = [];
        foreach ($agentEntities as $agent) {
            $agentData = $agent->toArray();

            // 添加 agent_id field，value同 id
            $agentData['agent_id'] = $agentData['id'];

            // 添加是否为官方organization标识
            $agentData['is_office'] = OfficialOrganizationUtil::isOfficialOrganization($agent->getOrganizationCode());

            // handleavatarlink
            $agentData['agent_avatar'] = $avatarUrlMap[$agent->getAgentAvatar()] ?? null;
            $agentData['robot_avatar'] = $agentData['agent_avatar'];

            // 添加assistantuserID和conversationID
            $flowCode = $agent->getFlowCode();
            if (isset($flowCodeToUserIdMap[$flowCode])) {
                $userId = $flowCodeToUserIdMap[$flowCode];
                $agentData['user_id'] = $userId;

                // 添加conversationID（如果存在）
                if (isset($conversationMap[$userId])) {
                    $agentData['conversation_id'] = $conversationMap[$userId];
                }
            }

            $result[] = $agentData;
        }

        return [
            'total' => $totalCount,
            'list' => $result,
        ];
    }

    // getapplication市场assistant
    public function getAgentsFromMarketplacePage(int $page, int $pageSize): array
    {
        // 查出enable的assistant
        $agents = $this->delightfulAgentDomainService->getEnabledAgents();
        // use array_column 提取 agent_version_id
        $agentIds = array_column($agents, 'agent_version_id');
        $agentsFromMarketplace = $this->delightfulAgentVersionDomainService->getAgentsFromMarketplace($agentIds, $page, $pageSize);
        $agentsFromMarketplaceCount = $this->delightfulAgentVersionDomainService->getAgentsFromMarketplaceCount($agentIds);
        return ['page' => $page, 'page_size' => $pageSize, 'total' => $agentsFromMarketplaceCount, 'list' => $agentsFromMarketplace];
    }

    // publishassistantversion

    /**
     * @param null|DelightfulBotThirdPlatformChatEntity[] $thirdPlatformList
     */
    #[Transactional]
    public function releaseAgentVersion(Authenticatable $authorization, DelightfulAgentVersionDTO $agentVersionDTO, ?DelightfulFlowEntity $publishDelightfulFlowEntity = null, ?array $thirdPlatformList = null): array
    {
        $key = 'agent:release:' . $agentVersionDTO->getAgentId();
        $userId = $authorization->getId();
        if (! $this->redisLocker->mutexLock($key, $userId, 3)) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.publish_version_has_latest_changes_please_republish');
        }
        $permissionDataIsolation = $this->createPermissionDataIsolation($authorization);

        $this->getAgentOperation($permissionDataIsolation, $agentVersionDTO->getAgentId())->validate('edit', $agentVersionDTO->getAgentId());

        $agentVersionDTO->setCreatedUid($authorization->getId());

        $agentVersionDTO->check();

        // 只有publish到企业才把自己给添加
        if ($agentVersionDTO->getReleaseScope() === DelightfulAgentReleaseStatus::PUBLISHED_TO_ENTERPRISE->value) {
            $visibilityConfig = $agentVersionDTO->getVisibilityConfig();
            if (! $visibilityConfig) {
                $visibilityConfig = new VisibilityConfig();
                $agentVersionDTO->setVisibilityConfig($visibilityConfig);
            }

            $currentUserId = $authorization->getId();
            if (! in_array($currentUserId, array_column($visibilityConfig->getUsers(), 'id'))) {
                $user = new User();
                $user->setId($currentUserId);
                $agentVersionDTO->getVisibilityConfig()?->addUser($user);
            }
        }
        $agent = $this->delightfulAgentDomainService->getAgentById($agentVersionDTO->getAgentId());

        $isAddFriend = $agent->getAgentVersionId() === null;

        // 如果assistantstatus是disable则不可publish
        if ($agent->getStatus() === DelightfulAgentVersionStatus::ENTERPRISE_DISABLED->value) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_status_disabled_cannot_publish');
        }
        $delightfulAgentVersionEntity = $this->buildAgentVersion($agent, $agentVersionDTO);

        // publish最新connectstream
        $flowDataIsolation = $this->createFlowDataIsolation($authorization);
        if ($publishDelightfulFlowEntity && ! $publishDelightfulFlowEntity->shouldCreate()) {
            $publishDelightfulFlowEntity->setCode($agent->getFlowCode());
            $delightfulFlow = $this->delightfulFlowDomainService->getByCode($flowDataIsolation, $publishDelightfulFlowEntity->getCode());
            if (! $delightfulFlow) {
                ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.not_configured_workflow');
            }
            $delightfulFlowVersionEntity = new DelightfulFlowVersionEntity();
            $delightfulFlowVersionEntity->setName($delightfulAgentVersionEntity->getVersionNumber());
            $delightfulFlowVersionEntity->setFlowCode($delightfulFlow->getCode());
            $delightfulFlowVersionEntity->setDelightfulFlow($publishDelightfulFlowEntity);
            $delightfulFlowVersionEntity = $this->delightfulFlowVersionDomainService->publish($flowDataIsolation, $delightfulFlow, $delightfulFlowVersionEntity);
        } else {
            $delightfulFlowVersionEntity = $this->delightfulFlowVersionDomainService->getLastVersion($flowDataIsolation, $delightfulAgentVersionEntity->getFlowCode());
        }
        if (! $delightfulFlowVersionEntity) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.not_configured_workflow');
        }
        $delightfulAgentVersionEntity->setFlowVersion($delightfulFlowVersionEntity->getCode());

        // publishassistant
        $result = $this->delightfulAgentVersionDomainService->releaseAgentVersion($delightfulAgentVersionEntity);

        // 如果publish的是个人，那么不能操作第三方assistant
        if ($delightfulAgentVersionEntity->getReleaseScope() === DelightfulAgentReleaseStatus::PERSONAL_USE->value) {
            $thirdPlatformList = null;
        }
        // sync第三方assistant
        $this->delightfulBotThirdPlatformChatDomainService->syncBotThirdPlatformList($agent->getId(), $thirdPlatformList);

        // 首次publish添加为好友
        $result['is_add_friend'] = $isAddFriend;

        $delightfulAgentVersionEntity = $result['data'];
        $versionId = $delightfulAgentVersionEntity->getId();
        $agentId = $delightfulAgentVersionEntity->getRootId();
        $this->delightfulAgentDomainService->updateDefaultVersion($agentId, $versionId);
        $this->redisLocker->release($key, $userId);
        $this->updateWithInstructConversation($delightfulAgentVersionEntity);
        return $result;
    }

    // queryassistant的versionrecord

    /**
     * @param DelightfulUserAuthorization $authorization
     */
    public function getReleaseAgentVersions(Authenticatable $authorization, string $agentId): array
    {
        $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $agentId)->validate('r', $agentId);
        $releaseAgentVersions = $this->delightfulAgentVersionDomainService->getReleaseAgentVersions($agentId);

        if (empty($releaseAgentVersions)) {
            return $releaseAgentVersions;
        }
        $releaseAgentVersions = DelightfulAgentVersionFactory::toArrays($releaseAgentVersions);
        $creatorUids = array_unique(array_column($releaseAgentVersions, 'created_uid'));
        $dataIsolation = ContactDataIsolation::create($authorization->getOrganizationCode(), $authorization->getId());
        $creators = $this->delightfulUserDomainService->getUserByIds($creatorUids, $dataIsolation);
        $creatorMap = array_column($creators, null, 'user_id');

        $avatarPaths = array_unique(array_filter(array_column($releaseAgentVersions, 'agent_avatar')));
        $avatarLinks = [];
        if (! empty($avatarPaths)) {
            $fileLinks = $this->fileDomainService->getLinks($authorization->getOrganizationCode(), $avatarPaths);
            foreach ($fileLinks as $fileLink) {
                $avatarLinks[$fileLink->getPath()] = $fileLink->getUrl();
            }
        }

        foreach ($releaseAgentVersions as &$version) {
            $version['delightfulUserEntity'] = $creatorMap[$version['created_uid']] ?? null;
            $version['delightful_user_entity'] = $creatorMap[$version['created_uid']] ?? null;
            if (! empty($version['agent_avatar']) && isset($avatarLinks[$version['agent_avatar']])) {
                $version['agent_avatar'] = $avatarLinks[$version['agent_avatar']];
            }
        }

        return $releaseAgentVersions;
    }

    // getassistant最新version号

    /**
     * @param DelightfulUserAuthorization $authorization
     */
    public function getAgentMaxVersion(Authenticatable $authorization, string $agentId): string
    {
        $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $agentId)->validate('r', $agentId);
        return $this->delightfulAgentVersionDomainService->getAgentMaxVersion($agentId);
    }

    /**
     * @param DelightfulUserAuthorization $authorization
     */
    public function updateAgentStatus(Authenticatable $authorization, string $agentId, DelightfulAgentVersionStatus $status): void
    {
        $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $agentId)->validate('w', $agentId);

        // 修改assistant本身status
        $this->delightfulAgentDomainService->updateAgentStatus($agentId, $status->value);
    }

    /**
     * @param DelightfulUserAuthorization $authorization
     */
    public function updateAgentEnterpriseStatus(Authenticatable $authorization, string $agentId, int $status, string $userId): void
    {
        $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $agentId)->validate('w', $agentId);

        // 校验
        if ($status !== DelightfulAgentVersionStatus::ENTERPRISE_PUBLISHED->value && $status !== DelightfulAgentVersionStatus::ENTERPRISE_UNPUBLISHED->value) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.status_can_only_be_published_or_unpublished');
        }
        // getassistant
        $delightfulAgentEntity = $this->delightfulAgentDomainService->getAgentById($agentId);

        // 是否是自己的assistant
        if ($delightfulAgentEntity->getCreatedUid() !== $userId) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.illegal_operation');
        }

        if ($delightfulAgentEntity->getAgentVersionId() === null) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_not_published_to_enterprise_cannot_operate');
        }

        // getassistantversion
        $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getById($delightfulAgentEntity->getAgentVersionId());

        // 校验status是否allow被修改: APPROVAL_PASSED
        if ($delightfulAgentVersionEntity->getApprovalStatus() !== DelightfulAgentVersionStatus::APPROVAL_PASSED->value) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.approval_not_passed_cannot_modify_status');
        }

        // 修改version
        $this->delightfulAgentVersionDomainService->updateAgentEnterpriseStatus($delightfulAgentVersionEntity->getId(), $status);
    }

    /**
     * @param DelightfulUserAuthorization $authenticatable
     */
    public function getAgentDetail(string $agentId, Authenticatable $authenticatable): DelightfulAgentVO
    {
        $flowDataIsolation = new FlowDataIsolation($authenticatable->getOrganizationCode(), $authenticatable->getId());
        $userId = $authenticatable->getId();

        // 判断是否具有permission
        $agentOperation = $this->operationPermissionAppService->getOperationByResourceAndUser(
            new PermissionDataIsolation($authenticatable->getOrganizationCode(), $authenticatable->getId()),
            ResourceType::AgentCode,
            $agentId,
            $userId
        );
        $agentOperation->validate('read', $agentId);

        $delightfulAgentVO = new DelightfulAgentVO();
        // getassistantinfo
        $delightfulAgentEntity = $this->delightfulAgentDomainService->getById($agentId);

        $delightfulAgentEntity->setInstructs($this->processInstructionsImages($delightfulAgentEntity->getInstructs(), $authenticatable->getOrganizationCode()));

        $fileLink = $this->fileDomainService->getLink($delightfulAgentEntity->getOrganizationCode(), $delightfulAgentEntity->getAgentAvatar());
        if ($fileLink !== null) {
            $delightfulAgentEntity->setAgentAvatar($fileLink->getUrl());
        }
        $delightfulAgentEntity->setUserOperation($agentOperation->value);
        $delightfulAgentVO->setAgentEntity($delightfulAgentEntity);

        // according toversionidgetversioninfo
        $agentVersionId = $delightfulAgentEntity->getAgentVersionId();
        $delightfulFlowEntity = null;
        if (! empty($agentVersionId)) {
            $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getById($agentVersionId);

            $delightfulAgentVersionEntity->setInstructs($this->processInstructionsImages($delightfulAgentVersionEntity->getInstructs(), $authenticatable->getOrganizationCode()));

            $delightfulAgentVO->setAgentVersionEntity($delightfulAgentVersionEntity);
            $delightfulFlowVersionEntity = $this->delightfulFlowVersionDomainService->show($flowDataIsolation, $delightfulAgentVersionEntity->getFlowCode(), $delightfulAgentVersionEntity->getFlowVersion());
            $delightfulFlowEntity = $delightfulFlowVersionEntity->getDelightfulFlow();

            // 只有publish了才will有status
            $friendQueryDTO = new FriendQueryDTO();
            $friendQueryDTO->setAiCodes([$delightfulAgentVersionEntity->getFlowCode()]);

            // data隔离handle
            $friendDataIsolation = new ContactDataIsolation();
            $friendDataIsolation->setCurrentUserId($authenticatable->getId());
            $friendDataIsolation->setCurrentOrganizationCode($authenticatable->getOrganizationCode());

            // getuser代理的好友列表
            $userAgentFriends = $this->delightfulUserDomainService->getUserAgentFriendsList($friendQueryDTO, $friendDataIsolation);

            $delightfulAgentVO->setIsAdd(isset($userAgentFriends[$delightfulAgentVersionEntity->getFlowCode()]));
        } else {
            $delightfulFlowEntity = $this->delightfulFlowDomainService->getByCode($flowDataIsolation, $delightfulAgentEntity->getFlowCode());
        }

        $delightfulFlowEntity->setUserOperation($delightfulAgentEntity->getUserOperation());
        $delightfulAgentVO->setDelightfulFlowEntity($delightfulFlowEntity);
        $createdUid = $delightfulAgentEntity->getCreatedUid();
        $delightfulUserEntity = $this->delightfulUserDomainService->getUserById($createdUid);
        if ($delightfulUserEntity) {
            $userDto = new DelightfulUserEntity();
            $userDto->setAvatarUrl($delightfulUserEntity->getAvatarUrl());
            $userDto->setNickname($delightfulUserEntity->getNickname());
            $userDto->setUserId($delightfulUserEntity->getUserId());
            $delightfulAgentVO->setDelightfulUserEntity($userDto);
        }

        if ($delightfulAgentVO->getAgentVersionEntity()) {
            $this->setVisibilityConfigDetails($delightfulAgentVO->getAgentVersionEntity()->getVisibilityConfig(), $authenticatable);
        }
        return $delightfulAgentVO;
    }

    /**
     * @param DelightfulUserAuthorization $authenticatable
     */
    public function isUpdated(Authenticatable $authenticatable, string $agentId): bool
    {
        // checkcurrentassistant和version的assistant的info
        $delightfulAgentEntity = $this->delightfulAgentDomainService->getAgentById($agentId);

        $agentVersionId = $delightfulAgentEntity->getAgentVersionId();

        // 没publish过
        if (empty($agentVersionId)) {
            return false;
        }

        $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getById($agentVersionId);

        // 任意一项different都need修改
        if (
            $delightfulAgentEntity->getAgentAvatar() !== $delightfulAgentVersionEntity->getAgentAvatar()
            || $delightfulAgentEntity->getAgentDescription() !== $delightfulAgentVersionEntity->getAgentDescription()
            || $delightfulAgentEntity->getAgentName() !== $delightfulAgentVersionEntity->getAgentName()
        ) {
            return true;
        }

        $flowDataIsolation = new FlowDataIsolation($authenticatable->getOrganizationCode(), $authenticatable->getId());
        // 判断workflow
        $delightfulFlowVersionEntity = $this->delightfulFlowVersionDomainService->getLastVersion($flowDataIsolation, $delightfulAgentVersionEntity->getFlowCode());

        if ($delightfulFlowVersionEntity === null) {
            return false;
        }

        if ($delightfulFlowVersionEntity->getCode() !== $delightfulAgentVersionEntity->getFlowVersion()) {
            return true;
        }

        // 判断交互指令,如果不一致则needreturn true
        $oldInstruct = $delightfulAgentVersionEntity->getInstructs();
        $newInstruct = $delightfulAgentEntity->getInstructs();

        return $oldInstruct !== $newInstruct;
    }

    public function getDetailByUserId(string $userId): ?DelightfulAgentVersionEntity
    {
        $delightfulUserEntity = $this->delightfulUserDomainService->getUserById($userId);
        if ($delightfulUserEntity === null) {
            throw new InvalidArgumentException('user is empty');
        }
        $delightfulId = $delightfulUserEntity->getDelightfulId();
        $accountEntity = $this->delightfulAccountDomainService->getAccountInfoByDelightfulId($delightfulId);
        if ($accountEntity === null) {
            throw new InvalidArgumentException('account is empty');
        }
        $aiCode = $accountEntity->getAiCode();
        // according to aiCode(flowCode)
        return $this->delightfulAgentVersionDomainService->getAgentByFlowCode($aiCode);
    }

    /**
     * syncdefaultassistantconversation.
     */
    public function initDefaultAssistantConversation(DelightfulUserEntity $userEntity, ?array $defaultConversationAICodes = null): void
    {
        $dataIsolation = DataIsolation::create($userEntity->getOrganizationCode(), $userEntity->getUserId());
        $defaultConversationAICodes = $defaultConversationAICodes ?? $this->delightfulAgentDomainService->getDefaultConversationAICodes();
        foreach ($defaultConversationAICodes as $aiCode) {
            $aiUserEntity = $this->delightfulUserDomainService->getByAiCode($dataIsolation, $aiCode);
            $agentName = $aiUserEntity?->getNickname();
            // 判断conversation是否已经initialize，如果已initialize则skip
            if ($this->delightfulAgentDomainService->isDefaultAssistantConversationExist($userEntity->getUserId(), $aiCode)) {
                continue;
            }
            $this->logger->info("initializeassistantconversation，aiCode: {$aiCode}, name: {$agentName}");
            try {
                Db::transaction(function () use ($dataIsolation, $aiUserEntity, $aiCode, $userEntity) {
                    // 插入defaultconversationrecord
                    $this->delightfulAgentDomainService->insertDefaultAssistantConversation($userEntity->getUserId(), $aiCode);
                    // 添加好友，assistantdefault同意好友
                    $friendId = $aiUserEntity->getUserId();
                    $this->delightfulUserDomainService->addFriend($dataIsolation, $friendId);
                    // send添加好友控制message
                    $friendUserEntity = new DelightfulUserEntity();
                    $friendUserEntity->setUserId($friendId);
                    di(DelightfulUserContactAppService::class)->sendAddFriendControlMessage($dataIsolation, $friendUserEntity);
                });
                $this->logger->info("initializeassistantconversationsuccess，aiCode: {$aiCode}, name: {$agentName}");
            } catch (Throwable $e) {
                $errorMessage = $e->getMessage();
                $trace = $e->getTraceAsString();
                $this->logger->error("initializeassistantconversationfail，aiCode: {$aiCode}, name: {$agentName}\nerrorinfo: {$errorMessage}\n堆栈: {$trace} ");
            }
        }
    }

    public function saveInstruct(DelightfulUserAuthorization $authorization, string $agentId, array $instructs): array
    {
        // assistant是否有permission
        $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $agentId)->validate('w', $agentId);

        return $this->delightfulAgentDomainService->updateInstruct($authorization->getOrganizationCode(), $agentId, $instructs, $authorization->getId());
    }

    public function getInstruct(string $agentId): array
    {
        // getassistantinfo
        $delightfulAgentEntity = $this->delightfulAgentDomainService->getAgentById($agentId);
        if (empty($delightfulAgentEntity->getInstructs())) {
            return [];
        }
        return $delightfulAgentEntity->getInstructs();
    }

    /**
     * get可见性configuration中member和department的详细info.
     *
     * @param null|VisibilityConfig $visibilityConfig 可见性configuration
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     */
    public function setVisibilityConfigDetails(?VisibilityConfig $visibilityConfig, DelightfulUserAuthorization $authorization)
    {
        if (! $visibilityConfig) {
            return;
        }

        $dataIsolation = ContactDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId()
        );

        // handlememberinfo，移除currentuser
        $users = $visibilityConfig->getUsers();
        if (! empty($users)) {
            $currentUserId = $authorization->getId();
            // filter掉currentuser
            $filteredUsers = [];
            foreach ($users as $user) {
                if ($user->getId() !== $currentUserId) {
                    $filteredUsers[] = $user;
                }
            }
            if (! empty($filteredUsers)) {
                $userEntities = $this->delightfulUserDomainService->getUserByIds(array_column($filteredUsers, 'id'), $dataIsolation);
                $userMap = [];
                foreach ($userEntities as $userEntity) {
                    $userMap[$userEntity->getUserId()] = $userEntity;
                }

                // 先setting为null
                $visibilityConfig->setUsers([]);

                foreach ($filteredUsers as $user) {
                    $userEntity = $userMap[$user->getId()];
                    $user->setNickname($userEntity->getNickname());
                    $user->setAvatar($userEntity->getAvatarUrl());
                    $visibilityConfig->addUser($user);
                }
            } else {
                $visibilityConfig->setUsers([]);
            }
        }

        $departments = $visibilityConfig->getDepartments();
        if (! empty($departments)) {
            $departmentIds = array_column($departments, 'id');
            $departmentEntities = $this->delightfulDepartmentDomainService->getDepartmentByIds($dataIsolation, $departmentIds);
            $departmentMap = [];
            foreach ($departmentEntities as $department) {
                $departmentMap[$department->getDepartmentId()] = $department;
            }
            foreach ($departments as $department) {
                $department->setName($departmentMap[$department->getId()]->getName());
            }
        }
    }

    public function initAgents(DelightfulUserAuthorization $authenticatable): void
    {
        $orgCode = $authenticatable->getOrganizationCode();
        $userId = $authenticatable->getId();
        $lockKey = 'agent:init_agents:' . $orgCode;

        // 尝试getlock，timeout时间setting为60秒
        if (! $this->redisLocker->mutexLock($lockKey, $userId, 60)) {
            $this->logger->warning(sprintf('get initAgents lockfail, orgCode: %s, userId: %s', $orgCode, $userId));
            // getlockfail，can选择直接return或throwexception，这里选择直接return避免阻塞
            return;
        }

        try {
            $this->logger->info(sprintf('get initAgents locksuccess, startexecuteinitialize, orgCode: %s, userId: %s', $orgCode, $userId));
            $this->initChatAgent($authenticatable);
            $this->initImageGenerationAgent($authenticatable);
            $this->initDocAnalysisAgent($authenticatable);
        } finally {
            // ensurelock被释放
            $this->redisLocker->release($lockKey, $userId);
            $this->logger->info(sprintf('释放 initAgents lock, orgCode: %s, userId: %s', $orgCode, $userId));
        }
    }

    /**
     * 为新register的organizationcreate人initialize一个Chat.
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     */
    #[Transactional]
    public function initChatAgent(Authenticatable $authorization): void
    {
        $service = di(DelightfulFlowAIModelAppService::class);
        $models = $service->getEnabled($authorization);
        $modelName = '';
        if (! empty($models['list'])) {
            $modelName = $models['list'][0]->getModelName();
        }

        $loadPresetConfig = $this->loadPresetConfig('chat', ['modelName' => $modelName]);
        // 准备基本configuration
        $config = [
            'agent_name' => '麦吉assistant',
            'agent_description' => '我will回答你一切',
            'agent_avatar' => $this->fileDomainService->getDefaultIconPaths()['bot'] ?? '',
            'flow' => $loadPresetConfig['flow'],
        ];

        // call通用initializemethod
        $this->initAgentFromConfig($authorization, $config);
    }

    /**
     * 为新register的organizationcreate人initialize一个文生图Agent.
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     */
    #[Transactional]
    public function initImageGenerationAgent(Authenticatable $authorization): void
    {
        $service = di(DelightfulFlowAIModelAppService::class);
        $models = $service->getEnabled($authorization);
        $modelName = '';
        if (! empty($models['list'])) {
            $modelName = $models['list'][0]->getModelName();
        }

        $loadPresetConfig = $this->loadPresetConfig('generate_image', ['modelName' => $modelName]);
        // 准备基本configuration
        $config = [
            'agent_name' => '文生图助手',
            'agent_description' => '一个强大的AI文本generate图像助手，canaccording to您的descriptioncreate精美图像。',
            'agent_avatar' => $this->fileDomainService->getDefaultIconPaths()['bot'] ?? '',
            'flow' => $loadPresetConfig['flow'],
            'instruct' => $loadPresetConfig['instructs'],
        ];

        // call通用initializemethod
        $this->initAgentFromConfig($authorization, $config);
    }

    /**
     * 为新register的organizationcreate人initialize一个documentparseAgent.
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     */
    #[Transactional]
    public function initDocAnalysisAgent(Authenticatable $authorization): void
    {
        $service = di(DelightfulFlowAIModelAppService::class);
        $models = $service->getEnabled($authorization);
        $modelName = '';
        if (! empty($models['list'])) {
            $modelName = $models['list'][0]->getModelName();
        }

        // 准备基本configuration
        $config = [
            'agent_name' => 'documentparse助手',
            'agent_description' => 'documentparse助手',
            'agent_avatar' => $this->fileDomainService->getDefaultIconPaths()['bot'] ?? '',
            'flow' => $this->loadPresetConfig('document', ['modelName' => $modelName])['flow'],
        ];

        // call通用initializemethod
        $this->initAgentFromConfig($authorization, $config);
    }

    /**
     * 从configurationfileinitializecustomizeAgent.
     *
     * @param $authorization DelightfulUserAuthorization userauthorizationinfo
     * @param array $config containAgentconfiguration的array
     * @return DelightfulAgentEntity create的机器人实体
     * @throws Throwable 当configurationinvalid或initializefail时throwexception
     */
    #[Transactional]
    public function initAgentFromConfig(DelightfulUserAuthorization $authorization, array $config): DelightfulAgentEntity
    {
        // create机器人
        $delightfulAgentDTO = new DelightfulAgentDTO();
        $delightfulAgentDTO->setAgentName($config['agent_name']);
        $delightfulAgentDTO->setAgentDescription($config['agent_description'] ?? '');
        $delightfulAgentDTO->setAgentAvatar($config['agent_avatar'] ?? $this->fileDomainService->getDefaultIconPaths()['bot'] ?? '');
        $delightfulAgentDTO->setCurrentUserId($authorization->getId());
        $delightfulAgentDTO->setCurrentOrganizationCode($authorization->getOrganizationCode());

        $delightfulAgentEntity = $this->saveAgent($authorization, $delightfulAgentDTO);
        if (isset($config['instruct'])) {
            $this->delightfulAgentDomainService->updateInstruct($authorization->getOrganizationCode(), $delightfulAgentEntity->getId(), $config['instruct'], $authorization->getId());
        }
        // createFlow
        $delightfulFLowDTO = new DelightfulFlowDTO($config['flow']);
        $delightfulFlowAssembler = new DelightfulFlowAssembler();
        $delightfulFlowDO = $delightfulFlowAssembler::createDelightfulFlowDO($delightfulFLowDTO);

        // createversion
        $agentVersionDTO = new DelightfulAgentVersionDTO();
        $agentVersionDTO->setAgentId($delightfulAgentEntity->getId());
        $agentVersionDTO->setVersionNumber('0.0.1');
        $agentVersionDTO->setVersionDescription('initialversion');
        $agentVersionDTO->setReleaseScope(DelightfulAgentReleaseStatus::PUBLISHED_TO_ENTERPRISE->value);
        $agentVersionDTO->setCreatedUid($authorization->getId());

        $this->releaseAgentVersion($authorization, $agentVersionDTO, $delightfulFlowDO);

        return $delightfulAgentEntity;
    }

    /**
     * readJSONfile并替换templatevariable.
     *
     * @param string $filepath JSONfilepath
     * @param array $variables 替换variable ['modelName' => 'gpt-4', 'otherVar' => '其他value']
     * @return null|array parse后的array或fail时returnnull
     */
    public function readJsonToArray(string $filepath, array $variables = []): ?array
    {
        if (! file_exists($filepath)) {
            return null;
        }

        $jsonContent = file_get_contents($filepath);
        if ($jsonContent === false) {
            return null;
        }

        // 替换templatevariable
        if (! empty($variables)) {
            foreach ($variables as $key => $value) {
                $jsonContent = str_replace("{{{$key}}}", $value, $jsonContent);
            }
        }

        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * getenable的assistantversion列表.
     * optimize：直接在领域service层进行JOINquery，避免传入过多ID.
     */
    private function getEnabledAgentVersions(string $organizationCode, int $page, int $pageSize, string $agentName): array
    {
        // 直接call领域serviceget该organization下enable的assistantversion，避免先get所有ID再query
        return $this->delightfulAgentVersionDomainService->getEnabledAgentsByOrganization($organizationCode, $page, $pageSize, $agentName);
    }

    /**
     * according to可见性configurationfilterassistant.
     * @param array $agentVersions assistantversion列表
     * @return array filter后的assistantversion列表
     */
    private function filterVisibleAgents(array $agentVersions, string $currentUserId, string $organizationCode): array
    {
        $visibleAgentVersions = [];

        // getuserdepartmentinfo
        $dataIsolation = ContactDataIsolation::create($organizationCode, $currentUserId);
        $departmentUserEntities = $this->delightfulDepartmentUserDomainService->getDepartmentUsersByUserIds([$currentUserId], $dataIsolation);
        $directDepartmentIds = [];

        // getuser直接所属的departmentID
        foreach ($departmentUserEntities as $entity) {
            $directDepartmentIds[] = $entity->getDepartmentId();
        }

        if (empty($directDepartmentIds)) {
            $userDepartmentIds = [];
        } else {
            // 批量get所有相关departmentinfo
            $departments = $this->delightfulDepartmentDomainService->getDepartmentByIds($dataIsolation, $directDepartmentIds);
            $departmentsMap = [];
            foreach ($departments as $department) {
                $departmentsMap[$department->getDepartmentId()] = $department;
            }

            // handledepartment层级关系
            $allDepartmentIds = [];
            foreach ($directDepartmentIds as $departmentId) {
                if (isset($departmentsMap[$departmentId])) {
                    $department = $departmentsMap[$departmentId];
                    $pathStr = $department->getPath();
                    // pathformat为 "-1/parent_id/department_id"，去除前导的-1
                    $allDepartmentIds[] = array_filter(explode('/', trim($pathStr, '/')), static function ($id) {
                        return $id !== '-1';
                    });
                }
                $allDepartmentIds[] = [$departmentId];
            }
            $allDepartmentIds = array_merge(...$allDepartmentIds);
            // 去重，ensure所有departmentID唯一
            $userDepartmentIds = array_unique($allDepartmentIds);
        }

        foreach ($agentVersions as $agentVersion) {
            $visibilityConfig = $agentVersion->getVisibilityConfig();

            // 全部可见或无可见性configuration
            if ($visibilityConfig === null || $visibilityConfig->getVisibilityType() === VisibilityType::All->value) {
                $visibleAgentVersions[] = $agentVersion;
                continue;
            }

            // 特定可见 - 此处无需再次checkvisibilityType，因为前面已排除了null和Alltype
            // 剩下的只可能是SPECIFICtype
            if ($this->isUserVisible($visibilityConfig, $currentUserId, $userDepartmentIds)) {
                $visibleAgentVersions[] = $agentVersion;
            }
        }

        return $visibleAgentVersions;
    }

    /**
     * 判断user是否可见
     */
    private function isUserVisible(VisibilityConfig $visibilityConfig, string $currentUserId, array $userDepartmentIds): bool
    {
        // checkuser是否在可见user列表中
        foreach ($visibilityConfig->getUsers() as $visibleUser) {
            if ($visibleUser->getId() === $currentUserId) {
                return true;
            }
        }

        // checkuserdepartment是否在可见department列表中
        foreach ($visibilityConfig->getDepartments() as $visibleDepartment) {
            if (in_array($visibleDepartment->getId(), $userDepartmentIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * getassistanttotal.
     * optimize：useJOINquery避免传入大量ID.
     * optimize：useJOINquery避免传入大量ID.
     */
    private function getTotalAgentsCount(string $organizationCode, string $agentName): int
    {
        return $this->delightfulAgentVersionDomainService->getEnabledAgentsByOrganizationCount($organizationCode, $agentName);
    }

    /**
     * handlecreate者info.
     */
    private function enrichCreatorInfo(array &$agentVersions): void
    {
        $agentIds = array_column($agentVersions, 'agent_id');
        $agents = $this->delightfulAgentDomainService->getAgentByIds($agentIds);
        $users = $this->delightfulUserDomainService->getUserByIdsWithoutOrganization(array_column($agents, 'created_uid'));
        $userMap = array_column($users, null, 'user_id');

        foreach ($agentVersions as &$agent) {
            $agent['created_info'] = $userMap[$agent['created_uid']] ?? null;
        }
    }

    /**
     * handleassistantavatar和好友status
     */
    private function enrichAgentAvatarAndFriendStatus(array &$agentVersions, DelightfulUserAuthorization $authorization): void
    {
        // 批量收集needgetlink的filepath和flow_code
        $avatarPaths = [];
        $flowCodes = [];
        foreach ($agentVersions as $agent) {
            if (! empty($agent['agent_avatar'])) {
                $avatarPaths[] = $agent['agent_avatar'];
            }
            $flowCodes[] = $agent['flow_code'];
        }

        // 批量getavatarlink，避免循环callgetLink
        $fileLinks = [];
        if (! empty($avatarPaths)) {
            $fileLinks = $this->fileDomainService->getLinks($authorization->getOrganizationCode(), array_unique($avatarPaths));
        }

        // settingavatarURL
        foreach ($agentVersions as &$agent) {
            $avatarUrl = '';
            if (! empty($agent['agent_avatar']) && isset($fileLinks[$agent['agent_avatar']])) {
                $avatarUrl = $fileLinks[$agent['agent_avatar']]?->getUrl() ?? '';
            }
            $agent['agent_avatar'] = $avatarUrl;
            $agent['robot_avatar'] = $avatarUrl;
        }
        unset($agent);
        $friendQueryDTO = new FriendQueryDTO();
        $friendQueryDTO->setAiCodes($flowCodes);

        $friendDataIsolation = new ContactDataIsolation();
        $friendDataIsolation->setCurrentUserId($authorization->getId());
        $friendDataIsolation->setCurrentOrganizationCode($authorization->getOrganizationCode());

        $userAgentFriends = $this->delightfulUserDomainService->getUserAgentFriendsList($friendQueryDTO, $friendDataIsolation);

        foreach ($agentVersions as &$agent) {
            $agent['is_add'] = isset($userAgentFriends[$agent['flow_code']]);
            if ($agent['is_add']) {
                $agent['user_id'] = $userAgentFriends[$agent['flow_code']]->getFriendId();
            }
        }
    }

    /**
     * getnull的paginationresult.
     */
    private function getEmptyPageResult(int $page, int $pageSize): array
    {
        return [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => 0,
            'list' => [],
        ];
    }

    /**
     * handle指令中的imagepath.
     * @param null|array $instructs 指令array
     * @param string $organizationCode organizationcode
     * @return array handle后的指令array
     */
    private function processInstructionsImages(?array $instructs, string $organizationCode): array
    {
        if (empty($instructs)) {
            return [];
        }

        // 收集所有needhandle的imagepath
        $imagePaths = [];
        foreach ($instructs as $instruct) {
            $hasValidItems = isset($instruct['items']) && is_array($instruct['items']);
            if (! $hasValidItems) {
                continue;
            }

            foreach ($instruct['items'] as $item) {
                // handle普通指令的image
                $explanation = $item['instruction_explanation'] ?? [];
                $hasValidImage = is_array($explanation) && ! empty($explanation['image']);
                if ($hasValidImage) {
                    $imagePaths[] = $explanation['image'];
                }

                // handleoptiontype指令的image
                $values = $item['values'] ?? [];
                $hasValidValues = is_array($values);
                if (! $hasValidValues) {
                    continue;
                }

                foreach ($values as $value) {
                    $valueExplanation = $value['instruction_explanation'] ?? [];
                    $hasValidValueImage = is_array($valueExplanation) && ! empty($valueExplanation['image']);
                    if ($hasValidValueImage) {
                        $imagePaths[] = $valueExplanation['image'];
                    }
                }
            }
        }

        if (empty($imagePaths)) {
            return $instructs;
        }

        // get所有image的link
        $fileLinks = $this->fileDomainService->getLinks($organizationCode, array_unique($imagePaths));
        $imageUrlMap = [];
        foreach ($fileLinks as $fileLink) {
            $imageUrlMap[$fileLink->getPath()] = $fileLink->getUrl();
        }

        // handle指令array中的imagepath
        foreach ($instructs as &$instruct) {
            $hasValidItems = isset($instruct['items']) && is_array($instruct['items']);
            if (! $hasValidItems) {
                continue;
            }

            foreach ($instruct['items'] as &$item) {
                // handle普通指令的image
                $explanation = &$item['instruction_explanation'];
                $hasValidImagePath = is_array($explanation) && isset($explanation['image']);
                if ($hasValidImagePath) {
                    $explanation['image'] = $imageUrlMap[$explanation['image']] ?? '';
                }

                // handleoptiontype指令的image
                $values = &$item['values'];
                $hasValidValues = is_array($values);
                if (! $hasValidValues) {
                    continue;
                }

                foreach ($values as &$value) {
                    $valueExplanation = &$value['instruction_explanation'];
                    $hasValidValuePath = is_array($valueExplanation) && isset($valueExplanation['image']);
                    if ($hasValidValuePath) {
                        $valueExplanation['image'] = $imageUrlMap[$valueExplanation['image']] ?? '';
                    }
                }
                unset($value);
            }
            unset($item);
        }
        unset($instruct);

        return $instructs;
    }

    private function updateWithInstructConversation(DelightfulAgentVersionEntity $delightfulAgentVersionEntity): void
    {
        AsyncEventUtil::dispatch(new DelightfulAgentInstructEvent($delightfulAgentVersionEntity));
    }

    private function buildAgentVersion(DelightfulAgentEntity $agentEntity, DelightfulAgentVersionDTO $agentVersionDTO): DelightfulAgentVersionEntity
    {
        $delightfulAgentVersionEntity = new DelightfulAgentVersionEntity();

        $delightfulAgentVersionEntity->setFlowCode($agentEntity->getFlowCode());
        $delightfulAgentVersionEntity->setAgentId($agentEntity->getId());
        $delightfulAgentVersionEntity->setAgentName($agentEntity->getAgentName());
        $delightfulAgentVersionEntity->setAgentAvatar($agentEntity->getAgentAvatar());
        $delightfulAgentVersionEntity->setAgentDescription($agentEntity->getAgentDescription());
        $delightfulAgentVersionEntity->setOrganizationCode($agentEntity->getOrganizationCode());
        $delightfulAgentVersionEntity->setCreatedUid($agentVersionDTO->getCreatedUid());

        $delightfulAgentVersionEntity->setVersionDescription($agentVersionDTO->getVersionDescription());
        $delightfulAgentVersionEntity->setReleaseScope($agentVersionDTO->getReleaseScope());
        $delightfulAgentVersionEntity->setVersionNumber($agentVersionDTO->getVersionNumber());

        $delightfulAgentVersionEntity->setInstructs($agentEntity->getInstructs());
        $delightfulAgentVersionEntity->setStartPage($agentEntity->getStartPage());
        $delightfulAgentVersionEntity->setVisibilityConfig($agentVersionDTO->getVisibilityConfig());

        return $delightfulAgentVersionEntity;
    }

    /**
     * loadpresetAgentconfiguration.
     *
     * @param string $presetName presetname
     * @param array $variables 替换variable
     * @return array configurationarray
     */
    private function loadPresetConfig(string $presetName, array $variables = []): array
    {
        $presetPath = BASE_PATH . "/storage/agent/{$presetName}.txt";
        $config = $this->readJsonToArray($presetPath, $variables);

        if (empty($config)) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, "无法loadpresetconfiguration: {$presetName}");
        }

        return $config;
    }

    /**
     * 分离官方organization和userorganization的assistant.
     *
     * @param array $agentEntities assistant实体array
     * @return array return [officialAgents, userOrgAgents]
     */
    private function separateOfficialAndUserAgents(array $agentEntities): array
    {
        $officialAgents = [];
        $userOrgAgents = [];

        foreach ($agentEntities as $agent) {
            if (OfficialOrganizationUtil::isOfficialOrganization($agent->getOrganizationCode())) {
                $officialAgents[] = $agent;
            } else {
                $userOrgAgents[] = $agent;
            }
        }

        return [$officialAgents, $userOrgAgents];
    }

    /**
     * getassistantconversationmapping.
     *
     * @param DelightfulAgentEntity[] $agentEntities assistant实体array
     * @param DelightfulUserAuthorization $authorization userauthorizationobject
     * @return array return [flowCodeToUserIdMap, conversationMap]
     */
    private function getAgentConversationMapping(array $agentEntities, DelightfulUserAuthorization $authorization): array
    {
        // 3. 分离官方和非官方assistant
        [$officialAgents, $userOrgAgents] = $this->separateOfficialAndUserAgents($agentEntities);

        // 提取 flow_code
        $officialFlowCodes = array_map(static fn ($agent) => $agent->getFlowCode(), $officialAgents);
        $userOrgFlowCodes = array_map(static fn ($agent) => $agent->getFlowCode(), $userOrgAgents);

        // 4. 分别query官方和userorganization的assistantuserID
        $flowCodeToUserIdMap = [];

        // 4.1 query官方assistant的userID
        if (! empty($officialFlowCodes) && OfficialOrganizationUtil::hasOfficialOrganization()) {
            $officialDataIsolation = new ContactDataIsolation();
            $officialDataIsolation->setCurrentUserId($authorization->getId());
            $officialDataIsolation->setCurrentOrganizationCode(OfficialOrganizationUtil::getOfficialOrganizationCode());

            $officialUserIdMap = $this->delightfulUserDomainService->getByAiCodes($officialDataIsolation, $officialFlowCodes);
            $flowCodeToUserIdMap = array_merge($flowCodeToUserIdMap, $officialUserIdMap);
        }

        // 4.2 queryuserorganizationassistant的userID
        if (! empty($userOrgFlowCodes)) {
            $userDataIsolation = new ContactDataIsolation();
            $userDataIsolation->setCurrentUserId($authorization->getId());
            $userDataIsolation->setCurrentOrganizationCode($authorization->getOrganizationCode());

            $userOrgUserIdMap = $this->delightfulUserDomainService->getByAiCodes($userDataIsolation, $userOrgFlowCodes);
            $flowCodeToUserIdMap = array_merge($flowCodeToUserIdMap, $userOrgUserIdMap);
        }

        // 5. 收集所有assistant的userID
        $agentUserIds = array_values($flowCodeToUserIdMap);

        // 6. queryuser与这些assistant的conversationID
        $conversationMap = [];
        if (! empty($agentUserIds)) {
            $conversationMap = $this->delightfulConversationDomainService->getConversationIdMappingByReceiveIds(
                $authorization->getId(),
                $agentUserIds
            );
        }

        return [$flowCodeToUserIdMap, $conversationMap];
    }

    /**
     * 批量getassistantavatarURL.
     *
     * @param DelightfulAgentEntity[] $agentEntities assistant实体array
     * @param DelightfulUserAuthorization $authorization userauthorizationobject
     * @return array avatarpath到URL的mapping
     */
    private function batchGetAvatarUrls(array $agentEntities, DelightfulUserAuthorization $authorization): array
    {
        // 分离官方organization和userorganization的assistant
        [$officialAgents, $userOrgAgents] = $this->separateOfficialAndUserAgents($agentEntities);

        $avatarUrlMap = [];

        // 批量get官方organization的avatarlink
        if (! empty($officialAgents) && OfficialOrganizationUtil::hasOfficialOrganization()) {
            $officialAvatars = array_filter(array_map(static fn ($agent) => $agent->getAgentAvatar(), $officialAgents));
            if (! empty($officialAvatars)) {
                $officialFileLinks = $this->fileDomainService->getLinks(
                    OfficialOrganizationUtil::getOfficialOrganizationCode(),
                    array_unique($officialAvatars)
                );

                foreach ($officialFileLinks as $fileLink) {
                    $avatarUrlMap[$fileLink->getPath()] = $fileLink->getUrl();
                }
            }
        }

        // 批量getuserorganization的avatarlink
        if (! empty($userOrgAgents)) {
            $userOrgAvatars = array_filter(array_map(static fn ($agent) => $agent->getAgentAvatar(), $userOrgAgents));
            if (! empty($userOrgAvatars)) {
                $userOrgFileLinks = $this->fileDomainService->getLinks(
                    $authorization->getOrganizationCode(),
                    array_unique($userOrgAvatars)
                );

                foreach ($userOrgFileLinks as $fileLink) {
                    $avatarUrlMap[$fileLink->getPath()] = $fileLink->getUrl();
                }
            }
        }

        return $avatarUrlMap;
    }

    /**
     * 将 DelightfulAgentEntity convert为 DelightfulAgentVersionEntity.
     * 用于handle私人assistant没有publishversion的情况.
     *
     * @param DelightfulAgentEntity $agentEntity assistant实体
     * @return DelightfulAgentVersionEntity assistantversion实体
     */
    private function convertAgentToAgentVersion(DelightfulAgentEntity $agentEntity): DelightfulAgentVersionEntity
    {
        $delightfulAgentVersionEntity = new DelightfulAgentVersionEntity();

        // setting基本info
        $delightfulAgentVersionEntity->setFlowCode($agentEntity->getFlowCode());
        $delightfulAgentVersionEntity->setAgentId($agentEntity->getId());
        $delightfulAgentVersionEntity->setAgentName($agentEntity->getAgentName());
        $delightfulAgentVersionEntity->setAgentAvatar($agentEntity->getAgentAvatar());
        $delightfulAgentVersionEntity->setAgentDescription($agentEntity->getAgentDescription());
        $delightfulAgentVersionEntity->setOrganizationCode($agentEntity->getOrganizationCode());
        $delightfulAgentVersionEntity->setCreatedUid($agentEntity->getCreatedUid());
        $delightfulAgentVersionEntity->setInstructs($agentEntity->getInstructs());
        $delightfulAgentVersionEntity->setStartPage($agentEntity->getStartPage());

        // version相关info设为null，表示没有publishversion
        $delightfulAgentVersionEntity->setVersionNumber(null);
        $delightfulAgentVersionEntity->setVersionDescription(null);

        // setting时间info
        $delightfulAgentVersionEntity->setCreatedAt($agentEntity->getCreatedAt());
        $delightfulAgentVersionEntity->setUpdatedUid($agentEntity->getUpdatedUid());
        $delightfulAgentVersionEntity->setUpdatedAt($agentEntity->getUpdatedAt());

        return $delightfulAgentVersionEntity;
    }
}

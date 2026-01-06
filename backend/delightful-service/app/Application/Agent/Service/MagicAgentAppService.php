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

        // 查询当前具有权限的
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

    // 创建/修改助理
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
            // 修改得检查权限
            $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $delightfulAgentEntity->getId())->validate('edit', $delightfulAgentEntity->getId());
        }

        $delightfulAgentEntity = $this->delightfulAgentDomainService->saveAgent($delightfulAgentEntity);
        $fileLink = $this->fileDomainService->getLink($delightfulAgentDTO->getCurrentOrganizationCode(), $delightfulAgentEntity->getAgentAvatar());
        if ($fileLink) {
            $delightfulAgentEntity->setAgentAvatar($fileLink->getUrl());
        }

        return $delightfulAgentEntity;
    }

    // 删除助理

    /**
     * @param DelightfulUserAuthorization $authorization
     */
    public function deleteAgentById(Authenticatable $authorization, string $id): bool
    {
        $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $id)->validate('d', $id);
        return $this->delightfulAgentDomainService->deleteAgentById($id, $authorization->getOrganizationCode());
    }

    // 获取指定用户的助理
    #[Deprecated]
    public function getAgentsByUserIdPage(string $userId, int $page, int $pageSize, string $agentName, DelightfulAgentQueryStatus $queryStatus): array
    {
        $query = new DelightfulAgentQuery();
        $query->setCreatedUid($userId);
        $query->setAgentName($agentName);
        $query->setOrder(['id' => 'desc']);

        // 设置版本状态过滤
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
            // 首先尝试作为 agent_version_id 从已发布版本中获取
            $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getAgentById($agentVersionId);
        } catch (Throwable $e) {
            // 如果失败，从 delightful_bots 表获取原始助理数据，并转换为 DelightfulAgentVersionEntity（版本号为 null）
            try {
                $delightfulAgentEntity = $this->delightfulAgentDomainService->getById($agentVersionId);
                $delightfulAgentVersionEntity = $this->convertAgentToAgentVersion($delightfulAgentEntity);
            } catch (Throwable) {
                // 如果都失败，抛出原始异常
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

    // 获取发布版本的助理,对于用户的
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
        // 根据工作流id获取工作流信息
        $flowDataIsolation = new FlowDataIsolation($authorization->getOrganizationCode(), $authorization->getId());
        $delightfulFlowVersionEntity = $this->delightfulFlowVersionDomainService->show($flowDataIsolation, $delightfulAgentVersionEntity->getFlowCode(), $delightfulAgentVersionEntity->getFlowVersion());
        $delightfulFlowEntity = $delightfulFlowVersionEntity->getDelightfulFlow();

        $delightfulFlowEntity->setUserOperation($delightfulAgentEntity->getUserOperation());
        $delightfulAgentVO->setDelightfulFlowEntity($delightfulFlowEntity);
        $friendQueryDTO = new FriendQueryDTO();
        $friendQueryDTO->setAiCodes([$delightfulAgentVersionEntity->getFlowCode()]);

        // 数据隔离处理
        $friendDataIsolation = new ContactDataIsolation();
        $friendDataIsolation->setCurrentUserId($authorization->getId());
        $friendDataIsolation->setCurrentOrganizationCode($organizationCode);

        // 获取用户代理的好友列表
        $userAgentFriends = $this->delightfulUserDomainService->getUserAgentFriendsList($friendQueryDTO, $friendDataIsolation);

        $delightfulAgentVO->setIsAdd(isset($userAgentFriends[$delightfulAgentVersionEntity->getFlowCode()]));

        $visibilityConfig = $delightfulAgentVersionEntity->getVisibilityConfig();

        $this->setVisibilityConfigDetails($visibilityConfig, $authorization);
        return $delightfulAgentVO;
    }

    /**
     * 获取企业内部的助理.
     * @param DelightfulUserAuthorization $authorization
     */
    public function getAgentsByOrganizationPage(Authenticatable $authorization, int $page, int $pageSize, string $agentName): array
    {
        if (! $authorization instanceof DelightfulUserAuthorization) {
            return [];
        }

        $organizationCode = $authorization->getOrganizationCode();
        $currentUserId = $authorization->getId();

        // 获取启用的助理版本列表
        $agentVersions = $this->getEnabledAgentVersions($organizationCode, $page, $pageSize, $agentName);
        if (empty($agentVersions)) {
            return $this->getEmptyPageResult($page, $pageSize);
        }

        // 根据可见性配置过滤助理
        $visibleAgentVersions = $this->filterVisibleAgents($agentVersions, $currentUserId, $organizationCode);
        if (empty($visibleAgentVersions)) {
            return $this->getEmptyPageResult($page, $pageSize);
        }

        // 转换为数组格式
        $agentVersions = DelightfulAgentVersionFactory::toArrays($visibleAgentVersions);

        // 获取助理总数
        $totalAgentsCount = $this->getTotalAgentsCount($organizationCode, $agentName);

        // 处理创建者信息
        $this->enrichCreatorInfo($agentVersions);

        // 处理头像和好友状态
        $this->enrichAgentAvatarAndFriendStatus($agentVersions, $authorization);

        return [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $totalAgentsCount,
            'list' => $agentVersions,
        ];
    }

    /**
     * 获取聊天模式可用助理列表（全量数据，不分页）.
     * @param Authenticatable $authorization 用户授权
     * @param DelightfulAgentQuery $query 查询条件
     * @return array 助理列表及会话ID
     */
    public function getChatModeAvailableAgents(Authenticatable $authorization, DelightfulAgentQuery $query): array
    {
        if (! $authorization instanceof DelightfulUserAuthorization) {
            return ['total' => 0, 'list' => []];
        }

        // 1. 使用 queriesAvailable 查询官方+用户组织的助理（全量数据）
        $fullQuery = clone $query;
        $fullPage = Page::createNoPage(); // 获取全量数据
        $agentAppService = di(AgentAppService::class);
        $fullData = $agentAppService->queriesAvailable($authorization, $fullQuery, $fullPage, true);

        if (empty($fullData['list'])) {
            return ['total' => 0, 'list' => []];
        }

        // 获取全量助理实体
        $totalCount = $fullData['total'];
        /** @var DelightfulAgentEntity[] $agentEntities */
        $agentEntities = $fullData['list'];

        // 获取助理会话映射
        [$flowCodeToUserIdMap, $conversationMap] = $this->getAgentConversationMapping($agentEntities, $authorization);

        // 批量获取头像链接
        $avatarUrlMap = $this->batchGetAvatarUrls($agentEntities, $authorization);

        // 转换为数组格式并添加会话ID
        $result = [];
        foreach ($agentEntities as $agent) {
            $agentData = $agent->toArray();

            // 添加 agent_id 字段，值同 id
            $agentData['agent_id'] = $agentData['id'];

            // 添加是否为官方组织标识
            $agentData['is_office'] = OfficialOrganizationUtil::isOfficialOrganization($agent->getOrganizationCode());

            // 处理头像链接
            $agentData['agent_avatar'] = $avatarUrlMap[$agent->getAgentAvatar()] ?? null;
            $agentData['robot_avatar'] = $agentData['agent_avatar'];

            // 添加助理用户ID和会话ID
            $flowCode = $agent->getFlowCode();
            if (isset($flowCodeToUserIdMap[$flowCode])) {
                $userId = $flowCodeToUserIdMap[$flowCode];
                $agentData['user_id'] = $userId;

                // 添加会话ID（如果存在）
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

    // 获取应用市场助理
    public function getAgentsFromMarketplacePage(int $page, int $pageSize): array
    {
        // 查出启用的助理
        $agents = $this->delightfulAgentDomainService->getEnabledAgents();
        // 使用 array_column 提取 agent_version_id
        $agentIds = array_column($agents, 'agent_version_id');
        $agentsFromMarketplace = $this->delightfulAgentVersionDomainService->getAgentsFromMarketplace($agentIds, $page, $pageSize);
        $agentsFromMarketplaceCount = $this->delightfulAgentVersionDomainService->getAgentsFromMarketplaceCount($agentIds);
        return ['page' => $page, 'page_size' => $pageSize, 'total' => $agentsFromMarketplaceCount, 'list' => $agentsFromMarketplace];
    }

    // 发布助理版本

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

        // 只有发布到企业才把自己给添加
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

        // 如果助理状态是禁用则不可发布
        if ($agent->getStatus() === DelightfulAgentVersionStatus::ENTERPRISE_DISABLED->value) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_status_disabled_cannot_publish');
        }
        $delightfulAgentVersionEntity = $this->buildAgentVersion($agent, $agentVersionDTO);

        // 发布最新连接流
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

        // 发布助理
        $result = $this->delightfulAgentVersionDomainService->releaseAgentVersion($delightfulAgentVersionEntity);

        // 如果发布的是个人，那么不能操作第三方助理
        if ($delightfulAgentVersionEntity->getReleaseScope() === DelightfulAgentReleaseStatus::PERSONAL_USE->value) {
            $thirdPlatformList = null;
        }
        // 同步第三方助理
        $this->delightfulBotThirdPlatformChatDomainService->syncBotThirdPlatformList($agent->getId(), $thirdPlatformList);

        // 首次发布添加为好友
        $result['is_add_friend'] = $isAddFriend;

        $delightfulAgentVersionEntity = $result['data'];
        $versionId = $delightfulAgentVersionEntity->getId();
        $agentId = $delightfulAgentVersionEntity->getRootId();
        $this->delightfulAgentDomainService->updateDefaultVersion($agentId, $versionId);
        $this->redisLocker->release($key, $userId);
        $this->updateWithInstructConversation($delightfulAgentVersionEntity);
        return $result;
    }

    // 查询助理的版本记录

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

    // 获取助理最新版本号

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

        // 修改助理本身状态
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
        // 获取助理
        $delightfulAgentEntity = $this->delightfulAgentDomainService->getAgentById($agentId);

        // 是否是自己的助理
        if ($delightfulAgentEntity->getCreatedUid() !== $userId) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.illegal_operation');
        }

        if ($delightfulAgentEntity->getAgentVersionId() === null) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_not_published_to_enterprise_cannot_operate');
        }

        // 获取助理版本
        $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getById($delightfulAgentEntity->getAgentVersionId());

        // 校验状态是否允许被修改: APPROVAL_PASSED
        if ($delightfulAgentVersionEntity->getApprovalStatus() !== DelightfulAgentVersionStatus::APPROVAL_PASSED->value) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.approval_not_passed_cannot_modify_status');
        }

        // 修改版本
        $this->delightfulAgentVersionDomainService->updateAgentEnterpriseStatus($delightfulAgentVersionEntity->getId(), $status);
    }

    /**
     * @param DelightfulUserAuthorization $authenticatable
     */
    public function getAgentDetail(string $agentId, Authenticatable $authenticatable): DelightfulAgentVO
    {
        $flowDataIsolation = new FlowDataIsolation($authenticatable->getOrganizationCode(), $authenticatable->getId());
        $userId = $authenticatable->getId();

        // 判断是否具有权限
        $agentOperation = $this->operationPermissionAppService->getOperationByResourceAndUser(
            new PermissionDataIsolation($authenticatable->getOrganizationCode(), $authenticatable->getId()),
            ResourceType::AgentCode,
            $agentId,
            $userId
        );
        $agentOperation->validate('read', $agentId);

        $delightfulAgentVO = new DelightfulAgentVO();
        // 获取助理信息
        $delightfulAgentEntity = $this->delightfulAgentDomainService->getById($agentId);

        $delightfulAgentEntity->setInstructs($this->processInstructionsImages($delightfulAgentEntity->getInstructs(), $authenticatable->getOrganizationCode()));

        $fileLink = $this->fileDomainService->getLink($delightfulAgentEntity->getOrganizationCode(), $delightfulAgentEntity->getAgentAvatar());
        if ($fileLink !== null) {
            $delightfulAgentEntity->setAgentAvatar($fileLink->getUrl());
        }
        $delightfulAgentEntity->setUserOperation($agentOperation->value);
        $delightfulAgentVO->setAgentEntity($delightfulAgentEntity);

        // 根据版本id获取版本信息
        $agentVersionId = $delightfulAgentEntity->getAgentVersionId();
        $delightfulFlowEntity = null;
        if (! empty($agentVersionId)) {
            $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getById($agentVersionId);

            $delightfulAgentVersionEntity->setInstructs($this->processInstructionsImages($delightfulAgentVersionEntity->getInstructs(), $authenticatable->getOrganizationCode()));

            $delightfulAgentVO->setAgentVersionEntity($delightfulAgentVersionEntity);
            $delightfulFlowVersionEntity = $this->delightfulFlowVersionDomainService->show($flowDataIsolation, $delightfulAgentVersionEntity->getFlowCode(), $delightfulAgentVersionEntity->getFlowVersion());
            $delightfulFlowEntity = $delightfulFlowVersionEntity->getDelightfulFlow();

            // 只有发布了才会有状态
            $friendQueryDTO = new FriendQueryDTO();
            $friendQueryDTO->setAiCodes([$delightfulAgentVersionEntity->getFlowCode()]);

            // 数据隔离处理
            $friendDataIsolation = new ContactDataIsolation();
            $friendDataIsolation->setCurrentUserId($authenticatable->getId());
            $friendDataIsolation->setCurrentOrganizationCode($authenticatable->getOrganizationCode());

            // 获取用户代理的好友列表
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
        // 检查当前助理和版本的助理的信息
        $delightfulAgentEntity = $this->delightfulAgentDomainService->getAgentById($agentId);

        $agentVersionId = $delightfulAgentEntity->getAgentVersionId();

        // 没发布过
        if (empty($agentVersionId)) {
            return false;
        }

        $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getById($agentVersionId);

        // 任意一项不同都需要修改
        if (
            $delightfulAgentEntity->getAgentAvatar() !== $delightfulAgentVersionEntity->getAgentAvatar()
            || $delightfulAgentEntity->getAgentDescription() !== $delightfulAgentVersionEntity->getAgentDescription()
            || $delightfulAgentEntity->getAgentName() !== $delightfulAgentVersionEntity->getAgentName()
        ) {
            return true;
        }

        $flowDataIsolation = new FlowDataIsolation($authenticatable->getOrganizationCode(), $authenticatable->getId());
        // 判断工作流
        $delightfulFlowVersionEntity = $this->delightfulFlowVersionDomainService->getLastVersion($flowDataIsolation, $delightfulAgentVersionEntity->getFlowCode());

        if ($delightfulFlowVersionEntity === null) {
            return false;
        }

        if ($delightfulFlowVersionEntity->getCode() !== $delightfulAgentVersionEntity->getFlowVersion()) {
            return true;
        }

        // 判断交互指令,如果不一致则需要返回 true
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
        // 根据 aiCode(flowCode)
        return $this->delightfulAgentVersionDomainService->getAgentByFlowCode($aiCode);
    }

    /**
     * 同步默认助理会话.
     */
    public function initDefaultAssistantConversation(DelightfulUserEntity $userEntity, ?array $defaultConversationAICodes = null): void
    {
        $dataIsolation = DataIsolation::create($userEntity->getOrganizationCode(), $userEntity->getUserId());
        $defaultConversationAICodes = $defaultConversationAICodes ?? $this->delightfulAgentDomainService->getDefaultConversationAICodes();
        foreach ($defaultConversationAICodes as $aiCode) {
            $aiUserEntity = $this->delightfulUserDomainService->getByAiCode($dataIsolation, $aiCode);
            $agentName = $aiUserEntity?->getNickname();
            // 判断会话是否已经初始化，如果已初始化则跳过
            if ($this->delightfulAgentDomainService->isDefaultAssistantConversationExist($userEntity->getUserId(), $aiCode)) {
                continue;
            }
            $this->logger->info("初始化助理会话，aiCode: {$aiCode}, 名称: {$agentName}");
            try {
                Db::transaction(function () use ($dataIsolation, $aiUserEntity, $aiCode, $userEntity) {
                    // 插入默认会话记录
                    $this->delightfulAgentDomainService->insertDefaultAssistantConversation($userEntity->getUserId(), $aiCode);
                    // 添加好友，助理默认同意好友
                    $friendId = $aiUserEntity->getUserId();
                    $this->delightfulUserDomainService->addFriend($dataIsolation, $friendId);
                    // 发送添加好友控制消息
                    $friendUserEntity = new DelightfulUserEntity();
                    $friendUserEntity->setUserId($friendId);
                    di(DelightfulUserContactAppService::class)->sendAddFriendControlMessage($dataIsolation, $friendUserEntity);
                });
                $this->logger->info("初始化助理会话成功，aiCode: {$aiCode}, 名称: {$agentName}");
            } catch (Throwable $e) {
                $errorMessage = $e->getMessage();
                $trace = $e->getTraceAsString();
                $this->logger->error("初始化助理会话失败，aiCode: {$aiCode}, 名称: {$agentName}\n错误信息: {$errorMessage}\n堆栈: {$trace} ");
            }
        }
    }

    public function saveInstruct(DelightfulUserAuthorization $authorization, string $agentId, array $instructs): array
    {
        // 助理是否有权限
        $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $agentId)->validate('w', $agentId);

        return $this->delightfulAgentDomainService->updateInstruct($authorization->getOrganizationCode(), $agentId, $instructs, $authorization->getId());
    }

    public function getInstruct(string $agentId): array
    {
        // 获取助理信息
        $delightfulAgentEntity = $this->delightfulAgentDomainService->getAgentById($agentId);
        if (empty($delightfulAgentEntity->getInstructs())) {
            return [];
        }
        return $delightfulAgentEntity->getInstructs();
    }

    /**
     * 获取可见性配置中成员和部门的详细信息.
     *
     * @param null|VisibilityConfig $visibilityConfig 可见性配置
     * @param DelightfulUserAuthorization $authorization 用户授权信息
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

        // 处理成员信息，移除当前用户
        $users = $visibilityConfig->getUsers();
        if (! empty($users)) {
            $currentUserId = $authorization->getId();
            // 过滤掉当前用户
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

                // 先设置为null
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

        // 尝试获取锁，超时时间设置为60秒
        if (! $this->redisLocker->mutexLock($lockKey, $userId, 60)) {
            $this->logger->warning(sprintf('获取 initAgents 锁失败, orgCode: %s, userId: %s', $orgCode, $userId));
            // 获取锁失败，可以选择直接返回或抛出异常，这里选择直接返回避免阻塞
            return;
        }

        try {
            $this->logger->info(sprintf('获取 initAgents 锁成功, 开始执行初始化, orgCode: %s, userId: %s', $orgCode, $userId));
            $this->initChatAgent($authenticatable);
            $this->initImageGenerationAgent($authenticatable);
            $this->initDocAnalysisAgent($authenticatable);
        } finally {
            // 确保锁被释放
            $this->redisLocker->release($lockKey, $userId);
            $this->logger->info(sprintf('释放 initAgents 锁, orgCode: %s, userId: %s', $orgCode, $userId));
        }
    }

    /**
     * 为新注册的组织创建人初始化一个Chat.
     *
     * @param DelightfulUserAuthorization $authorization 用户授权信息
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
        // 准备基本配置
        $config = [
            'agent_name' => '麦吉助理',
            'agent_description' => '我会回答你一切',
            'agent_avatar' => $this->fileDomainService->getDefaultIconPaths()['bot'] ?? '',
            'flow' => $loadPresetConfig['flow'],
        ];

        // 调用通用初始化方法
        $this->initAgentFromConfig($authorization, $config);
    }

    /**
     * 为新注册的组织创建人初始化一个文生图Agent.
     *
     * @param DelightfulUserAuthorization $authorization 用户授权信息
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
        // 准备基本配置
        $config = [
            'agent_name' => '文生图助手',
            'agent_description' => '一个强大的AI文本生成图像助手，可以根据您的描述创建精美图像。',
            'agent_avatar' => $this->fileDomainService->getDefaultIconPaths()['bot'] ?? '',
            'flow' => $loadPresetConfig['flow'],
            'instruct' => $loadPresetConfig['instructs'],
        ];

        // 调用通用初始化方法
        $this->initAgentFromConfig($authorization, $config);
    }

    /**
     * 为新注册的组织创建人初始化一个文档解析Agent.
     *
     * @param DelightfulUserAuthorization $authorization 用户授权信息
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

        // 准备基本配置
        $config = [
            'agent_name' => '文档解析助手',
            'agent_description' => '文档解析助手',
            'agent_avatar' => $this->fileDomainService->getDefaultIconPaths()['bot'] ?? '',
            'flow' => $this->loadPresetConfig('document', ['modelName' => $modelName])['flow'],
        ];

        // 调用通用初始化方法
        $this->initAgentFromConfig($authorization, $config);
    }

    /**
     * 从配置文件初始化自定义Agent.
     *
     * @param $authorization DelightfulUserAuthorization 用户授权信息
     * @param array $config 包含Agent配置的数组
     * @return DelightfulAgentEntity 创建的机器人实体
     * @throws Throwable 当配置无效或初始化失败时抛出异常
     */
    #[Transactional]
    public function initAgentFromConfig(DelightfulUserAuthorization $authorization, array $config): DelightfulAgentEntity
    {
        // 创建机器人
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
        // 创建Flow
        $delightfulFLowDTO = new DelightfulFlowDTO($config['flow']);
        $delightfulFlowAssembler = new DelightfulFlowAssembler();
        $delightfulFlowDO = $delightfulFlowAssembler::createDelightfulFlowDO($delightfulFLowDTO);

        // 创建版本
        $agentVersionDTO = new DelightfulAgentVersionDTO();
        $agentVersionDTO->setAgentId($delightfulAgentEntity->getId());
        $agentVersionDTO->setVersionNumber('0.0.1');
        $agentVersionDTO->setVersionDescription('初始版本');
        $agentVersionDTO->setReleaseScope(DelightfulAgentReleaseStatus::PUBLISHED_TO_ENTERPRISE->value);
        $agentVersionDTO->setCreatedUid($authorization->getId());

        $this->releaseAgentVersion($authorization, $agentVersionDTO, $delightfulFlowDO);

        return $delightfulAgentEntity;
    }

    /**
     * 读取JSON文件并替换模板变量.
     *
     * @param string $filepath JSON文件路径
     * @param array $variables 替换变量 ['modelName' => 'gpt-4', 'otherVar' => '其他值']
     * @return null|array 解析后的数组或失败时返回null
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

        // 替换模板变量
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
     * 获取启用的助理版本列表.
     * 优化：直接在领域服务层进行JOIN查询，避免传入过多ID.
     */
    private function getEnabledAgentVersions(string $organizationCode, int $page, int $pageSize, string $agentName): array
    {
        // 直接调用领域服务获取该组织下启用的助理版本，避免先获取所有ID再查询
        return $this->delightfulAgentVersionDomainService->getEnabledAgentsByOrganization($organizationCode, $page, $pageSize, $agentName);
    }

    /**
     * 根据可见性配置过滤助理.
     * @param array $agentVersions 助理版本列表
     * @return array 过滤后的助理版本列表
     */
    private function filterVisibleAgents(array $agentVersions, string $currentUserId, string $organizationCode): array
    {
        $visibleAgentVersions = [];

        // 获取用户部门信息
        $dataIsolation = ContactDataIsolation::create($organizationCode, $currentUserId);
        $departmentUserEntities = $this->delightfulDepartmentUserDomainService->getDepartmentUsersByUserIds([$currentUserId], $dataIsolation);
        $directDepartmentIds = [];

        // 获取用户直接所属的部门ID
        foreach ($departmentUserEntities as $entity) {
            $directDepartmentIds[] = $entity->getDepartmentId();
        }

        if (empty($directDepartmentIds)) {
            $userDepartmentIds = [];
        } else {
            // 批量获取所有相关部门信息
            $departments = $this->delightfulDepartmentDomainService->getDepartmentByIds($dataIsolation, $directDepartmentIds);
            $departmentsMap = [];
            foreach ($departments as $department) {
                $departmentsMap[$department->getDepartmentId()] = $department;
            }

            // 处理部门层级关系
            $allDepartmentIds = [];
            foreach ($directDepartmentIds as $departmentId) {
                if (isset($departmentsMap[$departmentId])) {
                    $department = $departmentsMap[$departmentId];
                    $pathStr = $department->getPath();
                    // 路径格式为 "-1/parent_id/department_id"，去除前导的-1
                    $allDepartmentIds[] = array_filter(explode('/', trim($pathStr, '/')), static function ($id) {
                        return $id !== '-1';
                    });
                }
                $allDepartmentIds[] = [$departmentId];
            }
            $allDepartmentIds = array_merge(...$allDepartmentIds);
            // 去重，确保所有部门ID唯一
            $userDepartmentIds = array_unique($allDepartmentIds);
        }

        foreach ($agentVersions as $agentVersion) {
            $visibilityConfig = $agentVersion->getVisibilityConfig();

            // 全部可见或无可见性配置
            if ($visibilityConfig === null || $visibilityConfig->getVisibilityType() === VisibilityType::All->value) {
                $visibleAgentVersions[] = $agentVersion;
                continue;
            }

            // 特定可见 - 此处无需再次检查visibilityType，因为前面已排除了null和All类型
            // 剩下的只可能是SPECIFIC类型
            if ($this->isUserVisible($visibilityConfig, $currentUserId, $userDepartmentIds)) {
                $visibleAgentVersions[] = $agentVersion;
            }
        }

        return $visibleAgentVersions;
    }

    /**
     * 判断用户是否可见
     */
    private function isUserVisible(VisibilityConfig $visibilityConfig, string $currentUserId, array $userDepartmentIds): bool
    {
        // 检查用户是否在可见用户列表中
        foreach ($visibilityConfig->getUsers() as $visibleUser) {
            if ($visibleUser->getId() === $currentUserId) {
                return true;
            }
        }

        // 检查用户部门是否在可见部门列表中
        foreach ($visibilityConfig->getDepartments() as $visibleDepartment) {
            if (in_array($visibleDepartment->getId(), $userDepartmentIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取助理总数.
     * 优化：使用JOIN查询避免传入大量ID.
     * 优化：使用JOIN查询避免传入大量ID.
     */
    private function getTotalAgentsCount(string $organizationCode, string $agentName): int
    {
        return $this->delightfulAgentVersionDomainService->getEnabledAgentsByOrganizationCount($organizationCode, $agentName);
    }

    /**
     * 处理创建者信息.
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
     * 处理助理头像和好友状态
     */
    private function enrichAgentAvatarAndFriendStatus(array &$agentVersions, DelightfulUserAuthorization $authorization): void
    {
        // 批量收集需要获取链接的文件路径和flow_code
        $avatarPaths = [];
        $flowCodes = [];
        foreach ($agentVersions as $agent) {
            if (! empty($agent['agent_avatar'])) {
                $avatarPaths[] = $agent['agent_avatar'];
            }
            $flowCodes[] = $agent['flow_code'];
        }

        // 批量获取头像链接，避免循环调用getLink
        $fileLinks = [];
        if (! empty($avatarPaths)) {
            $fileLinks = $this->fileDomainService->getLinks($authorization->getOrganizationCode(), array_unique($avatarPaths));
        }

        // 设置头像URL
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
     * 获取空的分页结果.
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
     * 处理指令中的图片路径.
     * @param null|array $instructs 指令数组
     * @param string $organizationCode 组织代码
     * @return array 处理后的指令数组
     */
    private function processInstructionsImages(?array $instructs, string $organizationCode): array
    {
        if (empty($instructs)) {
            return [];
        }

        // 收集所有需要处理的图片路径
        $imagePaths = [];
        foreach ($instructs as $instruct) {
            $hasValidItems = isset($instruct['items']) && is_array($instruct['items']);
            if (! $hasValidItems) {
                continue;
            }

            foreach ($instruct['items'] as $item) {
                // 处理普通指令的图片
                $explanation = $item['instruction_explanation'] ?? [];
                $hasValidImage = is_array($explanation) && ! empty($explanation['image']);
                if ($hasValidImage) {
                    $imagePaths[] = $explanation['image'];
                }

                // 处理选项类型指令的图片
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

        // 获取所有图片的链接
        $fileLinks = $this->fileDomainService->getLinks($organizationCode, array_unique($imagePaths));
        $imageUrlMap = [];
        foreach ($fileLinks as $fileLink) {
            $imageUrlMap[$fileLink->getPath()] = $fileLink->getUrl();
        }

        // 处理指令数组中的图片路径
        foreach ($instructs as &$instruct) {
            $hasValidItems = isset($instruct['items']) && is_array($instruct['items']);
            if (! $hasValidItems) {
                continue;
            }

            foreach ($instruct['items'] as &$item) {
                // 处理普通指令的图片
                $explanation = &$item['instruction_explanation'];
                $hasValidImagePath = is_array($explanation) && isset($explanation['image']);
                if ($hasValidImagePath) {
                    $explanation['image'] = $imageUrlMap[$explanation['image']] ?? '';
                }

                // 处理选项类型指令的图片
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
     * 加载预设Agent配置.
     *
     * @param string $presetName 预设名称
     * @param array $variables 替换变量
     * @return array 配置数组
     */
    private function loadPresetConfig(string $presetName, array $variables = []): array
    {
        $presetPath = BASE_PATH . "/storage/agent/{$presetName}.txt";
        $config = $this->readJsonToArray($presetPath, $variables);

        if (empty($config)) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, "无法加载预设配置: {$presetName}");
        }

        return $config;
    }

    /**
     * 分离官方组织和用户组织的助理.
     *
     * @param array $agentEntities 助理实体数组
     * @return array 返回 [officialAgents, userOrgAgents]
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
     * 获取助理会话映射.
     *
     * @param DelightfulAgentEntity[] $agentEntities 助理实体数组
     * @param DelightfulUserAuthorization $authorization 用户授权对象
     * @return array 返回 [flowCodeToUserIdMap, conversationMap]
     */
    private function getAgentConversationMapping(array $agentEntities, DelightfulUserAuthorization $authorization): array
    {
        // 3. 分离官方和非官方助理
        [$officialAgents, $userOrgAgents] = $this->separateOfficialAndUserAgents($agentEntities);

        // 提取 flow_code
        $officialFlowCodes = array_map(static fn ($agent) => $agent->getFlowCode(), $officialAgents);
        $userOrgFlowCodes = array_map(static fn ($agent) => $agent->getFlowCode(), $userOrgAgents);

        // 4. 分别查询官方和用户组织的助理用户ID
        $flowCodeToUserIdMap = [];

        // 4.1 查询官方助理的用户ID
        if (! empty($officialFlowCodes) && OfficialOrganizationUtil::hasOfficialOrganization()) {
            $officialDataIsolation = new ContactDataIsolation();
            $officialDataIsolation->setCurrentUserId($authorization->getId());
            $officialDataIsolation->setCurrentOrganizationCode(OfficialOrganizationUtil::getOfficialOrganizationCode());

            $officialUserIdMap = $this->delightfulUserDomainService->getByAiCodes($officialDataIsolation, $officialFlowCodes);
            $flowCodeToUserIdMap = array_merge($flowCodeToUserIdMap, $officialUserIdMap);
        }

        // 4.2 查询用户组织助理的用户ID
        if (! empty($userOrgFlowCodes)) {
            $userDataIsolation = new ContactDataIsolation();
            $userDataIsolation->setCurrentUserId($authorization->getId());
            $userDataIsolation->setCurrentOrganizationCode($authorization->getOrganizationCode());

            $userOrgUserIdMap = $this->delightfulUserDomainService->getByAiCodes($userDataIsolation, $userOrgFlowCodes);
            $flowCodeToUserIdMap = array_merge($flowCodeToUserIdMap, $userOrgUserIdMap);
        }

        // 5. 收集所有助理的用户ID
        $agentUserIds = array_values($flowCodeToUserIdMap);

        // 6. 查询用户与这些助理的会话ID
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
     * 批量获取助理头像URL.
     *
     * @param DelightfulAgentEntity[] $agentEntities 助理实体数组
     * @param DelightfulUserAuthorization $authorization 用户授权对象
     * @return array 头像路径到URL的映射
     */
    private function batchGetAvatarUrls(array $agentEntities, DelightfulUserAuthorization $authorization): array
    {
        // 分离官方组织和用户组织的助理
        [$officialAgents, $userOrgAgents] = $this->separateOfficialAndUserAgents($agentEntities);

        $avatarUrlMap = [];

        // 批量获取官方组织的头像链接
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

        // 批量获取用户组织的头像链接
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
     * 将 DelightfulAgentEntity 转换为 DelightfulAgentVersionEntity.
     * 用于处理私人助理没有发布版本的情况.
     *
     * @param DelightfulAgentEntity $agentEntity 助理实体
     * @return DelightfulAgentVersionEntity 助理版本实体
     */
    private function convertAgentToAgentVersion(DelightfulAgentEntity $agentEntity): DelightfulAgentVersionEntity
    {
        $delightfulAgentVersionEntity = new DelightfulAgentVersionEntity();

        // 设置基本信息
        $delightfulAgentVersionEntity->setFlowCode($agentEntity->getFlowCode());
        $delightfulAgentVersionEntity->setAgentId($agentEntity->getId());
        $delightfulAgentVersionEntity->setAgentName($agentEntity->getAgentName());
        $delightfulAgentVersionEntity->setAgentAvatar($agentEntity->getAgentAvatar());
        $delightfulAgentVersionEntity->setAgentDescription($agentEntity->getAgentDescription());
        $delightfulAgentVersionEntity->setOrganizationCode($agentEntity->getOrganizationCode());
        $delightfulAgentVersionEntity->setCreatedUid($agentEntity->getCreatedUid());
        $delightfulAgentVersionEntity->setInstructs($agentEntity->getInstructs());
        $delightfulAgentVersionEntity->setStartPage($agentEntity->getStartPage());

        // 版本相关信息设为null，表示没有发布版本
        $delightfulAgentVersionEntity->setVersionNumber(null);
        $delightfulAgentVersionEntity->setVersionDescription(null);

        // 设置时间信息
        $delightfulAgentVersionEntity->setCreatedAt($agentEntity->getCreatedAt());
        $delightfulAgentVersionEntity->setUpdatedUid($agentEntity->getUpdatedUid());
        $delightfulAgentVersionEntity->setUpdatedAt($agentEntity->getUpdatedAt());

        return $delightfulAgentVersionEntity;
    }
}

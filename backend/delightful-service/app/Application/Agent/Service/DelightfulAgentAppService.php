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

        // querycurrent具havepermission
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

    // create/modifyassistant
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
            // modifycheckpermission
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

    // getfinger定userassistant
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
            // firsttryasfor agent_version_id fromalreadypublishversionmiddleget
            $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getAgentById($agentVersionId);
        } catch (Throwable $e) {
            // iffail,from delightful_bots tablegetoriginalassistantdata,andconvertfor DelightfulAgentVersionEntity(versionnumberfor null)
            try {
                $delightfulAgentEntity = $this->delightfulAgentDomainService->getById($agentVersionId);
                $delightfulAgentVersionEntity = $this->convertAgentToAgentVersion($delightfulAgentEntity);
            } catch (Throwable) {
                // ifallfail,throworiginalexception
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

    // getpublishversionassistant,toatuser
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

        // dataisolationhandle
        $friendDataIsolation = new ContactDataIsolation();
        $friendDataIsolation->setCurrentUserId($authorization->getId());
        $friendDataIsolation->setCurrentOrganizationCode($organizationCode);

        // getuserproxygood友columntable
        $userAgentFriends = $this->delightfulUserDomainService->getUserAgentFriendsList($friendQueryDTO, $friendDataIsolation);

        $delightfulAgentVO->setIsAdd(isset($userAgentFriends[$delightfulAgentVersionEntity->getFlowCode()]));

        $visibilityConfig = $delightfulAgentVersionEntity->getVisibilityConfig();

        $this->setVisibilityConfigDetails($visibilityConfig, $authorization);
        return $delightfulAgentVO;
    }

    /**
     * getenterpriseinside部assistant.
     * @param DelightfulUserAuthorization $authorization
     */
    public function getAgentsByOrganizationPage(Authenticatable $authorization, int $page, int $pageSize, string $agentName): array
    {
        if (! $authorization instanceof DelightfulUserAuthorization) {
            return [];
        }

        $organizationCode = $authorization->getOrganizationCode();
        $currentUserId = $authorization->getId();

        // getenableassistantversioncolumntable
        $agentVersions = $this->getEnabledAgentVersions($organizationCode, $page, $pageSize, $agentName);
        if (empty($agentVersions)) {
            return $this->getEmptyPageResult($page, $pageSize);
        }

        // according tovisiblepropertyconfigurationfilterassistant
        $visibleAgentVersions = $this->filterVisibleAgents($agentVersions, $currentUserId, $organizationCode);
        if (empty($visibleAgentVersions)) {
            return $this->getEmptyPageResult($page, $pageSize);
        }

        // convertforarrayformat
        $agentVersions = DelightfulAgentVersionFactory::toArrays($visibleAgentVersions);

        // getassistanttotal
        $totalAgentsCount = $this->getTotalAgentsCount($organizationCode, $agentName);

        // handlecreate者info
        $this->enrichCreatorInfo($agentVersions);

        // handleavatarandgood友status
        $this->enrichAgentAvatarAndFriendStatus($agentVersions, $authorization);

        return [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $totalAgentsCount,
            'list' => $agentVersions,
        ];
    }

    /**
     * getchat模typecanuseassistantcolumntable(allquantitydata,notpagination).
     * @param Authenticatable $authorization userauthorization
     * @param DelightfulAgentQuery $query querycondition
     * @return array assistantcolumntableandconversationID
     */
    public function getChatModeAvailableAgents(Authenticatable $authorization, DelightfulAgentQuery $query): array
    {
        if (! $authorization instanceof DelightfulUserAuthorization) {
            return ['total' => 0, 'list' => []];
        }

        // 1. use queriesAvailable queryofficial+userorganizationassistant(allquantitydata)
        $fullQuery = clone $query;
        $fullPage = Page::createNoPage(); // getallquantitydata
        $agentAppService = di(AgentAppService::class);
        $fullData = $agentAppService->queriesAvailable($authorization, $fullQuery, $fullPage, true);

        if (empty($fullData['list'])) {
            return ['total' => 0, 'list' => []];
        }

        // getallquantityassistant实body
        $totalCount = $fullData['total'];
        /** @var DelightfulAgentEntity[] $agentEntities */
        $agentEntities = $fullData['list'];

        // getassistantconversationmapping
        [$flowCodeToUserIdMap, $conversationMap] = $this->getAgentConversationMapping($agentEntities, $authorization);

        // batchquantitygetavatarlink
        $avatarUrlMap = $this->batchGetAvatarUrls($agentEntities, $authorization);

        // convertforarrayformatandaddconversationID
        $result = [];
        foreach ($agentEntities as $agent) {
            $agentData = $agent->toArray();

            // add agent_id field,value同 id
            $agentData['agent_id'] = $agentData['id'];

            // addwhetherforofficialorganizationidentifier
            $agentData['is_office'] = OfficialOrganizationUtil::isOfficialOrganization($agent->getOrganizationCode());

            // handleavatarlink
            $agentData['agent_avatar'] = $avatarUrlMap[$agent->getAgentAvatar()] ?? null;
            $agentData['robot_avatar'] = $agentData['agent_avatar'];

            // addassistantuserIDandconversationID
            $flowCode = $agent->getFlowCode();
            if (isset($flowCodeToUserIdMap[$flowCode])) {
                $userId = $flowCodeToUserIdMap[$flowCode];
                $agentData['user_id'] = $userId;

                // addconversationID(if存in)
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
        // 查outenableassistant
        $agents = $this->delightfulAgentDomainService->getEnabledAgents();
        // use array_column extract agent_version_id
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

        // onlypublishtoenterprise才from己giveadd
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

        // ifassistantstatusisdisablethennotcanpublish
        if ($agent->getStatus() === DelightfulAgentVersionStatus::ENTERPRISE_DISABLED->value) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_status_disabled_cannot_publish');
        }
        $delightfulAgentVersionEntity = $this->buildAgentVersion($agent, $agentVersionDTO);

        // publishmostnewconnectstream
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

        // ifpublishisperson,that么notcan操asthethree方assistant
        if ($delightfulAgentVersionEntity->getReleaseScope() === DelightfulAgentReleaseStatus::PERSONAL_USE->value) {
            $thirdPlatformList = null;
        }
        // syncthethree方assistant
        $this->delightfulBotThirdPlatformChatDomainService->syncBotThirdPlatformList($agent->getId(), $thirdPlatformList);

        // 首timepublishaddforgood友
        $result['is_add_friend'] = $isAddFriend;

        $delightfulAgentVersionEntity = $result['data'];
        $versionId = $delightfulAgentVersionEntity->getId();
        $agentId = $delightfulAgentVersionEntity->getRootId();
        $this->delightfulAgentDomainService->updateDefaultVersion($agentId, $versionId);
        $this->redisLocker->release($key, $userId);
        $this->updateWithInstructConversation($delightfulAgentVersionEntity);
        return $result;
    }

    // queryassistantversionrecord

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

    // getassistantmostnewversionnumber

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

        // modifyassistantitselfstatus
        $this->delightfulAgentDomainService->updateAgentStatus($agentId, $status->value);
    }

    /**
     * @param DelightfulUserAuthorization $authorization
     */
    public function updateAgentEnterpriseStatus(Authenticatable $authorization, string $agentId, int $status, string $userId): void
    {
        $this->getAgentOperation($this->createPermissionDataIsolation($authorization), $agentId)->validate('w', $agentId);

        // validation
        if ($status !== DelightfulAgentVersionStatus::ENTERPRISE_PUBLISHED->value && $status !== DelightfulAgentVersionStatus::ENTERPRISE_UNPUBLISHED->value) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.status_can_only_be_published_or_unpublished');
        }
        // getassistant
        $delightfulAgentEntity = $this->delightfulAgentDomainService->getAgentById($agentId);

        // whetherisfrom己assistant
        if ($delightfulAgentEntity->getCreatedUid() !== $userId) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.illegal_operation');
        }

        if ($delightfulAgentEntity->getAgentVersionId() === null) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.agent_not_published_to_enterprise_cannot_operate');
        }

        // getassistantversion
        $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getById($delightfulAgentEntity->getAgentVersionId());

        // validationstatuswhetherallowbemodify: APPROVAL_PASSED
        if ($delightfulAgentVersionEntity->getApprovalStatus() !== DelightfulAgentVersionStatus::APPROVAL_PASSED->value) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.approval_not_passed_cannot_modify_status');
        }

        // modifyversion
        $this->delightfulAgentVersionDomainService->updateAgentEnterpriseStatus($delightfulAgentVersionEntity->getId(), $status);
    }

    /**
     * @param DelightfulUserAuthorization $authenticatable
     */
    public function getAgentDetail(string $agentId, Authenticatable $authenticatable): DelightfulAgentVO
    {
        $flowDataIsolation = new FlowDataIsolation($authenticatable->getOrganizationCode(), $authenticatable->getId());
        $userId = $authenticatable->getId();

        // judgewhether具havepermission
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

            // onlypublish才willhavestatus
            $friendQueryDTO = new FriendQueryDTO();
            $friendQueryDTO->setAiCodes([$delightfulAgentVersionEntity->getFlowCode()]);

            // dataisolationhandle
            $friendDataIsolation = new ContactDataIsolation();
            $friendDataIsolation->setCurrentUserId($authenticatable->getId());
            $friendDataIsolation->setCurrentOrganizationCode($authenticatable->getOrganizationCode());

            // getuserproxygood友columntable
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
        // checkcurrentassistantandversionassistantinfo
        $delightfulAgentEntity = $this->delightfulAgentDomainService->getAgentById($agentId);

        $agentVersionId = $delightfulAgentEntity->getAgentVersionId();

        // notpublishpass
        if (empty($agentVersionId)) {
            return false;
        }

        $delightfulAgentVersionEntity = $this->delightfulAgentVersionDomainService->getById($agentVersionId);

        // 任意oneitemdifferentallneedmodify
        if (
            $delightfulAgentEntity->getAgentAvatar() !== $delightfulAgentVersionEntity->getAgentAvatar()
            || $delightfulAgentEntity->getAgentDescription() !== $delightfulAgentVersionEntity->getAgentDescription()
            || $delightfulAgentEntity->getAgentName() !== $delightfulAgentVersionEntity->getAgentName()
        ) {
            return true;
        }

        $flowDataIsolation = new FlowDataIsolation($authenticatable->getOrganizationCode(), $authenticatable->getId());
        // judgeworkflow
        $delightfulFlowVersionEntity = $this->delightfulFlowVersionDomainService->getLastVersion($flowDataIsolation, $delightfulAgentVersionEntity->getFlowCode());

        if ($delightfulFlowVersionEntity === null) {
            return false;
        }

        if ($delightfulFlowVersionEntity->getCode() !== $delightfulAgentVersionEntity->getFlowVersion()) {
            return true;
        }

        // judge交互finger令,ifnotone致thenneedreturn true
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
            // judgeconversationwhetheralready经initialize,ifalreadyinitializethenskip
            if ($this->delightfulAgentDomainService->isDefaultAssistantConversationExist($userEntity->getUserId(), $aiCode)) {
                continue;
            }
            $this->logger->info("initializeassistantconversation,aiCode: {$aiCode}, name: {$agentName}");
            try {
                Db::transaction(function () use ($dataIsolation, $aiUserEntity, $aiCode, $userEntity) {
                    // insertdefaultconversationrecord
                    $this->delightfulAgentDomainService->insertDefaultAssistantConversation($userEntity->getUserId(), $aiCode);
                    // addgood友,assistantdefaultagreegood友
                    $friendId = $aiUserEntity->getUserId();
                    $this->delightfulUserDomainService->addFriend($dataIsolation, $friendId);
                    // sendaddgood友controlmessage
                    $friendUserEntity = new DelightfulUserEntity();
                    $friendUserEntity->setUserId($friendId);
                    di(DelightfulUserContactAppService::class)->sendAddFriendControlMessage($dataIsolation, $friendUserEntity);
                });
                $this->logger->info("initializeassistantconversationsuccess,aiCode: {$aiCode}, name: {$agentName}");
            } catch (Throwable $e) {
                $errorMessage = $e->getMessage();
                $trace = $e->getTraceAsString();
                $this->logger->error("initializeassistantconversationfail,aiCode: {$aiCode}, name: {$agentName}\nerrorinfo: {$errorMessage}\nheapstack: {$trace} ");
            }
        }
    }

    public function saveInstruct(DelightfulUserAuthorization $authorization, string $agentId, array $instructs): array
    {
        // assistantwhetherhavepermission
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
     * getvisiblepropertyconfigurationmiddlememberanddepartmentdetailedinfo.
     *
     * @param null|VisibilityConfig $visibilityConfig visiblepropertyconfiguration
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

        // handlememberinfo,移exceptcurrentuser
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

                // 先settingfornull
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

        // trygetlock,timeouttimesettingfor60second
        if (! $this->redisLocker->mutexLock($lockKey, $userId, 60)) {
            $this->logger->warning(sprintf('get initAgents lockfail, orgCode: %s, userId: %s', $orgCode, $userId));
            // getlockfail,canchoosedirectlyreturnorthrowexception,thiswithinchoosedirectlyreturnavoid阻塞
            return;
        }

        try {
            $this->logger->info(sprintf('get initAgents locksuccess, startexecuteinitialize, orgCode: %s, userId: %s', $orgCode, $userId));
            $this->initChatAgent($authenticatable);
            $this->initImageGenerationAgent($authenticatable);
            $this->initDocAnalysisAgent($authenticatable);
        } finally {
            // ensurelockberelease
            $this->redisLocker->release($lockKey, $userId);
            $this->logger->info(sprintf('release initAgents lock, orgCode: %s, userId: %s', $orgCode, $userId));
        }
    }

    /**
     * fornewregisterorganizationcreatepersoninitializeoneChat.
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
        // prepare基本configuration
        $config = [
            'agent_name' => '麦吉assistant',
            'agent_description' => 'Iwillreturn答youone切',
            'agent_avatar' => $this->fileDomainService->getDefaultIconPaths()['bot'] ?? '',
            'flow' => $loadPresetConfig['flow'],
        ];

        // call通useinitializemethod
        $this->initAgentFromConfig($authorization, $config);
    }

    /**
     * fornewregisterorganizationcreatepersoninitializeonetext generationgraphAgent.
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
        // prepare基本configuration
        $config = [
            'agent_name' => 'text generationgraph助hand',
            'agent_description' => 'onestrongbigAItextgenerategraphlike助hand,canaccording to您descriptioncreate精美graphlike.',
            'agent_avatar' => $this->fileDomainService->getDefaultIconPaths()['bot'] ?? '',
            'flow' => $loadPresetConfig['flow'],
            'instruct' => $loadPresetConfig['instructs'],
        ];

        // call通useinitializemethod
        $this->initAgentFromConfig($authorization, $config);
    }

    /**
     * fornewregisterorganizationcreatepersoninitializeonedocumentparseAgent.
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

        // prepare基本configuration
        $config = [
            'agent_name' => 'documentparse助hand',
            'agent_description' => 'documentparse助hand',
            'agent_avatar' => $this->fileDomainService->getDefaultIconPaths()['bot'] ?? '',
            'flow' => $this->loadPresetConfig('document', ['modelName' => $modelName])['flow'],
        ];

        // call通useinitializemethod
        $this->initAgentFromConfig($authorization, $config);
    }

    /**
     * fromconfigurationfileinitializecustomizeAgent.
     *
     * @param $authorization DelightfulUserAuthorization userauthorizationinfo
     * @param array $config containAgentconfigurationarray
     * @return DelightfulAgentEntity create机器person实body
     * @throws Throwable whenconfigurationinvalidorinitializefailo clockthrowexception
     */
    #[Transactional]
    public function initAgentFromConfig(DelightfulUserAuthorization $authorization, array $config): DelightfulAgentEntity
    {
        // create机器person
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
     * readJSONfileandreplacetemplatevariable.
     *
     * @param string $filepath JSONfilepath
     * @param array $variables replacevariable ['modelName' => 'gpt-4', 'otherVar' => 'othervalue']
     * @return null|array parsebackarrayorfailo clockreturnnull
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

        // replacetemplatevariable
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
     * getenableassistantversioncolumntable.
     * optimize:directlyindomainservicelayerconductJOINquery,avoidpass inpass多ID.
     */
    private function getEnabledAgentVersions(string $organizationCode, int $page, int $pageSize, string $agentName): array
    {
        // directlycalldomainservicegettheorganizationdownenableassistantversion,avoid先get所haveIDagainquery
        return $this->delightfulAgentVersionDomainService->getEnabledAgentsByOrganization($organizationCode, $page, $pageSize, $agentName);
    }

    /**
     * according tovisiblepropertyconfigurationfilterassistant.
     * @param array $agentVersions assistantversioncolumntable
     * @return array filterbackassistantversioncolumntable
     */
    private function filterVisibleAgents(array $agentVersions, string $currentUserId, string $organizationCode): array
    {
        $visibleAgentVersions = [];

        // getuserdepartmentinfo
        $dataIsolation = ContactDataIsolation::create($organizationCode, $currentUserId);
        $departmentUserEntities = $this->delightfulDepartmentUserDomainService->getDepartmentUsersByUserIds([$currentUserId], $dataIsolation);
        $directDepartmentIds = [];

        // getuserdirectlybelong todepartmentID
        foreach ($departmentUserEntities as $entity) {
            $directDepartmentIds[] = $entity->getDepartmentId();
        }

        if (empty($directDepartmentIds)) {
            $userDepartmentIds = [];
        } else {
            // batchquantityget所have相closedepartmentinfo
            $departments = $this->delightfulDepartmentDomainService->getDepartmentByIds($dataIsolation, $directDepartmentIds);
            $departmentsMap = [];
            foreach ($departments as $department) {
                $departmentsMap[$department->getDepartmentId()] = $department;
            }

            // handledepartmentlayerlevelclose系
            $allDepartmentIds = [];
            foreach ($directDepartmentIds as $departmentId) {
                if (isset($departmentsMap[$departmentId])) {
                    $department = $departmentsMap[$departmentId];
                    $pathStr = $department->getPath();
                    // pathformatfor "-1/parent_id/department_id",goexceptfront导-1
                    $allDepartmentIds[] = array_filter(explode('/', trim($pathStr, '/')), static function ($id) {
                        return $id !== '-1';
                    });
                }
                $allDepartmentIds[] = [$departmentId];
            }
            $allDepartmentIds = array_merge(...$allDepartmentIds);
            // go重,ensure所havedepartmentID唯one
            $userDepartmentIds = array_unique($allDepartmentIds);
        }

        foreach ($agentVersions as $agentVersion) {
            $visibilityConfig = $agentVersion->getVisibilityConfig();

            // all部visibleornovisiblepropertyconfiguration
            if ($visibilityConfig === null || $visibilityConfig->getVisibilityType() === VisibilityType::All->value) {
                $visibleAgentVersions[] = $agentVersion;
                continue;
            }

            // 特定visible - this处no需againtimecheckvisibilityType,因forfrontsurfacealreadyrowexceptnullandAlltype
            // 剩downonlymaybeisSPECIFICtype
            if ($this->isUserVisible($visibilityConfig, $currentUserId, $userDepartmentIds)) {
                $visibleAgentVersions[] = $agentVersion;
            }
        }

        return $visibleAgentVersions;
    }

    /**
     * judgeuserwhethervisible
     */
    private function isUserVisible(VisibilityConfig $visibilityConfig, string $currentUserId, array $userDepartmentIds): bool
    {
        // checkuserwhetherinvisibleusercolumntablemiddle
        foreach ($visibilityConfig->getUsers() as $visibleUser) {
            if ($visibleUser->getId() === $currentUserId) {
                return true;
            }
        }

        // checkuserdepartmentwhetherinvisibledepartmentcolumntablemiddle
        foreach ($visibilityConfig->getDepartments() as $visibleDepartment) {
            if (in_array($visibleDepartment->getId(), $userDepartmentIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * getassistanttotal.
     * optimize:useJOINqueryavoidpass inbigquantityID.
     * optimize:useJOINqueryavoidpass inbigquantityID.
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
     * handleassistantavatarandgood友status
     */
    private function enrichAgentAvatarAndFriendStatus(array &$agentVersions, DelightfulUserAuthorization $authorization): void
    {
        // batchquantity收collectionneedgetlinkfilepathandflow_code
        $avatarPaths = [];
        $flowCodes = [];
        foreach ($agentVersions as $agent) {
            if (! empty($agent['agent_avatar'])) {
                $avatarPaths[] = $agent['agent_avatar'];
            }
            $flowCodes[] = $agent['flow_code'];
        }

        // batchquantitygetavatarlink,avoidloopcallgetLink
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
     * getnullpaginationresult.
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
     * handlefinger令middleimagepath.
     * @param null|array $instructs finger令array
     * @param string $organizationCode organizationcode
     * @return array handlebackfinger令array
     */
    private function processInstructionsImages(?array $instructs, string $organizationCode): array
    {
        if (empty($instructs)) {
            return [];
        }

        // 收collection所haveneedhandleimagepath
        $imagePaths = [];
        foreach ($instructs as $instruct) {
            $hasValidItems = isset($instruct['items']) && is_array($instruct['items']);
            if (! $hasValidItems) {
                continue;
            }

            foreach ($instruct['items'] as $item) {
                // handlenormalfinger令image
                $explanation = $item['instruction_explanation'] ?? [];
                $hasValidImage = is_array($explanation) && ! empty($explanation['image']);
                if ($hasValidImage) {
                    $imagePaths[] = $explanation['image'];
                }

                // handleoptiontypefinger令image
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

        // get所haveimagelink
        $fileLinks = $this->fileDomainService->getLinks($organizationCode, array_unique($imagePaths));
        $imageUrlMap = [];
        foreach ($fileLinks as $fileLink) {
            $imageUrlMap[$fileLink->getPath()] = $fileLink->getUrl();
        }

        // handlefinger令arraymiddleimagepath
        foreach ($instructs as &$instruct) {
            $hasValidItems = isset($instruct['items']) && is_array($instruct['items']);
            if (! $hasValidItems) {
                continue;
            }

            foreach ($instruct['items'] as &$item) {
                // handlenormalfinger令image
                $explanation = &$item['instruction_explanation'];
                $hasValidImagePath = is_array($explanation) && isset($explanation['image']);
                if ($hasValidImagePath) {
                    $explanation['image'] = $imageUrlMap[$explanation['image']] ?? '';
                }

                // handleoptiontypefinger令image
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
     * @param array $variables replacevariable
     * @return array configurationarray
     */
    private function loadPresetConfig(string $presetName, array $variables = []): array
    {
        $presetPath = BASE_PATH . "/storage/agent/{$presetName}.txt";
        $config = $this->readJsonToArray($presetPath, $variables);

        if (empty($config)) {
            ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, "no法loadpresetconfiguration: {$presetName}");
        }

        return $config;
    }

    /**
     * minute离officialorganizationanduserorganizationassistant.
     *
     * @param array $agentEntities assistant实bodyarray
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
     * @param DelightfulAgentEntity[] $agentEntities assistant实bodyarray
     * @param DelightfulUserAuthorization $authorization userauthorizationobject
     * @return array return [flowCodeToUserIdMap, conversationMap]
     */
    private function getAgentConversationMapping(array $agentEntities, DelightfulUserAuthorization $authorization): array
    {
        // 3. minute离officialandnonofficialassistant
        [$officialAgents, $userOrgAgents] = $this->separateOfficialAndUserAgents($agentEntities);

        // extract flow_code
        $officialFlowCodes = array_map(static fn ($agent) => $agent->getFlowCode(), $officialAgents);
        $userOrgFlowCodes = array_map(static fn ($agent) => $agent->getFlowCode(), $userOrgAgents);

        // 4. minute别queryofficialanduserorganizationassistantuserID
        $flowCodeToUserIdMap = [];

        // 4.1 queryofficialassistantuserID
        if (! empty($officialFlowCodes) && OfficialOrganizationUtil::hasOfficialOrganization()) {
            $officialDataIsolation = new ContactDataIsolation();
            $officialDataIsolation->setCurrentUserId($authorization->getId());
            $officialDataIsolation->setCurrentOrganizationCode(OfficialOrganizationUtil::getOfficialOrganizationCode());

            $officialUserIdMap = $this->delightfulUserDomainService->getByAiCodes($officialDataIsolation, $officialFlowCodes);
            $flowCodeToUserIdMap = array_merge($flowCodeToUserIdMap, $officialUserIdMap);
        }

        // 4.2 queryuserorganizationassistantuserID
        if (! empty($userOrgFlowCodes)) {
            $userDataIsolation = new ContactDataIsolation();
            $userDataIsolation->setCurrentUserId($authorization->getId());
            $userDataIsolation->setCurrentOrganizationCode($authorization->getOrganizationCode());

            $userOrgUserIdMap = $this->delightfulUserDomainService->getByAiCodes($userDataIsolation, $userOrgFlowCodes);
            $flowCodeToUserIdMap = array_merge($flowCodeToUserIdMap, $userOrgUserIdMap);
        }

        // 5. 收collection所haveassistantuserID
        $agentUserIds = array_values($flowCodeToUserIdMap);

        // 6. queryuserandthistheseassistantconversationID
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
     * batchquantitygetassistantavatarURL.
     *
     * @param DelightfulAgentEntity[] $agentEntities assistant实bodyarray
     * @param DelightfulUserAuthorization $authorization userauthorizationobject
     * @return array avatarpathtoURLmapping
     */
    private function batchGetAvatarUrls(array $agentEntities, DelightfulUserAuthorization $authorization): array
    {
        // minute离officialorganizationanduserorganizationassistant
        [$officialAgents, $userOrgAgents] = $this->separateOfficialAndUserAgents($agentEntities);

        $avatarUrlMap = [];

        // batchquantitygetofficialorganizationavatarlink
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

        // batchquantitygetuserorganizationavatarlink
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
     * will DelightfulAgentEntity convertfor DelightfulAgentVersionEntity.
     * useathandle私personassistantnothavepublishversionsituation.
     *
     * @param DelightfulAgentEntity $agentEntity assistant实body
     * @return DelightfulAgentVersionEntity assistantversion实body
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

        // version相closeinfo设fornull,indicatenothavepublishversion
        $delightfulAgentVersionEntity->setVersionNumber(null);
        $delightfulAgentVersionEntity->setVersionDescription(null);

        // settingtimeinfo
        $delightfulAgentVersionEntity->setCreatedAt($agentEntity->getCreatedAt());
        $delightfulAgentVersionEntity->setUpdatedUid($agentEntity->getUpdatedUid());
        $delightfulAgentVersionEntity->setUpdatedAt($agentEntity->getUpdatedAt());

        return $delightfulAgentVersionEntity;
    }
}

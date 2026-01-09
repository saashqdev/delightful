<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Chat\DTO\Message\ControlMessage\AddFriendMessage;
use App\Domain\Chat\DTO\PageResponseDTO\PageResponseDTO;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Entity\ValueObject\PlatformRootDepartmentId;
use App\Domain\Chat\Service\DelightfulChatDomainService;
use App\Domain\Contact\DTO\FriendQueryDTO;
use App\Domain\Contact\DTO\UserQueryDTO;
use App\Domain\Contact\DTO\UserUpdateDTO;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\AddFriendType;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\DepartmentOption;
use App\Domain\Contact\Entity\ValueObject\UserOption;
use App\Domain\Contact\Entity\ValueObject\UserQueryType;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\Contact\Service\Facade\DelightfulUserDomainExtendInterface;
use App\Domain\Contact\Service\DelightfulAccountDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentUserDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;
use App\Domain\OrganizationEnvironment\Service\DelightfulOrganizationEnvDomainService;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\Operation;
use App\Domain\Permission\Entity\ValueObject\OperationPermission\ResourceType;
use App\Domain\Permission\Service\OperationPermissionDomainService;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Chat\Assembler\PageListAssembler;
use App\Interfaces\Chat\Assembler\UserAssembler;
use App\Interfaces\Chat\DTO\AgentInfoDTO;
use App\Interfaces\Chat\DTO\UserDepartmentDetailDTO;
use App\Interfaces\Chat\DTO\UserDetailDTO;
use Hyperf\Codec\Json;
use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Qbhy\HyperfAuth\Authenticatable;
use Throwable;

class DelightfulUserContactAppService extends AbstractAppService
{
    public function __construct(
        protected readonly DelightfulUserDomainService $userDomainService,
        protected readonly DelightfulAccountDomainService $accountDomainService,
        protected readonly DelightfulDepartmentUserDomainService $departmentUserDomainService,
        protected readonly DelightfulDepartmentDomainService $departmentChartDomainService,
        protected LoggerInterface $logger,
        protected readonly DelightfulOrganizationEnvDomainService $delightfulOrganizationEnvDomainService,
        protected readonly FileDomainService $fileDomainService,
        protected readonly DelightfulAgentDomainService $delightfulAgentDomainService,
        protected readonly OperationPermissionDomainService $operationPermissionDomainService,
        protected readonly DelightfulChatDomainService $delightfulChatDomainService,
        protected readonly ContainerInterface $container
    ) {
        try {
            $this->logger = ApplicationContext::getContainer()->get(LoggerFactory::class)?->get(get_class($this));
        } catch (Throwable) {
        }
    }

    /**
     * @param string $friendId good友userid. good友maybeisai
     * @throws Throwable
     */
    public function addFriend(DelightfulUserAuthorization $userAuthorization, string $friendId, AddFriendType $addFriendType): bool
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // checkwhetheralready经isgood友
        if ($this->userDomainService->isFriend($dataIsolation->getCurrentUserId(), $friendId)) {
            return true;
        }

        if (! $this->userDomainService->addFriend($dataIsolation, $friendId)) {
            return false;
        }
        // sendaddgood友message.addgood友splitfor:good友apply/good友agree/good友reject
        if ($addFriendType === AddFriendType::PASS) {
            // sendaddgood友controlmessage
            $friendUserEntity = new DelightfulUserEntity();
            $friendUserEntity->setUserId($friendId);
            $this->sendAddFriendControlMessage($dataIsolation, $friendUserEntity);
        }
        return true;
    }

    /**
     * toAIassistantsendaddgood友controlmessage.
     * @throws Throwable
     */
    public function sendAddFriendControlMessage(DataIsolation $dataIsolation, DelightfulUserEntity $friendUserEntity): bool
    {
        // checkwhetheralready经isgood友
        if ($this->userDomainService->isFriend($dataIsolation->getCurrentUserId(), $friendUserEntity->getUserId())) {
            return true;
        }

        $now = date('Y-m-d H:i:s');
        $messageDTO = new DelightfulMessageEntity([
            'receive_id' => $friendUserEntity->getUserId(),
            'receive_type' => ConversationType::Ai->value,
            'message_type' => ControlMessageType::AddFriendSuccess->value,
            'sender_id' => $dataIsolation->getCurrentUserId(),
            'sender_organization_code' => $dataIsolation->getCurrentOrganizationCode(),
            'app_message_id' => (string) IdGenerator::getSnowId(),
            'sender_type' => ConversationType::User->value,
            'send_time' => $now,
            'created_at' => $now,
            'updated_at' => $now,
            'content' => [
                'receive_id' => $friendUserEntity->getUserId(),
                'receive_type' => ConversationType::Ai->value,
                'user_id' => $dataIsolation->getCurrentUserId(),
            ],
        ]);
        /** @var AddFriendMessage $messageStruct */
        $messageStruct = $messageDTO->getContent();
        $conversationEntity = new DelightfulConversationEntity();
        $conversationEntity->setReceiveId($messageStruct->getReceiveId());
        $receiveType = ConversationType::tryFrom($messageStruct->getReceiveType());
        if ($receiveType === null) {
            ExceptionBuilder::throw(ChatErrorCode::RECEIVER_NOT_FOUND);
        }
        $conversationEntity->setReceiveType($receiveType);

        $receiverConversationEntity = new DelightfulConversationEntity();
        $receiverConversationEntity->setUserId($messageStruct->getReceiveId());
        $receiverConversationEntity->setUserOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        // 通usecontrolmessagehandlelogic
        $this->delightfulChatDomainService->handleCommonControlMessage($messageDTO, $conversationEntity, $receiverConversationEntity);

        return true;
    }

    public function searchFriend(string $keyword): array
    {
        return $this->userDomainService->searchFriend($keyword);
    }

    public function getUserWithoutDepartmentInfoByIds(array $ids, DelightfulUserAuthorization $authorization, array $column = ['*']): array
    {
        $dataIsolation = $this->createDataIsolation($authorization);
        return $this->userDomainService->getUserByIds($ids, $dataIsolation, $column);
    }

    /**
     * batchquantityqueryorganization架构,ai ,or者person版user.
     */
    public function getUserDetailByIds(UserQueryDTO $dto, DelightfulUserAuthorization $authorization): array
    {
        $userIds = $dto->getUserIds();
        $pageToken = (int) $dto->getPageToken();
        $pageSize = $dto->getPageSize();

        $userIds = array_slice($userIds, $pageToken, $pageSize);
        $queryType = $dto->getQueryType();
        $dataIsolation = $this->createDataIsolation($authorization);

        // getcurrentuser拥haveorganizationcolumn表
        $userOrganizations = $this->userDomainService->getUserOrganizations($dataIsolation->getCurrentUserId());

        // 基本userinfoquery - 传入user拥haveorganizationcolumn表
        $usersDetailDTOList = $this->userDomainService->getUserDetailByUserIdsWithOrgCodes($userIds, $userOrganizations);
        // handleuseravatar
        $usersDetail = $this->getUsersAvatarCoordinator($usersDetailDTOList, $dataIsolation);

        // handleuserassistantinfo
        $this->addAgentInfoToUsers($authorization, $usersDetail);

        if ($queryType === UserQueryType::User) {
            // only查person员info
            $users = $usersDetail;
        } else {
            // querydepartmentinfo
            $withDepartmentFullPath = $queryType === UserQueryType::UserAndDepartmentFullPath;

            // getuser所属department
            $departmentUsers = $this->departmentUserDomainService->getDepartmentUsersByUserIds($userIds, $dataIsolation);
            $departmentIds = array_column($departmentUsers, 'department_id');

            // getdepartmentdetail
            $departmentsInfo = $this->departmentChartDomainService->getDepartmentFullPathByIds($dataIsolation, $departmentIds);

            // group装useranddepartmentinfo
            $users = UserAssembler::getUserDepartmentDetailDTOList($departmentUsers, $usersDetail, $departmentsInfo, $withDepartmentFullPath);
        }

        // 通讯录andsearch相closeinterface,filterhiddendepartmentandhiddenuser.
        $users = $this->filterDepartmentOrUserHidden($users);
        return PageListAssembler::pageByMysql($users, (int) $dto->getPageToken(), $pageSize, count($dto->getUserIds()));
    }

    public function getUsersDetailByDepartmentId(UserQueryDTO $dto, DelightfulUserAuthorization $authorization): array
    {
        $dataIsolation = $this->createDataIsolation($authorization);
        // rootdepartmentbeabstractfor -1,所bythiswithinneedconvert
        if ($dto->getDepartmentId() === PlatformRootDepartmentId::Delightful) {
            $departmentId = $this->departmentChartDomainService->getDepartmentRootId($dataIsolation);
            $dto->setDepartmentId($departmentId);
        }
        // departmentdownusercolumn表,limit pageSize
        $usersPageResponseDTO = $this->departmentUserDomainService->getDepartmentUsersByDepartmentId($dto, $dataIsolation);
        $departmentUsers = $usersPageResponseDTO->getItems();
        $departmentIds = array_column($departmentUsers, 'department_id');
        // departmentdetail
        $departmentsInfo = $this->departmentChartDomainService->getDepartmentByIds($dataIsolation, $departmentIds);
        $departmentsInfoWithFullPath = [];
        foreach ($departmentsInfo as $departmentInfo) {
            $departmentsInfoWithFullPath[$departmentInfo->getDepartmentId()] = [$departmentInfo];
        }
        // getusertrue名/nickname/hand机number/avataretcinfo
        $userIds = array_values(array_unique(array_column($departmentUsers, 'user_id')));
        $usersDetail = $this->userDomainService->getUserDetailByUserIds($userIds, $dataIsolation);
        $usersDetail = $this->getUsersAvatar($usersDetail, $dataIsolation);
        // organizationuser + departmentdetail
        $userDepartmentDetailDTOS = UserAssembler::getUserDepartmentDetailDTOList($departmentUsers, $usersDetail, $departmentsInfoWithFullPath);
        // 通讯录andsearch相closeinterface,filterhiddendepartmentandhiddenuser.
        $userDepartmentDetailDTOS = $this->filterDepartmentOrUserHidden($userDepartmentDetailDTOS);
        // byat $usersPageResponseDTO  items limitparametertype,fromcodestandardangledegree,again new one通use PageResponseDTO, 按pagination结构return
        // 另outside,byatfilterlogic存in,maybe本timereturn items quantity少at $limit,butisagainhavedownone页.
        $pageResponseDTO = new PageResponseDTO();
        $pageResponseDTO->setPageToken($usersPageResponseDTO->getpageToken());
        $pageResponseDTO->setHasMore($usersPageResponseDTO->getHasMore());
        $pageResponseDTO->setItems($userDepartmentDetailDTOS);
        return $pageResponseDTO->toArray();
    }

    /**
     * 按 usernickname/true名/hand机number/email/departmentpath/position searchuser.
     */
    public function searchDepartmentUser(UserQueryDTO $queryDTO, DelightfulUserAuthorization $authorization): array
    {
        $this->logger->info(sprintf('searchDepartmentUser query:%s', Json::encode($queryDTO->toArray())));

        $dataIsolation = $this->createDataIsolation($authorization);

        $usersForQueryDepartmentPath = [];
        $usersForQueryJobTitle = [];
        // searchpositioncontainsearch词person
        if ($queryDTO->isQueryByJobTitle()) {
            $departmentUsers = $this->departmentUserDomainService->searchDepartmentUsersByJobTitle($queryDTO->getQuery(), $dataIsolation);
            // getuserdetailedinfo
            $userIds = array_column($departmentUsers, 'user_id');
            $userEntities = $this->userDomainService->getUserDetailByUserIds($userIds, $dataIsolation);
            $usersForQueryJobTitle = array_map(static fn ($entity) => $entity->toArray(), $userEntities);
        }

        // 按nicknamesearch
        $usersByNickname = $this->userDomainService->searchUserByNickName($queryDTO->getQuery(), $dataIsolation);
        // 按hand机number/true名search
        $usersByPhoneOrRealName = $this->accountDomainService->searchUserByPhoneOrRealName($queryDTO->getQuery(), $dataIsolation);

        // mergeresult
        $usersForQueryDepartmentPath = array_merge($usersForQueryJobTitle, $usersForQueryDepartmentPath, $usersByNickname, $usersByPhoneOrRealName);
        // go重
        $usersForQueryDepartmentPath = array_values(array_column($usersForQueryDepartmentPath, null, 'user_id'));

        // goexceptAIassistant
        if ($queryDTO->isFilterAgent()) {
            $usersForQueryDepartmentPath = array_filter($usersForQueryDepartmentPath, static fn ($user) => $user['user_type'] !== UserType::Ai->value);
        }

        // settinguserIDsuseatquerydetailedinfo
        $userIds = array_column($usersForQueryDepartmentPath, 'user_id');
        $queryDTO->setUserIds($userIds);

        $usersForQueryDepartmentPath = $this->getUserDetailByIds($queryDTO, $authorization);
        $usersForQueryDepartmentPath['items'] = $this->filterDepartmentOrUserHidden($usersForQueryDepartmentPath['items']);

        return $usersForQueryDepartmentPath;
    }

    public function getUserFriendList(FriendQueryDTO $friendQueryDTO, DelightfulUserAuthorization $authorization): array
    {
        $dataIsolation = $this->createDataIsolation($authorization);
        return $this->userDomainService->getUserFriendList($friendQueryDTO, $dataIsolation);
    }

    public function updateUserOptionByIds(array $userIds, ?UserOption $userOption = null): int
    {
        return $this->userDomainService->updateUserOptionByIds($userIds, $userOption);
    }

    public function getEnvByAuthorization(string $authorization): ?DelightfulEnvironmentEntity
    {
        return $this->delightfulOrganizationEnvDomainService->getEnvironmentEntityByAuthorization($authorization);
    }

    /**
     * Get user details for all organizations under the account from authorization token.
     *
     * @param string $authorization Authorization token
     * @param null|string $organizationCode Optional organization code to filter users
     * @return array Paginated format consistent with existing queries interface
     * @throws Throwable
     */
    public function getUsersDetailByAccountAuthorization(string $authorization, ?string $organizationCode = null): array
    {
        // Get user details list
        $usersDetailDTOList = $this->userDomainService->getUsersDetailByAccountFromAuthorization($authorization, $organizationCode);

        if (empty($usersDetailDTOList)) {
            return PageListAssembler::pageByMysql([], 0, 0, 0);
        }

        // Note: Since this interface is not within RequestContextMiddleware, organization context cannot be obtained
        // Therefore, avatar processing is not performed, and raw data is returned directly
        // Avatar processing requires specific organization context and file service configuration

        // Return paginated format consistent with existing interfaces
        return PageListAssembler::pageByMysql($usersDetailDTOList, 0, 0, count($usersDetailDTOList));
    }

    public function getByUserId(string $userId): ?DelightfulUserEntity
    {
        return $this->userDomainService->getByUserId($userId);
    }

    public function getLoginCodeEnv(string $loginCode): DelightfulEnvironmentEntity
    {
        if (empty($loginCode)) {
            // ifnothave传,that么default取currentenvironment
            $delightfulEnvironmentEntity = $this->delightfulOrganizationEnvDomainService->getCurrentDefaultDelightfulEnv();
        } else {
            $delightfulEnvironmentEntity = $this->delightfulOrganizationEnvDomainService->getEnvironmentEntityByLoginCode($loginCode);
        }
        if ($delightfulEnvironmentEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::LOGIN_FAILED);
        }
        return $delightfulEnvironmentEntity;
    }

    /**
     * whetherallowupdateuserinfo.
     */
    public function getUserUpdatePermission(DelightfulUserAuthorization $userAuthorization): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return di(DelightfulUserDomainExtendInterface::class)->getUserUpdatePermission($dataIsolation);
    }

    /**
     * updateuserinfo.
     */
    public function updateUserInfo(DelightfulUserAuthorization $userAuthorization, UserUpdateDTO $userUpdateDTO): DelightfulUserEntity
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $userDomainExtendService = di(DelightfulUserDomainExtendInterface::class);
        $userDomainExtendService->updateUserInfo($dataIsolation, $userUpdateDTO);
        return $this->getByUserId($dataIsolation->getCurrentUserId());
    }

    /**
     * foruseraddAgentinfo(applicationlayer协调器).
     * @param array<UserDetailDTO> $usersDetailDTOList
     */
    public function addAgentInfoToUsers(Authenticatable $authorization, array $usersDetailDTOList): array
    {
        $aiCodes = [];
        // ifis AI assistant,that么return AI assistant相closeinfoandtoitpermission
        foreach ($usersDetailDTOList as $userDetailDTO) {
            if (! empty($userDetailDTO->getAiCode())) {
                $aiCodes[] = $userDetailDTO->getAiCode();
            }
        }
        // get agentIds
        $agents = $this->delightfulAgentDomainService->getByFlowCodes($aiCodes);
        $flowCodeMapAgentId = [];
        foreach ($agents as $agent) {
            $flowCodeMapAgentId[$agent->getFlowCode()] = $agent->getId();
        }
        $agentIds = array_keys($agents);
        $agentPermissions = [];
        if (! empty($agentIds)) {
            // query user tothisthese agent permission
            $permissionDataIsolation = $this->createPermissionDataIsolation($authorization);
            $agentPermissions = $this->operationPermissionDomainService->getResourceOperationByUserIds(
                $permissionDataIsolation,
                ResourceType::AgentCode,
                [$authorization->getId()],
                $agentIds
            )[$authorization->getId()] ?? [];
        }

        foreach ($usersDetailDTOList as $userDetailDTO) {
            if (! empty($userDetailDTO->getAiCode())) {
                $agentId = $flowCodeMapAgentId[$userDetailDTO->getAiCode()] ?? null;
                // setting agent info
                $userDetailDTO->setAgentInfo(
                    new AgentInfoDTO([
                        'bot_id' => (string) $agentId,
                        'agent_id' => (string) $agentId,
                        'flow_code' => $userDetailDTO->getAiCode(),
                        'user_operation' => ($agentPermissions[$agentId] ?? Operation::None)->value,
                    ])
                );
            }
        }
        return $usersDetailDTOList;
    }

    /**
     * 通讯录andsearch相closeinterface,filterhiddendepartmentandhiddenuser.
     * @param UserDepartmentDetailDTO[]|UserDetailDTO[] $usersDepartmentDetails
     */
    private function filterDepartmentOrUserHidden(array $usersDepartmentDetails): array
    {
        foreach ($usersDepartmentDetails as $key => $userDepartmentDetail) {
            // userwhetherhidden
            if ($userDepartmentDetail->getOption() === UserOption::Hidden) {
                unset($usersDepartmentDetails[$key]);
                continue;
            }
            if ($userDepartmentDetail instanceof UserDetailDTO) {
                // notwantcheckuserdepartmentinfo
                continue;
            }
            $userPathNodes = [];
            foreach ($userDepartmentDetail->getPathNodes() as $pathNode) {
                // user所indepartmentwhetherhidden
                if ($pathNode->getOption() === DepartmentOption::Hidden) {
                    continue;
                }
                $userPathNodes[] = $pathNode;
            }
            $userDepartmentDetail->setPathNodes($userPathNodes);
        }
        return array_values($usersDepartmentDetails);
    }

    /**
     * 读私haveor者公have桶,拿avatar.
     * @return UserDetailDTO[]
     */
    private function getUsersAvatar(array $usersDetail, DataIsolation $dataIsolation): array
    {
        return $this->getUsersAvatarCoordinator($usersDetail, $dataIsolation);
    }

    /**
     * 读私haveor者公have桶,拿avatar(applicationlayer协调器).
     * @param array<UserDetailDTO> $usersDetail
     * @return array<UserDetailDTO>
     */
    private function getUsersAvatarCoordinator(array $usersDetail, DataIsolation $dataIsolation): array
    {
        $fileKeys = array_column($usersDetail, 'avatar_url');
        // 移exceptnullvalue/httpor者 httpsopenhead/lengthless than 32
        $validFileKeys = [];
        foreach ($fileKeys as $fileKey) {
            if (! empty($fileKey) && mb_strlen($fileKey) >= 32 && ! str_starts_with($fileKey, 'http')) {
                $validFileKeys[] = $fileKey;
            }
        }

        // 按organizationgroupfileKeys
        $orgFileKeys = [];
        foreach ($validFileKeys as $fileKey) {
            $orgCode = explode('/', $fileKey, 2)[0] ?? '';
            if (! empty($orgCode)) {
                $orgFileKeys[$orgCode][] = $fileKey;
            }
        }

        // 按organizationbatchquantitygetlink
        $links = [];
        foreach ($orgFileKeys as $orgCode => $fileKeys) {
            $orgLinks = $this->fileDomainService->getLinks($orgCode, $fileKeys);
            $links[] = $orgLinks;
        }
        if (! empty($links)) {
            $links = array_merge(...$links);
        }

        // replace avatar_url
        foreach ($usersDetail as &$user) {
            $avatarUrl = $user['avatar_url'];
            $fileLink = $links[$avatarUrl] ?? null;
            if (isset($fileLink)) {
                $user['avatar_url'] = $fileLink->getUrl();
            }
        }
        return $usersDetail;
    }
}

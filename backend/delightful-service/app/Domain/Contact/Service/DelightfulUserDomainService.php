<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Service;

use App\Domain\Authentication\DTO\LoginResponseDTO;
use App\Domain\Chat\Entity\DelightfulFriendEntity;
use App\Domain\Chat\Entity\ValueObject\FriendStatus;
use App\Domain\Contact\DTO\FriendQueryDTO;
use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\PlatformType;
use App\Domain\Contact\Entity\ValueObject\UserOption;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;
use App\Domain\Token\Entity\DelightfulTokenEntity;
use App\Domain\Token\Entity\ValueObject\DelightfulTokenType;
use App\ErrorCode\ChatErrorCode;
use App\ErrorCode\DelightfulAccountErrorCode;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\Traits\DataIsolationTrait;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\OfficialOrganizationUtil;
use App\Interfaces\Chat\Assembler\UserAssembler;
use App\Interfaces\Chat\DTO\UserDetailDTO;
use Hyperf\Codec\Json;
use Throwable;

class DelightfulUserDomainService extends AbstractContactDomainService
{
    use DataIsolationTrait;

    /**
     * @throws Throwable
     */
    public function addFriend(DataIsolation $dataIsolation, string $friendId): bool
    {
        // check uid and friendId whether存in
        $uid = $dataIsolation->getCurrentUserId();
        $usersInfo = $this->userRepository->getUserByIdsAndOrganizations([$uid, $friendId]);
        $usersInfo = array_column($usersInfo, null, 'user_id');
        if (! isset($usersInfo[$uid], $usersInfo[$friendId])) {
            ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
        }
        // 检测whether已经is好友
        if ($this->friendRepository->isFriend($uid, $friendId)) {
            return true;
        }
        /** @var DelightfulUserEntity $friendUserInfo */
        $friendUserInfo = $usersInfo[$friendId];
        $friendStatus = FriendStatus::Apply;
        if ($friendUserInfo->getUserType() === UserType::Ai) {
            // ifis ai ,直接同意
            $friendStatus = FriendStatus::Agree;
        } else {
            // ifispersoncategory，check他们whether处at同一organization
            $this->assertUserInOrganization($friendId, $dataIsolation->getCurrentOrganizationCode());
        }
        // will好友close系write friend 表.
        $this->friendRepository->insertFriend([
            'id' => IdGenerator::getSnowId(),
            'user_id' => $uid,
            'user_organization_code' => $dataIsolation->getCurrentOrganizationCode(),
            'friend_id' => $friendId,
            'friend_organization_code' => $usersInfo[$friendId]['organization_code'],
            'friend_type' => $friendUserInfo->getUserType(),
            'status' => $friendStatus->value,
            'remarks' => '',
            'extra' => '',
        ]);
        return true;
    }

    /**
     * checkcurrentuserwhetherincurrentorganizationinside,andand账numberisactivatedstatus
     */
    public function assertUserInOrganization(string $userId, string $currentOrganizationCode): void
    {
        $userOrganizations = $this->userRepository->getUserOrganizations($userId);
        if (! in_array($currentOrganizationCode, $userOrganizations, true)) {
            ExceptionBuilder::throw(UserErrorCode::ORGANIZATION_NOT_EXIST);
        }
    }

    /**
     * getuser所属organizationcolumn表.
     * @return string[]
     */
    public function getUserOrganizations(string $userId): array
    {
        return $this->userRepository->getUserOrganizations($userId);
    }

    /**
     * according to delightfulId getuser所属organizationcolumn表.
     * @return string[]
     */
    public function getUserOrganizationsByDelightfulId(string $delightfulId): array
    {
        return $this->userRepository->getUserOrganizationsByDelightfulId($delightfulId);
    }

    public function getByUserId(string $uid): ?DelightfulUserEntity
    {
        return $this->userRepository->getUserById($uid);
    }

    /**
     * @return array<DelightfulUserEntity>
     */
    public function getUserByIds(array $ids, DataIsolation $dataIsolation, array $column = ['*']): array
    {
        return $this->userRepository->getUserByIdsAndOrganizations($ids, [$dataIsolation->getCurrentOrganizationCode()], $column);
    }

    public function getUserByPageToken(string $pageToken = '', int $pageSize = 50): array
    {
        return $this->userRepository->getUserByPageToken($pageToken, $pageSize);
    }

    /**
     * @return array<string, DelightfulUserEntity>
     */
    public function getByUserIds(DataIsolation $dataIsolation, array $userIds): array
    {
        $userIds = array_values(array_unique($userIds));
        return $this->userRepository->getByUserIds($dataIsolation->getCurrentOrganizationCode(), $userIds);
    }

    public function searchFriend(string $keyword): array
    {
        // check uid and friendId whether存in
        [$popular, $latest] = $this->userRepository->searchByKeyword($keyword);
        // 按most受欢迎andmost新加入each取front三
        return $this->getAgents($popular, $latest);
    }

    public function getAgentList(): array
    {
        [$popular, $latest] = $this->userRepository->getSquareAgentList();
        return $this->getAgents($popular, $latest);
    }

    public function getUserById(string $userId): ?DelightfulUserEntity
    {
        return $this->userRepository->getUserById($userId);
    }

    public function getByAiCode(DataIsolation $dataIsolation, string $aiCode): ?DelightfulUserEntity
    {
        $account = $this->accountRepository->getByAiCode($aiCode);
        if (! $account) {
            return null;
        }
        return $this->userRepository->getUserByDelightfulId($dataIsolation, $account->getDelightfulId());
    }

    /**
     * 批quantityaccording to aiCode（flowCode）+ organizationencodinggetassistant user_id.
     * @return array<string, string> return aiCode => userId mapping
     */
    public function getByAiCodes(DataIsolation $dataIsolation, array $aiCodes): array
    {
        if (empty($aiCodes)) {
            return [];
        }

        // 1. according to aiCodes 批quantityget account info
        $accounts = $this->accountRepository->getAccountInfoByAiCodes($aiCodes);
        if (empty($accounts)) {
            return [];
        }

        // 2. 收集 delightful_ids
        $delightfulIds = [];
        $aiCodeToDelightfulIdMap = [];
        foreach ($accounts as $account) {
            $delightfulIds[] = $account->getDelightfulId();
            $aiCodeToDelightfulIdMap[$account->getAiCode()] = $account->getDelightfulId();
        }

        // 3. according to delightful_ids 批quantitygetuserinfo
        $users = $this->userRepository->getUserByDelightfulIds($delightfulIds);
        if (empty($users)) {
            return [];
        }

        // 4. filterorganizationencodingandbuild delightfulId => userId mapping
        $delightfulIdToUserIdMap = [];
        foreach ($users as $user) {
            // 只保留currentorganizationuser
            if ($user->getOrganizationCode() === $dataIsolation->getCurrentOrganizationCode()) {
                $delightfulIdToUserIdMap[$user->getDelightfulId()] = $user->getUserId();
            }
        }

        // 5. buildfinal aiCode => userId mapping
        $result = [];
        foreach ($aiCodeToDelightfulIdMap as $aiCode => $delightfulId) {
            if (isset($delightfulIdToUserIdMap[$delightfulId])) {
                $result[$aiCode] = $delightfulIdToUserIdMap[$delightfulId];
            }
        }

        return $result;
    }

    /**
     * @return array<UserDetailDTO>
     */
    public function getUserDetailByUserIds(array $userIds, DataIsolation $dataIsolation): array
    {
        $userDetails = $this->getUserDetailByUserIdsInDelightful($userIds);
        // 按organizationfilteruser
        return array_filter($userDetails, static fn ($user) => $user->getOrganizationCode() === $dataIsolation->getCurrentOrganizationCode());
    }

    /**
     * according touserIDanduserorganizationcolumn表queryuserdetail，according touserorganization决定filterstrategy.
     * @param array $userIds userIDarray
     * @param array $userOrganizations currentuser拥haveorganizationencodingarray
     * @return array<UserDetailDTO>
     */
    public function getUserDetailByUserIdsWithOrgCodes(array $userIds, array $userOrganizations): array
    {
        // get官方organizationencoding
        $officialOrganizationCode = OfficialOrganizationUtil::getOfficialOrganizationCode();

        // mergeuserorganizationand官方organization
        $orgCodes = array_filter(array_unique(array_merge($userOrganizations, [$officialOrganizationCode])));

        // from user表拿基本info，support多organizationquery
        $users = $this->userRepository->getUserByIdsAndOrganizations($userIds, $orgCodes);

        // checkcurrentuserwhether拥have官方organization
        $hasOfficialOrganization = in_array($officialOrganizationCode, $userOrganizations, true);

        // according touserwhether拥have官方organizationcome决定filterstrategy
        if (! $hasOfficialOrganization) {
            // ifusernothave官方organization，filter掉官方organizationnonAIuser
            $users = array_filter($users, static function (DelightfulUserEntity $user) use ($officialOrganizationCode) {
                // ifnotis官方organization，直接保留
                if ($user->getOrganizationCode() !== $officialOrganizationCode) {
                    return true;
                }
                // ifis官方organization，只保留AIuser
                return $user->getUserType() === UserType::Ai;
            });
        }

        if (empty($users)) {
            return [];
        }

        // parseavataretcinfo
        $delightfulIds = array_column($users, 'delightful_id');
        // from account 表拿hand机number真名etcinfo
        $accounts = $this->accountRepository->getAccountInfoByDelightfulIds($delightfulIds);
        return UserAssembler::getUsersDetail($users, $accounts);
    }

    /**
     * 按nicknamesearchuser.
     */
    public function searchUserByNickName(string $query, DataIsolation $dataIsolation): array
    {
        return $this->userRepository->searchByNickName($query, $dataIsolation->getCurrentOrganizationCode());
    }

    /**
     * searchusernickname（alldelightful平台检索）.
     */
    public function searchUserByNickNameInDelightful(string $query): array
    {
        return $this->userRepository->searchByNickNameInDelightful($query);
    }

    /**
     * will flowCodes settingto friendQueryDTO middle,andreturn flowCodewhetheris该user好友.
     * @return array<string, DelightfulFriendEntity>
     */
    public function getUserAgentFriendsList(FriendQueryDTO $friendQueryDTO, DataIsolation $dataIsolation): array
    {
        $userIdToFlowCodeMaps = $this->setUserIdsByAiCodes($friendQueryDTO, $dataIsolation);
        $friendList = $this->friendRepository->getFriendList($friendQueryDTO, $dataIsolation->getCurrentUserId());
        $flowFriends = [];
        // use flowCode 置换 friendId
        foreach ($friendList as $friend) {
            $friendId = $friend->getFriendId();
            if (isset($userIdToFlowCodeMaps[$friendId])) {
                $friendFlowCode = $userIdToFlowCodeMaps[$friendId];
                $flowFriends[$friendFlowCode] = $friend;
            }
        }
        return $flowFriends;
    }

    /**
     * @return DelightfulFriendEntity[]
     */
    public function getUserFriendList(FriendQueryDTO $friendQueryDTO, DataIsolation $dataIsolation): array
    {
        $this->setUserIdsByAiCodes($friendQueryDTO, $dataIsolation);
        return $this->friendRepository->getFriendList($friendQueryDTO, $dataIsolation->getCurrentUserId());
    }

    /**
     * @return DelightfulUserEntity[]
     */
    public function getUserByIdsWithoutOrganization(array $ids, array $column = ['*']): array
    {
        return $this->userRepository->getUserByIdsAndOrganizations($ids, [], $column);
    }

    public function addUserManual(string $userId, string $userManual): void
    {
        $this->userRepository->addUserManual($userId, $userManual);
    }

    public function updateUserOptionByIds(array $userIds, ?UserOption $userOption = null): int
    {
        if (empty($userIds)) {
            return 0;
        }
        return $this->userRepository->updateUserOptionByIds($userIds, $userOption);
    }

    /**
     * 麦吉userbody系downlogin校验.
     * @return LoginResponseDTO[]
     */
    public function delightfulUserLoginCheck(string $authorization, DelightfulEnvironmentEntity $delightfulEnvironmentEntity, ?string $delightfulOrganizationCode = null): array
    {
        // generatecache键andlock键
        $cacheKey = md5(sprintf('OrganizationUserLogin:auth:%s:env:%s:', $authorization, $delightfulEnvironmentEntity->getId()));
        $lockKey = $this->generateLockKey(PlatformType::Delightful, $authorization);

        // 尝试fromcachegetresult
        $cachedResult = $this->getCachedLoginCheckResult($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // 加lockhandle，防止andhairrequest
        $owner = $this->acquireLock($lockKey);

        try {
            // handle麦吉usersystemtoken，getdelightfulIdanduserId
            $tokenDTO = new DelightfulTokenEntity();
            $tokenDTO->setType(DelightfulTokenType::Account);
            $tokenDTO->setToken($authorization);
            $delightfulUserToken = $this->delightfulTokenRepository->getTokenEntity($tokenDTO);

            if ($delightfulUserToken === null) {
                ExceptionBuilder::throw(ChatErrorCode::AUTHORIZATION_INVALID);
            }

            $delightfulId = $delightfulUserToken->getTypeRelationValue();

            // queryuserandhandleorganizationclose系，query麦吉user
            $delightfulUserEntities = $this->userRepository->getUserByDelightfulIds([$delightfulId]);
            if (empty($delightfulUserEntities)) {
                ExceptionBuilder::throw(ChatErrorCode::USER_NOT_CREATE_ACCOUNT);
            }

            // buildreturnresult
            $loginResponses = [];
            foreach ($delightfulUserEntities as $delightfulUserEntity) {
                $currentOrgCode = $delightfulUserEntity->getOrganizationCode();
                $loginResponseDTO = new LoginResponseDTO();
                $loginResponseDTO->setDelightfulId($delightfulUserEntity->getDelightfulId())
                    ->setDelightfulUserId($delightfulUserEntity->getUserId())
                    ->setDelightfulOrganizationCode($currentOrgCode)
                    ->setThirdPlatformOrganizationCode($currentOrgCode)
                    ->setThirdPlatformUserId($delightfulId);

                $loginResponses[] = $loginResponseDTO;
            }
            // cacheresult
            $this->cacheLoginCheckResult($cacheKey, $loginResponses);

            return $loginResponses;
        } finally {
            // releaselock
            $this->locker->release($lockKey, $owner);
        }
    }

    /**
     * @return array<UserDetailDTO>
     */
    public function getUserDetailByUserIdsInDelightful(array $userIds): array
    {
        // from user表拿基本info
        $users = $this->userRepository->getUserByIdsAndOrganizations($userIds);
        // parseavataretcinfo
        $delightfulIds = array_column($users, 'delightful_id');
        // from account 表拿hand机number真名etcinfo
        $accounts = $this->accountRepository->getAccountInfoByDelightfulIds($delightfulIds);
        return UserAssembler::getUsersDetail($users, $accounts);
    }

    public function searchUserByPhoneOrRealNameInDelightful(string $query): array
    {
        $accounts = $this->accountRepository->searchUserByPhoneOrRealName($query);
        if (empty($accounts)) {
            return [];
        }
        $delightfulIds = array_column($accounts, 'delightful_id');
        return $this->userRepository->getUserByAccountsInDelightful($delightfulIds);
    }

    /**
     * according touserIDgetuserhand机number.
     */
    public function getUserPhoneByUserId(string $userId): ?string
    {
        // 先getuserinfo
        $user = $this->userRepository->getUserById($userId);
        if ($user === null) {
            return null;
        }

        // pass delightful_id get账numberinfo
        $account = $this->accountRepository->getAccountInfoByDelightfulId($user->getDelightfulId());
        if ($account === null) {
            return null;
        }

        return $account->getPhone();
    }

    /**
     * Batch get user phones by user IDs.
     *
     * @param array $userIds Array of user IDs
     * @return array Array with structure [user_id => phone]
     */
    public function batchGetUserPhonesByIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        // 1. Batch get user info to get delightful_ids
        $users = $this->userRepository->getUserByIdsAndOrganizations($userIds);
        if (empty($users)) {
            return [];
        }

        // 2. Extract delightful_ids and create user_id => delightful_id mapping
        $delightfulIds = [];
        $userIdToDelightfulIdMap = [];
        foreach ($users as $user) {
            $delightfulIds[] = $user->getDelightfulId();
            $userIdToDelightfulIdMap[$user->getUserId()] = $user->getDelightfulId();
        }

        // 3. Batch get account info by delightful_ids
        $accounts = $this->accountRepository->getAccountInfoByDelightfulIds($delightfulIds);
        if (empty($accounts)) {
            return [];
        }

        // 4. Create delightful_id => phone mapping
        $delightfulIdToPhoneMap = [];
        foreach ($accounts as $account) {
            $delightfulIdToPhoneMap[$account->getDelightfulId()] = $account->getPhone();
        }

        // 5. Build final user_id => phone mapping
        $result = [];
        foreach ($userIdToDelightfulIdMap as $userId => $delightfulId) {
            $result[$userId] = $delightfulIdToPhoneMap[$delightfulId] ?? '';
        }

        return $result;
    }

    /**
     * Get user details for all organizations under the account from authorization token.
     *
     * @param string $authorization Authorization token
     * @param null|string $organizationCode Optional organization code to filter users
     * @return array<UserDetailDTO> List of user details
     * @throws Throwable
     */
    public function getUsersDetailByAccountFromAuthorization(string $authorization, ?string $organizationCode = null): array
    {
        // Verify if token is of account type
        $tokenDTO = new DelightfulTokenEntity();
        $tokenDTO->setType(DelightfulTokenType::Account);
        $tokenDTO->setToken($tokenDTO->getDelightfulShortToken($authorization));
        $delightfulToken = $this->delightfulTokenRepository->getTokenEntity($tokenDTO);

        if ($delightfulToken === null || $delightfulToken->getType() !== DelightfulTokenType::Account) {
            ExceptionBuilder::throw(ChatErrorCode::AUTHORIZATION_INVALID);
        }

        // Get account's delightful_id
        $delightfulId = $delightfulToken->getTypeRelationValue();

        // Get users under this account, optionally filtered by organization
        if ($organizationCode) {
            // If organization code is provided, only get users from that organization
            $delightfulUserEntities = $this->userRepository->getUsersByDelightfulIdAndOrganizationCode([$delightfulId], $organizationCode);
        } else {
            // If no organization code, get users from all organizations
            $delightfulUserEntities = $this->userRepository->getUserByDelightfulIds([$delightfulId]);
        }

        if (empty($delightfulUserEntities)) {
            return [];
        }

        // Get account information
        $accountEntity = $this->accountRepository->getAccountInfoByDelightfulId($delightfulId);
        if ($accountEntity === null) {
            return [];
        }

        // Use existing UserAssembler to build user details
        return UserAssembler::getUsersDetail($delightfulUserEntities, [$accountEntity]);
    }

    /**
     * check两userwhetheris好友close系.
     */
    public function isFriend(string $userId, string $friendId): bool
    {
        return $this->friendRepository->isFriend($userId, $friendId);
    }

    protected function setUserIdsByAiCodes(FriendQueryDTO $friendQueryDTO, DataIsolation $dataIsolation): array
    {
        $userIdToFlowCodeMaps = [];
        if (! empty($friendQueryDTO->getAiCodes())) {
            // according to ai code query delightful id
            $accounts = $this->accountRepository->getAccountInfoByAiCodes($friendQueryDTO->getAiCodes());
            $delightfulIds = array_column($accounts, 'delightful_id');
            // 转user Id
            $users = $this->userRepository->getUserByAccountsAndOrganization($delightfulIds, $dataIsolation->getCurrentOrganizationCode());
            $userIds = array_column($users, 'user_id');
            $friendQueryDTO->setUserIds($userIds);
            $accounts = array_column($accounts, null, 'delightful_id');
            foreach ($users as $user) {
                /** @var null|AccountEntity $accountEntity */
                $accountEntity = $accounts[$user['delightful_id']] ?? null;
                if (isset($accountEntity)) {
                    $userIdToFlowCodeMaps[$user['user_id']] = $accountEntity->getAiCode();
                }
            }
        }
        return $userIdToFlowCodeMaps;
    }

    protected function getAgents(array $popular, array $latest): array
    {
        // according todelightful_id,查账numberdetail
        $delightfulIds[] = array_column($popular, 'delightful_id');
        $delightfulIds[] = array_column($latest, 'delightful_id');
        $delightfulIds = array_values(array_unique(array_merge(...$delightfulIds)));
        $accounts = $this->accountRepository->getAccountInfoByDelightfulIds($delightfulIds);
        return [
            'popular' => UserAssembler::getAgentList($popular, $accounts),
            'latest' => UserAssembler::getAgentList($latest, $accounts),
        ];
    }

    /**
     * generatelock键.
     */
    protected function generateLockKey(PlatformType $platformType, string $authorization): string
    {
        return sprintf('get%sUserInfoFromKeewood:%s', $platformType->name, md5($authorization));
    }

    /**
     * cachelogin校验result.
     * @param array<LoginResponseDTO> $result
     */
    protected function cacheLoginCheckResult(string $cacheKey, array $result): void
    {
        // forcompatiblecache，needwillDTOobjectconvertforarraystorage
        $cacheDTOArray = array_map(static function ($dto) {
            return $dto->toArray();
        }, $result);
        $this->redis->setex($cacheKey, 60, Json::encode($cacheDTOArray));
    }

    /**
     * get互斥lock
     * @return string lock所have者标识
     */
    protected function acquireLock(string $lockKey): string
    {
        try {
            $owner = random_bytes(10);
            $this->locker->mutexLock($lockKey, $owner, 10);
            return $owner;
        } catch (Throwable) {
            ExceptionBuilder::throw(DelightfulAccountErrorCode::REQUEST_TOO_FREQUENT);
        }
    }

    /**
     * release互斥lock
     */
    protected function releaseLock(string $lockKey, string $owner): void
    {
        $this->locker->release($lockKey, $owner);
    }

    /**
     * getcachelogin校验result.
     * @return null|array<LoginResponseDTO>
     */
    private function getCachedLoginCheckResult(string $cacheKey): ?array
    {
        $loginCache = $this->redis->get($cacheKey);
        if (! empty($loginCache)) {
            $cachedData = Json::decode($loginCache);
            // willcachemiddlearrayconvertforDTOobject
            return array_map(static function ($item) {
                return new LoginResponseDTO($item);
            }, $cachedData);
        }

        return null;
    }
}

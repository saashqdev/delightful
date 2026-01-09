<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Facade;

use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\UserIdType;
use App\Domain\Contact\Entity\ValueObject\UserOption;

interface DelightfulUserRepositoryInterface
{
    public function getSquareAgentList(): array;

    public function createUser(DelightfulUserEntity $userDTO): DelightfulUserEntity;

    /**
     * @param DelightfulUserEntity[] $userDTOs
     * @return DelightfulUserEntity[]
     */
    public function createUsers(array $userDTOs): array;

    public function getUserById(string $id): ?DelightfulUserEntity;

    public function getUserByDelightfulId(DataIsolation $dataIsolation, string $id): ?DelightfulUserEntity;

    /**
     * @return DelightfulUserEntity[]
     */
    public function getUserByIdsAndOrganizations(array $ids, array $organizationCodes = [], array $column = ['*']): array;

    /**
     * @return array<string, DelightfulUserEntity>
     */
    public function getUserByPageToken(string $pageToken = '', int $pageSize = 50): array;

    /**
     * @return array<string, DelightfulUserEntity>
     */
    public function getByUserIds(string $organizationCode, array $userIds): array;

    /**
     * according to userIdType,generate对应type的value.
     */
    public function getUserIdByType(UserIdType $userIdType, string $addStr): string;

    /**
     * @return string[]
     */
    public function getUserOrganizations(string $userId): array;

    /**
     * according to delightfulId getuser所属的organizationcolumntable.
     * @return string[]
     */
    public function getUserOrganizationsByDelightfulId(string $delightfulId): array;

    public function getUserByAiCode(string $aiCode): array;

    public function searchByKeyword(string $keyword): array;

    public function insertUser(array $userInfo): void;

    public function getUserByMobile(string $mobile): ?array;

    public function getUserByMobileWithStateCode(string $stateCode, string $mobile): ?array;

    public function getUserByMobilesWithStateCode(string $stateCode, array $mobiles): array;

    public function getUserByMobiles(array $mobiles): array;

    public function updateDataById(string $userId, array $data): int;

    public function deleteUserByIds(array $ids): int;

    public function getUserByAccountAndOrganization(string $accountId, string $organizationCode): ?DelightfulUserEntity;

    public function getUserByAccountsAndOrganization(array $accountIds, string $organizationCode): array;

    public function getUserByAccountsInDelightful(array $accountIds): array;

    public function searchByNickName(string $nickName, string $organizationCode): array;

    public function searchByNickNameInDelightful(string $nickName): array;

    public function getUserByIds(array $ids): array;

    public function saveUser(DelightfulUserEntity $userDTO): DelightfulUserEntity;

    public function addUserManual(string $userId, string $userManual): void;

    /**
     * @return DelightfulUserEntity[]
     */
    public function getUsersByDelightfulIdAndOrganizationCode(array $delightfulIds, string $organizationCode): array;

    /**
     * @return DelightfulUserEntity[]
     */
    public function getUserByDelightfulIds(array $delightfulIds): array;

    /**
     * @return DelightfulUserEntity[]
     */
    public function getUserAllUserIds(string $userId): array;

    public function updateUserOptionByIds(array $ids, ?UserOption $userOption = null): int;

    public function getDelightfulIdsByUserIds(array $userIds): array;
}

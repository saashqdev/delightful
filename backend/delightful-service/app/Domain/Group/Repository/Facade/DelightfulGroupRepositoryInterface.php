<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Group\Repository\Facade;

use App\Domain\Chat\DTO\PageResponseDTO\GroupsPageResponseDTO;
use App\Domain\Group\Entity\DelightfulGroupEntity;

interface DelightfulGroupRepositoryInterface
{
    // creategroup
    public function createGroup(DelightfulGroupEntity $delightfulGroupDTO): DelightfulGroupEntity;

    // 批quantityquerygroupinformation

    /**
     * @return DelightfulGroupEntity[]
     */
    public function getGroupsByIds(array $groupIds): array;

    // 修改groupinformation
    public function updateGroupById(string $groupId, array $data): int;

    public function getGroupInfoById(string $groupId, ?string $organizationCode = null): ?DelightfulGroupEntity;

    /**
     * @return DelightfulGroupEntity[]
     */
    public function getGroupsInfoByIds(array $groupIds, ?string $organizationCode = null, bool $keyById = false): array;

    public function addUsersToGroup(DelightfulGroupEntity $delightfulGroupEntity, array $userIds): bool;

    public function getGroupUserList(string $groupId, string $pageToken, ?string $organizationCode = null, ?array $columns = ['*']): array;

    public function getUserGroupList(string $pageToken, string $userId, ?int $pageSize = null): GroupsPageResponseDTO;

    public function getGroupIdsByUserIds(array $userIds): array;

    public function getGroupUserCount(string $groupId): int;

    public function removeUsersFromGroup(DelightfulGroupEntity $delightfulGroupEntity, array $userIds): int;

    public function deleteGroup(DelightfulGroupEntity $delightfulGroupEntity): int;

    // userwhetheringroupmiddle
    public function isUserInGroup(string $groupId, string $userId): bool;

    /**
     * userwhetheringroupmiddle.
     * @param array<string> $userIds
     */
    public function isUsersInGroup(string $groupId, array $userIds): bool;

    public function transferGroupOwner(string $groupId, string $oldGroupOwner, string $newGroupOwner): bool;

    public function getUserGroupRelations(array $groupIds, array $userIds): array;
}

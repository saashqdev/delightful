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
    // 创建群组
    public function createGroup(DelightfulGroupEntity $magicGroupDTO): DelightfulGroupEntity;

    // 批量查询群组信息

    /**
     * @return DelightfulGroupEntity[]
     */
    public function getGroupsByIds(array $groupIds): array;

    // 修改群组信息
    public function updateGroupById(string $groupId, array $data): int;

    public function getGroupInfoById(string $groupId, ?string $organizationCode = null): ?DelightfulGroupEntity;

    /**
     * @return DelightfulGroupEntity[]
     */
    public function getGroupsInfoByIds(array $groupIds, ?string $organizationCode = null, bool $keyById = false): array;

    public function addUsersToGroup(DelightfulGroupEntity $magicGroupEntity, array $userIds): bool;

    public function getGroupUserList(string $groupId, string $pageToken, ?string $organizationCode = null, ?array $columns = ['*']): array;

    public function getUserGroupList(string $pageToken, string $userId, ?int $pageSize = null): GroupsPageResponseDTO;

    public function getGroupIdsByUserIds(array $userIds): array;

    public function getGroupUserCount(string $groupId): int;

    public function removeUsersFromGroup(DelightfulGroupEntity $magicGroupEntity, array $userIds): int;

    public function deleteGroup(DelightfulGroupEntity $magicGroupEntity): int;

    // 用户是否在群组中
    public function isUserInGroup(string $groupId, string $userId): bool;

    /**
     * 用户是否在群组中.
     * @param array<string> $userIds
     */
    public function isUsersInGroup(string $groupId, array $userIds): bool;

    public function transferGroupOwner(string $groupId, string $oldGroupOwner, string $newGroupOwner): bool;

    public function getUserGroupRelations(array $groupIds, array $userIds): array;
}

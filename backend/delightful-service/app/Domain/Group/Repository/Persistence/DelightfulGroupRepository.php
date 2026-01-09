<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Group\Repository\Persistence;

use App\Domain\Chat\DTO\Group\GroupDTO;
use App\Domain\Chat\DTO\PageResponseDTO\GroupsPageResponseDTO;
use App\Domain\Contact\Repository\Facade\DelightfulUserRepositoryInterface;
use App\Domain\Group\Entity\DelightfulGroupEntity;
use App\Domain\Group\Entity\ValueObject\GroupStatusEnum;
use App\Domain\Group\Entity\ValueObject\GroupUserRoleEnum;
use App\Domain\Group\Entity\ValueObject\GroupUserStatusEnum;
use App\Domain\Group\Repository\Facade\DelightfulGroupRepositoryInterface;
use App\Domain\Group\Repository\Persistence\Model\DelightfulGroupModel;
use App\Domain\Group\Repository\Persistence\Model\DelightfulGroupUserModel;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Chat\Assembler\GroupAssembler;
use Hyperf\Database\Model\Builder;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\DbConnection\Db;

readonly class DelightfulGroupRepository implements DelightfulGroupRepositoryInterface
{
    public function __construct(
        private DelightfulGroupModel $groupModel,
        private DelightfulGroupUserModel $groupUserModel,
        private DelightfulUserRepositoryInterface $userRepository,
    ) {
    }

    // create群组
    public function createGroup(DelightfulGroupEntity $delightfulGroupDTO): DelightfulGroupEntity
    {
        $groupInfo = $delightfulGroupDTO->toArray();
        if (empty($groupInfo['id'])) {
            $groupInfo['id'] = IdGenerator::getSnowId();
        }
        $this->groupModel::query()->create($groupInfo);
        $delightfulGroupDTO->setId((string) $groupInfo['id']);
        return GroupAssembler::getGroupEntity($groupInfo);
    }

    // 批量query群组information

    /**
     * @return DelightfulGroupEntity[]
     */
    public function getGroupsByIds(array $groupIds): array
    {
        $groups = $this->groupModel::query()->whereIn('id', $groupIds);
        $groups = Db::select($groups->toSql(), $groups->getBindings());
        $groupEntities = [];
        foreach ($groups as $group) {
            $groupEntities[] = GroupAssembler::getGroupEntity($group);
        }
        return $groupEntities;
    }

    public function updateGroupById(string $groupId, array $data): int
    {
        return $this->groupModel::query()->where('id', $groupId)->update($data);
    }

    public function getGroupInfoById(string $groupId, ?string $organizationCode = null): ?DelightfulGroupEntity
    {
        $groupInfo = $this->groupModel::query()->where('id', $groupId);
        $groupInfo = Db::select($groupInfo->toSql(), $groupInfo->getBindings())[0] ?? null;
        if (empty($groupInfo)) {
            return null;
        }
        return GroupAssembler::getGroupEntity($groupInfo);
    }

    /**
     * @return DelightfulGroupEntity[]
     */
    public function getGroupsInfoByIds(array $groupIds, ?string $organizationCode = null, bool $keyById = false): array
    {
        $groupIds = array_unique($groupIds);
        if (empty($groupIds)) {
            return [];
        }
        $groups = $this->groupModel::query()->whereIn('id', $groupIds);
        $groups = Db::select($groups->toSql(), $groups->getBindings());
        $groupEntities = [];
        foreach ($groups as $group) {
            $entity = GroupAssembler::getGroupEntity($group);
            if ($keyById) {
                $groupEntities[$entity->getId()] = $entity;
            } else {
                $groupEntities[] = $entity;
            }
        }
        return $groupEntities;
    }

    public function addUsersToGroup(DelightfulGroupEntity $delightfulGroupEntity, array $userIds): bool
    {
        $groupId = $delightfulGroupEntity->getId();
        $groupOwner = $delightfulGroupEntity->getGroupOwner();
        $users = $this->userRepository->getUserByIdsAndOrganizations($userIds, [], ['user_id', 'user_type', 'organization_code']);
        $users = array_column($users, null, 'user_id');
        $time = date('Y-m-d H:i:s');
        $groupUsers = [];
        // 批量getuserinformation
        foreach ($userIds as $userId) {
            $user = $users[$userId] ?? null;
            if (empty($user)) {
                continue;
            }
            if ($groupOwner === $userId) {
                $userRole = GroupUserRoleEnum::OWNER->value;
            } else {
                $userRole = GroupUserRoleEnum::MEMBER->value;
            }
            $groupUsers[] = [
                'id' => IdGenerator::getSnowId(),
                'group_id' => $groupId,
                'user_id' => $userId,
                'user_role' => $userRole,
                'user_type' => $user['user_type'],
                'status' => GroupUserStatusEnum::Normal->value,
                'created_at' => $time,
                'updated_at' => $time,
                'organization_code' => $user['organization_code'],
            ];
        }
        // 批量往群组中添加user
        ! empty($groupUsers) && $this->groupUserModel::query()->insert($groupUsers);
        return true;
    }

    public function getGroupUserList(string $groupId, string $pageToken, ?string $organizationCode = null, ?array $columns = ['*']): array
    {
        $userList = $this->groupUserModel::query()
            ->select($columns)
            ->where('group_id', $groupId);
        $userList = Db::select($userList->toSql(), $userList->getBindings());
        // 将时间还原成时间戳
        foreach ($userList as &$user) {
            ! empty($user['created_at']) && $user['created_at'] = strtotime($user['created_at']);
            ! empty($user['updated_at']) && $user['updated_at'] = strtotime($user['updated_at']);
        }
        return $userList;
    }

    public function getUserGroupList(string $pageToken, string $userId, ?int $pageSize = null): GroupsPageResponseDTO
    {
        $userGroupList = $this->groupUserModel::query()
            ->where('user_id', $userId)
            ->when($pageToken, function (Builder $query) use ($pageToken) {
                $query->offset((int) $pageToken);
            })
            ->when($pageSize, function (Builder $query) use ($pageSize) {
                $query->limit($pageSize);
            });
        $userGroupList = Db::select($userGroupList->toSql(), $userGroupList->getBindings());
        $groupIds = array_values(array_unique(array_column($userGroupList, 'group_id')));
        $groups = $this->groupModel::query()->whereIn('id', $groupIds);
        $groups = Db::select($groups->toSql(), $groups->getBindings());
        $items = [];
        foreach ($groups as $group) {
            $items[] = new GroupDTO($group);
        }
        $hasMore = count($groupIds) === $pageSize ? true : false;
        $pageToken = $hasMore ? (string) ((int) $pageToken + $pageSize) : '';
        return new GroupsPageResponseDTO([
            'items' => $items,
            'has_more' => $hasMore,
            'page_token' => $pageToken,
        ]);
    }

    public function getGroupIdsByUserIds(array $userIds): array
    {
        $groupUsers = DelightfulGroupUserModel::query()->whereIn('user_id', $userIds);
        $groupUsers = Db::select($groupUsers->toSql(), $groupUsers->getBindings());
        $list = [];
        foreach ($groupUsers as $groupUser) {
            $groupUserId = $groupUser['user_id'];
            $list[$groupUserId][] = $groupUser['group_id'];
        }
        return $list;
    }

    public function getGroupUserCount(string $groupId): int
    {
        return $this->groupUserModel::query()->where('group_id', $groupId)->count();
    }

    /**
     * 将user从群组中移除.
     */
    public function removeUsersFromGroup(DelightfulGroupEntity $delightfulGroupEntity, array $userIds): int
    {
        return $this->groupUserModel::query()
            ->where('group_id', $delightfulGroupEntity->getId())
            ->whereIn('user_id', $userIds)
            ->delete();
    }

    public function deleteGroup(DelightfulGroupEntity $delightfulGroupEntity): int
    {
        return $this->groupModel::query()
            ->where('id', $delightfulGroupEntity->getId())
            ->update([
                'group_status' => GroupStatusEnum::Disband->value,
            ]);
    }

    public function isUserInGroup(string $groupId, string $userId): bool
    {
        return $this->groupUserModel::query()
            ->where('group_id', $groupId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function isUsersInGroup(string $groupId, array $userIds): bool
    {
        return $this->groupUserModel::query()
            ->where('group_id', $groupId)
            ->whereIn('user_id', $userIds)
            ->exists();
    }

    /**
     * get关联的user群组.
     * @param array<string> $groupIds
     * @param array<string> $userIds
     */
    public function getUserGroupRelations(array $groupIds, array $userIds): array
    {
        $res = $this->groupUserModel::query()
            ->whereIn('group_id', $groupIds)
            ->whereIn('user_id', $userIds);
        $res = Db::select($res->toSql(), $res->getBindings());
        return empty($res) ? [] : $res;
    }

    #[Transactional]
    public function transferGroupOwner(string $groupId, string $oldGroupOwner, string $newGroupOwner): bool
    {
        $this->groupUserModel::query()
            ->where('group_id', $groupId)
            ->where('user_id', $oldGroupOwner)
            ->update([
                'user_role' => GroupUserRoleEnum::MEMBER->value,
            ]);
        $this->groupUserModel::query()
            ->where('group_id', $groupId)
            ->where('user_id', $newGroupOwner)
            ->update([
                'user_role' => GroupUserRoleEnum::OWNER->value,
            ]);
        $this->groupModel::query()
            ->where('id', $groupId)
            ->update([
                'group_owner' => $newGroupOwner,
            ]);
        return true;
    }
}

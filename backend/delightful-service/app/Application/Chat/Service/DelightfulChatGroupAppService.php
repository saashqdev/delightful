<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Chat\DTO\PageResponseDTO\GroupsPageResponseDTO;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Event\Group\GroupDeleteEvent;
use App\Domain\Chat\Service\DelightfulControlDomainService;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulDepartmentDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentUserDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\Group\Entity\DelightfulGroupEntity;
use App\Domain\Group\Entity\ValueObject\GroupLimitEnum;
use App\Domain\Group\Entity\ValueObject\GroupStatusEnum;
use App\Domain\Group\Service\DelightfulGroupDomainService;
use App\Domain\Permission\Service\OperationPermissionDomainService;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Delightful\AsyncEvent\AsyncEventUtil;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Coroutine\co;

class DelightfulChatGroupAppService extends AbstractAppService
{
    public function __construct(
        protected readonly DelightfulGroupDomainService $delightfulGroupDomainService,
        protected readonly DelightfulUserDomainService $delightfulUserDomainService,
        protected readonly DelightfulDepartmentUserDomainService $delightfulDepartmentUserDomainService,
        protected readonly DelightfulDepartmentDomainService $delightfulDepartmentDomainService,
        protected readonly DelightfulControlMessageAppService $controlMessageAppService,
        protected readonly DelightfulConversationDomainService $delightfulConversationDomainService,
        protected readonly DelightfulControlDomainService $delightfulControlDomainService,
        protected LoggerInterface $logger,
        protected readonly DelightfulAgentDomainService $delightfulAgentDomainService,
        protected readonly OperationPermissionDomainService $operationPermissionDomainService,
        protected readonly DelightfulUserContactAppService $delightfulUserContactAppService
    ) {
        try {
            $this->logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get(get_class($this));
        } catch (Throwable) {
        }
    }

    // creategroup chat
    public function createChatGroup(array $groupUserIds, array $inputDepartmentIds, DelightfulUserAuthorization $userAuthorization, DelightfulGroupEntity $delightfulGroupDTO): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $chatGroupUserNumLimit = GroupLimitEnum::NormalGroup->value;
        $groupUserIds[] = $dataIsolation->getCurrentUserId();
        $groupUserIds = array_values(array_unique($groupUserIds));
        $users = $this->getGroupAddUsers($groupUserIds, $dataIsolation, $inputDepartmentIds, $chatGroupUserNumLimit);
        $userIds = array_column($users, 'user_id');
        // 确定group chat名称
        $groupName = $this->getGroupName($delightfulGroupDTO, $userIds, $dataIsolation);
        $delightfulGroupDTO->setGroupName($groupName);
        $delightfulGroupDTO->setMemberLimit($chatGroupUserNumLimit);
        // creategroup chat
        Db::beginTransaction();
        try {
            $groupEntity = $this->delightfulGroupDomainService->createGroup($delightfulGroupDTO, $dataIsolation);
            $seqContent = [
                'operate_user_id' => $dataIsolation->getCurrentUserId(),
                'group_id' => $groupEntity->getId(),
                'user_ids' => $userIds,
                'group_name' => $groupName,
                'group_avatar' => $delightfulGroupDTO->getGroupAvatar(),
                'group_owner_id' => $dataIsolation->getCurrentUserId(),
            ];
            $createGroupSeq = $this->addGroupUsers(
                $userIds,
                $seqContent,
                $groupEntity,
                $dataIsolation,
                ControlMessageType::GroupCreate
            );
            Db::commit();
        } catch (Throwable$exception) {
            Db::rollBack();
            ExceptionBuilder::throw(ChatErrorCode::GROUP_CREATE_ERROR, throwable: $exception);
        }
        // 为操作者及时return seq
        return $this->noticeGroupChangeSeq($createGroupSeq);
    }

    /**
     * group chat加人.
     */
    public function groupAddUsers(array $groupAddUserIds, array $inputDepartmentIds, DelightfulUserAuthorization $userAuthorization, DelightfulGroupEntity $delightfulGroupDTO): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        // 检查group chat是否存在
        $groupId = $delightfulGroupDTO->getId();
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        // 当前group chat人数
        $groupUserCount = $this->delightfulGroupDomainService->getGroupUserCount($groupId);
        // 最大人数限制减去当前人数
        $chatGroupUserNumLimit = GroupLimitEnum::NormalGroup->value;
        $chatGroupUserNumLimit -= $groupUserCount;
        // 获取本次需要添加的群成员 (综合 指定的user_id + departmentid下的user)
        $wantJoinUsers = $this->getGroupAddUsers($groupAddUserIds, $dataIsolation, $inputDepartmentIds, $chatGroupUserNumLimit);
        $wantJoinUserIds = array_column($wantJoinUsers, 'user_id');
        // 判断哪些user已经在group chat中
        $groupUsers = $this->delightfulGroupDomainService->getGroupUserList($groupId, '', $dataIsolation, ['user_id']);
        // 已经存在于group chat中的userid
        $existUserIds = array_column($groupUsers, 'user_id');
        $needAddGroupUserIds = array_diff($wantJoinUserIds, $existUserIds);
        if (empty($needAddGroupUserIds)) {
            ExceptionBuilder::throw(ChatErrorCode::USER_ALREADY_IN_GROUP);
        }
        Db::beginTransaction();
        try {
            $seqContent = [
                'operate_user_id' => $dataIsolation->getCurrentUserId(),
                'group_id' => $groupEntity->getId(),
                'user_ids' => $needAddGroupUserIds,
            ];
            $addUsersSeq = $this->addGroupUsers(
                $needAddGroupUserIds,
                $seqContent,
                $groupEntity,
                $dataIsolation,
                ControlMessageType::GroupUsersAdd
            );
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            ExceptionBuilder::throw(ChatErrorCode::GROUP_UPDATE_ERROR, throwable: $exception);
        }
        // 为操作者及时return seq
        return $this->noticeGroupChangeSeq($addUsersSeq);
    }

    public function groupKickUsers(
        DelightfulUserAuthorization $userAuthorization,
        DelightfulGroupEntity $delightfulGroupDTO,
        array $userIds,
        ControlMessageType $controlMessageType
    ): array {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        // 检查group chat是否存在
        $groupId = $delightfulGroupDTO->getId();
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        // 不能踢出群主
        $groupOwner = $groupEntity->getGroupOwner();
        if (in_array($groupOwner, $userIds, true)) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_CANNOT_KICK_OWNER);
        }
        $userSeq = $this->groupRemoveUsers($dataIsolation, $groupEntity, $userIds, $controlMessageType);
        return $this->noticeGroupChangeSeq($userSeq);
    }

    public function leaveGroupConversation(
        DelightfulUserAuthorization $userAuthorization,
        DelightfulGroupEntity $delightfulGroupDTO,
        array $userIds,
        ControlMessageType $controlMessageType
    ): array {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        // 检查group chat是否存在
        $groupId = $delightfulGroupDTO->getId();
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        // 群主不能退出group chat,需要先转移群主身份
        $groupOwner = $groupEntity->getGroupOwner();
        if ($groupOwner === $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_TRANSFER_OWNER_BEFORE_LEAVE);
        }
        // 幂等.检查user是否已离开group
        $isInGroup = $this->delightfulGroupDomainService->isUserInGroup($groupId, $dataIsolation->getCurrentUserId());
        if (! $isInGroup) {
            // returnuser上次离开group chat的 seq
            $seqEntity = $this->delightfulGroupDomainService->getGroupControlSeq($groupEntity, $dataIsolation, ControlMessageType::GroupUsersRemove);
            if (isset($seqEntity)) {
                return $this->noticeGroupChangeSeq($seqEntity);
            }
        }
        // 退出group chat
        $userSeq = $this->groupRemoveUsers($dataIsolation, $groupEntity, $userIds, $controlMessageType);
        return $this->noticeGroupChangeSeq($userSeq);
    }

    public function deleteGroup(DelightfulUserAuthorization $userAuthorization, DelightfulGroupEntity $delightfulGroupDTO): array
    {
        // 获取所有群成员
        $groupUsers = $this->delightfulGroupDomainService->getGroupUserList(
            $delightfulGroupDTO->getId(),
            '',
            $this->createDataIsolation($userAuthorization),
            ['user_id']
        );
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $userIds = array_column($groupUsers, 'user_id');
        $controlMessageType = ControlMessageType::GroupDisband;
        // 检查group chat是否存在
        $groupId = $delightfulGroupDTO->getId();
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        // 检查group是否已解散
        if ($groupEntity->getGroupStatus() === GroupStatusEnum::Disband) {
            // 找到该user的解散groupseq
            $seqEntity = $this->delightfulGroupDomainService->getGroupControlSeq($groupEntity, $dataIsolation, ControlMessageType::GroupDisband);
            // 如果已经存在group chat解散的 seq,则直接return
            if (isset($seqEntity)) {
                return $this->noticeGroupChangeSeq($seqEntity);
            }
        }
        // 只能群主解散group chat
        $groupOwner = $groupEntity->getGroupOwner();
        if ($groupOwner !== $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_ONLY_OWNER_CAN_DISBAND);
        }
        Db::beginTransaction();
        try {
            $userSeq = $this->groupRemoveUsers($dataIsolation, $groupEntity, $userIds, $controlMessageType);
            // deletegroup chat
            $this->delightfulGroupDomainService->deleteGroup($groupEntity);
            Db::commit();
        } catch (BusinessException $exception) {
            Db::rollBack();
            throw $exception;
        } catch (Throwable $exception) {
            Db::rollBack();
            ExceptionBuilder::throw(ChatErrorCode::GROUP_UPDATE_ERROR, throwable: $exception);
        }

        AsyncEventUtil::dispatch(new GroupDeleteEvent($delightfulGroupDTO->getId()));

        return $this->noticeGroupChangeSeq($userSeq);
    }

    public function GroupUpdateInfo(DelightfulUserAuthorization $userAuthorization, DelightfulGroupEntity $delightfulGroupDTO): array
    {
        if (empty($delightfulGroupDTO->getGroupAvatar()) && empty($delightfulGroupDTO->getGroupName())) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR, 'chat.common.param_error', ['param' => 'group_name, group_avatar']);
        }
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        // 检查group chat是否存在
        $groupId = $delightfulGroupDTO->getId();
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        $groupEntity->setGroupAvatar($delightfulGroupDTO->getGroupAvatar());
        $groupEntity->setGroupName($delightfulGroupDTO->getGroupName());
        Db::beginTransaction();
        try {
            // 更新群info
            $groupEntity = $this->delightfulGroupDomainService->GroupUpdateInfo($delightfulGroupDTO, $dataIsolation);
            // generate群更新的 seq 并分发
            $seqContent = [
                'operate_user_id' => $dataIsolation->getCurrentUserId(),
                'group_id' => $groupEntity->getId(),
                'user_ids' => [],
            ];
            if ($delightfulGroupDTO->getGroupName() !== null) {
                $seqContent['group_name'] = $delightfulGroupDTO->getGroupName();
            }
            if ($delightfulGroupDTO->getGroupAvatar() !== null) {
                $seqContent['group_avatar'] = $delightfulGroupDTO->getGroupAvatar();
            }
            $userSeq = $this->createAndDispatchOperateGroupUsersSeq(
                $seqContent,
                $groupEntity,
                $dataIsolation,
                ControlMessageType::GroupUpdate
            );
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            ExceptionBuilder::throw(ChatErrorCode::GROUP_UPDATE_ERROR, throwable: $exception);
        }
        return $this->noticeGroupChangeSeq($userSeq);
    }

    public function getGroupsInfo(array $groupIds, DelightfulUserAuthorization $userAuthorization): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulGroupDomainService->getGroupsInfoByIds($groupIds, $dataIsolation);
    }

    /**
     * 获取群的成员列表.
     */
    public function getGroupUserList(string $groupId, string $pageToken, DelightfulUserAuthorization $userAuthorization): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulGroupDomainService->getGroupUserList($groupId, $pageToken, $dataIsolation);
    }

    /**
     * 获取user的群列表.
     */
    public function getUserGroupList(string $pageToken, DelightfulUserAuthorization $userAuthorization, int $pageSize): GroupsPageResponseDTO
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulGroupDomainService->getUserGroupList($pageToken, $dataIsolation, $pageSize);
    }

    public function groupTransferOwner(DelightfulGroupEntity $delightfulGroupDTO, DelightfulUserAuthorization $userAuthorization): array
    {
        // 检查group chat是否存在
        $groupId = $delightfulGroupDTO->getId();
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        Db::beginTransaction();
        try {
            // 转让群主
            $this->delightfulGroupDomainService->transferGroupOwner($groupEntity, $dataIsolation, $delightfulGroupDTO);
            // generate群主转让的 seq
            $seqContent = [
                'operate_user_id' => $dataIsolation->getCurrentUserId(),
                'group_id' => $groupEntity->getId(),
                'old_owner_user_id' => $groupEntity->getGroupOwner(),
                'new_owner_user_id' => $delightfulGroupDTO->getGroupOwner(),
            ];
            $userSeq = $this->createAndDispatchOperateGroupUsersSeq(
                $seqContent,// 全员notify
                $groupEntity,
                $dataIsolation,
                ControlMessageType::GroupOwnerChange
            );
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            ExceptionBuilder::throw(ChatErrorCode::GROUP_UPDATE_ERROR, throwable: $exception);
        }
        // 为操作者及时return seq
        return $this->noticeGroupChangeSeq($userSeq);
    }

    /**
     * group chat减人.
     */
    protected function groupRemoveUsers(
        DataIsolation $dataIsolation,
        DelightfulGroupEntity $groupEntity,
        array $userIds,
        ControlMessageType $controlMessageType
    ): DelightfulSeqEntity {
        // querygroup chat中的user
        $groupUsers = $this->delightfulGroupDomainService->getGroupUserList($groupEntity->getId(), '', $dataIsolation, ['user_id']);
        $groupUsers = array_column($groupUsers, 'user_id');
        // 判断要移除的user是否在group chat中
        $removeUserIds = array_intersect($userIds, $groupUsers);
        if (empty($removeUserIds)) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NO_USER_TO_REMOVE);
        }
        Db::beginTransaction();
        try {
            // 往group chat中减少user
            $this->delightfulGroupDomainService->removeUsersFromGroup($groupEntity, $removeUserIds);
            // 移除这些user的conversation窗口
            $this->delightfulConversationDomainService->batchDeleteGroupConversationByUserIds($groupEntity, $removeUserIds);
            // generate群成员减少的seq
            $seqContent = ['user_ids' => $removeUserIds, 'group_id' => $groupEntity->getId(), 'operate_user_id' => $dataIsolation->getCurrentUserId()];
            $groupUserRemoveSeq = $this->createAndDispatchOperateGroupUsersSeq($seqContent, $groupEntity, $dataIsolation, $controlMessageType);
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            ExceptionBuilder::throw(ChatErrorCode::GROUP_UPDATE_ERROR, throwable: $exception);
        }
        // 为操作者及时return seq
        return $groupUserRemoveSeq;
    }

    /**
     * 获取本次需要添加的群成员.
     * @return DelightfulUserEntity[]
     */
    private function getGroupAddUsers(array $needAddGroupUserIds, DataIsolation $dataIsolation, array $inputDepartmentIds, int $chatGroupUserNumLimit): array
    {
        if (! empty($inputDepartmentIds)) {
            $departmentIds = $this->delightfulDepartmentDomainService->getAllChildrenByDepartmentIds($inputDepartmentIds, $dataIsolation);
        } else {
            $departmentIds = [];
        }
        // 目前只支持添加同organization的user
        $groupAddUsers = $this->delightfulUserDomainService->getUserByIds($needAddGroupUserIds, $dataIsolation, ['user_id', 'nickname']);
        // 按department获取user
        if (! empty($departmentIds)) {
            $departmentUsers = $this->delightfulDepartmentUserDomainService->getDepartmentUsersByDepartmentIds(
                $departmentIds,
                $dataIsolation,
                $chatGroupUserNumLimit + 1,
                fields: ['user_id']
            );
        } else {
            $departmentUsers = [];
        }
        // 去重
        $groupAddUsers = array_values(array_column(array_merge($departmentUsers, $groupAddUsers), null, 'user_id'));
        if (count($groupAddUsers) > $chatGroupUserNumLimit) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_USER_NUM_LIMIT_ERROR);
        }
        return $groupAddUsers;
    }

    private function getGroupName(DelightfulGroupEntity $delightfulGroupDTO, array $userIds, DataIsolation $dataIsolation): string
    {
        // 如果group chat名称为null,获取群主 + 20 个群成员的nickname
        if (empty($delightfulGroupDTO->getGroupName())) {
            $someUserIds = array_slice($userIds, 0, 20);
            $someUserIds[] = $dataIsolation->getCurrentUserId();
            $someUsers = $this->delightfulUserDomainService->getUserByIds($someUserIds, $dataIsolation, ['user_id', 'nickname']);
            $someUsers = array_column($someUsers, null, 'user_id');
            // 将群主的nickname放在第一个
            $ownerNickname = $someUsers[$dataIsolation->getCurrentUserId()]['nickname'] ?? '';
            unset($someUsers[$dataIsolation->getCurrentUserId()]);
            $nicknames = array_column($someUsers, 'nickname');
            array_unshift($nicknames, $ownerNickname);
            $groupName = implode(',', $nicknames);
            // 长度超过20个字符后,用...代替
            if (mb_strlen($groupName) > 20) {
                $groupName = mb_substr($groupName, 0, 20) . '...';
            }
            return $groupName;
        }
        return $delightfulGroupDTO->getGroupName();
    }

    private function addGroupUsers(
        array $userIds,
        array $structure,
        DelightfulGroupEntity $groupEntity,
        DataIsolation $dataIsolation,
        ControlMessageType $controlMessageType
    ): DelightfulSeqEntity {
        // 往group chat中添加user
        $this->delightfulGroupDomainService->addUsersToGroup($groupEntity, $userIds);
        // 为新增的成员createconversation窗口
        $this->delightfulConversationDomainService->batchCreateGroupConversationByUserIds($groupEntity, $userIds);
        return $this->createAndDispatchOperateGroupUsersSeq($structure, $groupEntity, $dataIsolation, $controlMessageType);
    }

    /**
     * create并分发操作群成员的 seq.
     */
    private function createAndDispatchOperateGroupUsersSeq(
        array $seqContent,
        DelightfulGroupEntity $groupEntity,
        DataIsolation $dataIsolation,
        ControlMessageType $controlMessageType
    ): DelightfulSeqEntity {
        // 为当前操作者,generate群成员变更Seq,并经由 mq 分发给群成员
        $groupUserChangeSeq = $this->delightfulGroupDomainService->createGroupUserChangeSeq($dataIsolation, $groupEntity, $seqContent, $controlMessageType);
        $seqCreateEvent = $this->delightfulControlDomainService->getControlSeqCreatedEvent($groupUserChangeSeq);
        $this->delightfulControlDomainService->dispatchSeq($seqCreateEvent);
        return $groupUserChangeSeq;
    }

    private function noticeGroupChangeSeq(DelightfulSeqEntity $seqEntity): array
    {
        // 协程notifyuser其他设备,放在事务外面
        co(function () use ($seqEntity) {
            $this->delightfulControlDomainService->pushControlSequence($seqEntity);
        });
        // return为当前操作者generate的 seq
        return SeqAssembler::getClientSeqStruct($seqEntity)->toArray();
    }
}

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
use BeDelightful\AsyncEvent\AsyncEventUtil;
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
        // certaingroup chatname
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
        // foroperationauthorando clockreturn seq
        return $this->noticeGroupChangeSeq($createGroupSeq);
    }

    /**
     * group chataddperson.
     */
    public function groupAddUsers(array $groupAddUserIds, array $inputDepartmentIds, DelightfulUserAuthorization $userAuthorization, DelightfulGroupEntity $delightfulGroupDTO): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        // checkgroup chatwhetherexistsin
        $groupId = $delightfulGroupDTO->getId();
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        // currentgroup chatpersoncount
        $groupUserCount = $this->delightfulGroupDomainService->getGroupUserCount($groupId);
        // mostbigpersoncountlimitsubtractgocurrentpersoncount
        $chatGroupUserNumLimit = GroupLimitEnum::NormalGroup->value;
        $chatGroupUserNumLimit -= $groupUserCount;
        // getthistimeneedaddgroupmember (comprehensive fingersetuser_id + departmentiddownuser)
        $wantJoinUsers = $this->getGroupAddUsers($groupAddUserIds, $dataIsolation, $inputDepartmentIds, $chatGroupUserNumLimit);
        $wantJoinUserIds = array_column($wantJoinUsers, 'user_id');
        // judgewhichtheseuseralreadyalreadyingroup chatmiddle
        $groupUsers = $this->delightfulGroupDomainService->getGroupUserList($groupId, '', $dataIsolation, ['user_id']);
        // alreadyalready existsinatgroup chatmiddleuserid
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
        // foroperationauthorando clockreturn seq
        return $this->noticeGroupChangeSeq($addUsersSeq);
    }

    public function groupKickUsers(
        DelightfulUserAuthorization $userAuthorization,
        DelightfulGroupEntity $delightfulGroupDTO,
        array $userIds,
        ControlMessageType $controlMessageType
    ): array {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        // checkgroup chatwhetherexistsin
        $groupId = $delightfulGroupDTO->getId();
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        // notcankickoutgroup owner
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
        // checkgroup chatwhetherexistsin
        $groupId = $delightfulGroupDTO->getId();
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        // group ownernotcanexitgroup chat,needfirsttransfergroup ownerbodyshare
        $groupOwner = $groupEntity->getGroupOwner();
        if ($groupOwner === $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_TRANSFER_OWNER_BEFORE_LEAVE);
        }
        // poweretc.checkuserwhetheralreadyleavegroup
        $isInGroup = $this->delightfulGroupDomainService->isUserInGroup($groupId, $dataIsolation->getCurrentUserId());
        if (! $isInGroup) {
            // returnuseruptimeleavegroup chat seq
            $seqEntity = $this->delightfulGroupDomainService->getGroupControlSeq($groupEntity, $dataIsolation, ControlMessageType::GroupUsersRemove);
            if (isset($seqEntity)) {
                return $this->noticeGroupChangeSeq($seqEntity);
            }
        }
        // exitgroup chat
        $userSeq = $this->groupRemoveUsers($dataIsolation, $groupEntity, $userIds, $controlMessageType);
        return $this->noticeGroupChangeSeq($userSeq);
    }

    public function deleteGroup(DelightfulUserAuthorization $userAuthorization, DelightfulGroupEntity $delightfulGroupDTO): array
    {
        // get havegroupmember
        $groupUsers = $this->delightfulGroupDomainService->getGroupUserList(
            $delightfulGroupDTO->getId(),
            '',
            $this->createDataIsolation($userAuthorization),
            ['user_id']
        );
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $userIds = array_column($groupUsers, 'user_id');
        $controlMessageType = ControlMessageType::GroupDisband;
        // checkgroup chatwhetherexistsin
        $groupId = $delightfulGroupDTO->getId();
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        // checkgroupwhetheralreadydissolve
        if ($groupEntity->getGroupStatus() === GroupStatusEnum::Disband) {
            // findtotheuserdissolvegroupseq
            $seqEntity = $this->delightfulGroupDomainService->getGroupControlSeq($groupEntity, $dataIsolation, ControlMessageType::GroupDisband);
            // ifalreadyalready existsingroup chatdissolve seq,thendirectlyreturn
            if (isset($seqEntity)) {
                return $this->noticeGroupChangeSeq($seqEntity);
            }
        }
        // onlycangroup ownerdissolvegroup chat
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
        // checkgroup chatwhetherexistsin
        $groupId = $delightfulGroupDTO->getId();
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        $groupEntity->setGroupAvatar($delightfulGroupDTO->getGroupAvatar());
        $groupEntity->setGroupName($delightfulGroupDTO->getGroupName());
        Db::beginTransaction();
        try {
            // updategroupinfo
            $groupEntity = $this->delightfulGroupDomainService->GroupUpdateInfo($delightfulGroupDTO, $dataIsolation);
            // generategroupmorenew seq andminutehair
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
     * getgroupmembercolumntable.
     */
    public function getGroupUserList(string $groupId, string $pageToken, DelightfulUserAuthorization $userAuthorization): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulGroupDomainService->getGroupUserList($groupId, $pageToken, $dataIsolation);
    }

    /**
     * getusergroupcolumntable.
     */
    public function getUserGroupList(string $pageToken, DelightfulUserAuthorization $userAuthorization, int $pageSize): GroupsPageResponseDTO
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulGroupDomainService->getUserGroupList($pageToken, $dataIsolation, $pageSize);
    }

    public function groupTransferOwner(DelightfulGroupEntity $delightfulGroupDTO, DelightfulUserAuthorization $userAuthorization): array
    {
        // checkgroup chatwhetherexistsin
        $groupId = $delightfulGroupDTO->getId();
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $groupEntity = $this->delightfulGroupDomainService->getGroupInfoById($groupId, $dataIsolation);
        if ($groupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        Db::beginTransaction();
        try {
            // transferletgroup owner
            $this->delightfulGroupDomainService->transferGroupOwner($groupEntity, $dataIsolation, $delightfulGroupDTO);
            // generategroup ownertransferlet seq
            $seqContent = [
                'operate_user_id' => $dataIsolation->getCurrentUserId(),
                'group_id' => $groupEntity->getId(),
                'old_owner_user_id' => $groupEntity->getGroupOwner(),
                'new_owner_user_id' => $delightfulGroupDTO->getGroupOwner(),
            ];
            $userSeq = $this->createAndDispatchOperateGroupUsersSeq(
                $seqContent,// allmembernotify
                $groupEntity,
                $dataIsolation,
                ControlMessageType::GroupOwnerChange
            );
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            ExceptionBuilder::throw(ChatErrorCode::GROUP_UPDATE_ERROR, throwable: $exception);
        }
        // foroperationauthorando clockreturn seq
        return $this->noticeGroupChangeSeq($userSeq);
    }

    /**
     * group chatsubtractperson.
     */
    protected function groupRemoveUsers(
        DataIsolation $dataIsolation,
        DelightfulGroupEntity $groupEntity,
        array $userIds,
        ControlMessageType $controlMessageType
    ): DelightfulSeqEntity {
        // querygroup chatmiddleuser
        $groupUsers = $this->delightfulGroupDomainService->getGroupUserList($groupEntity->getId(), '', $dataIsolation, ['user_id']);
        $groupUsers = array_column($groupUsers, 'user_id');
        // judgewantmoveexceptuserwhetheringroup chatmiddle
        $removeUserIds = array_intersect($userIds, $groupUsers);
        if (empty($removeUserIds)) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NO_USER_TO_REMOVE);
        }
        Db::beginTransaction();
        try {
            // togroup chatmiddledecreaseuser
            $this->delightfulGroupDomainService->removeUsersFromGroup($groupEntity, $removeUserIds);
            // moveexceptthistheseuserconversationwindow
            $this->delightfulConversationDomainService->batchDeleteGroupConversationByUserIds($groupEntity, $removeUserIds);
            // generategroupmemberdecreaseseq
            $seqContent = ['user_ids' => $removeUserIds, 'group_id' => $groupEntity->getId(), 'operate_user_id' => $dataIsolation->getCurrentUserId()];
            $groupUserRemoveSeq = $this->createAndDispatchOperateGroupUsersSeq($seqContent, $groupEntity, $dataIsolation, $controlMessageType);
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            ExceptionBuilder::throw(ChatErrorCode::GROUP_UPDATE_ERROR, throwable: $exception);
        }
        // foroperationauthorando clockreturn seq
        return $groupUserRemoveSeq;
    }

    /**
     * getthistimeneedaddgroupmember.
     * @return DelightfulUserEntity[]
     */
    private function getGroupAddUsers(array $needAddGroupUserIds, DataIsolation $dataIsolation, array $inputDepartmentIds, int $chatGroupUserNumLimit): array
    {
        if (! empty($inputDepartmentIds)) {
            $departmentIds = $this->delightfulDepartmentDomainService->getAllChildrenByDepartmentIds($inputDepartmentIds, $dataIsolation);
        } else {
            $departmentIds = [];
        }
        // itemfrontonlysupportaddsameorganizationuser
        $groupAddUsers = $this->delightfulUserDomainService->getUserByIds($needAddGroupUserIds, $dataIsolation, ['user_id', 'nickname']);
        // bydepartmentgetuser
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
        // goreload
        $groupAddUsers = array_values(array_column(array_merge($departmentUsers, $groupAddUsers), null, 'user_id'));
        if (count($groupAddUsers) > $chatGroupUserNumLimit) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_USER_NUM_LIMIT_ERROR);
        }
        return $groupAddUsers;
    }

    private function getGroupName(DelightfulGroupEntity $delightfulGroupDTO, array $userIds, DataIsolation $dataIsolation): string
    {
        // ifgroup chatnamefornull,getgroup owner + 20 groupmembernickname
        if (empty($delightfulGroupDTO->getGroupName())) {
            $someUserIds = array_slice($userIds, 0, 20);
            $someUserIds[] = $dataIsolation->getCurrentUserId();
            $someUsers = $this->delightfulUserDomainService->getUserByIds($someUserIds, $dataIsolation, ['user_id', 'nickname']);
            $someUsers = array_column($someUsers, null, 'user_id');
            // willgroup ownernicknameputinfirst
            $ownerNickname = $someUsers[$dataIsolation->getCurrentUserId()]['nickname'] ?? '';
            unset($someUsers[$dataIsolation->getCurrentUserId()]);
            $nicknames = array_column($someUsers, 'nickname');
            array_unshift($nicknames, $ownerNickname);
            $groupName = implode(',', $nicknames);
            // lengthexceedspass20characterback,use...replace
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
        // togroup chatmiddleadduser
        $this->delightfulGroupDomainService->addUsersToGroup($groupEntity, $userIds);
        // fornewmembercreateconversationwindow
        $this->delightfulConversationDomainService->batchCreateGroupConversationByUserIds($groupEntity, $userIds);
        return $this->createAndDispatchOperateGroupUsersSeq($structure, $groupEntity, $dataIsolation, $controlMessageType);
    }

    /**
     * createandminutehairoperationasgroupmember seq.
     */
    private function createAndDispatchOperateGroupUsersSeq(
        array $seqContent,
        DelightfulGroupEntity $groupEntity,
        DataIsolation $dataIsolation,
        ControlMessageType $controlMessageType
    ): DelightfulSeqEntity {
        // forcurrentoperationauthor,generategroupmemberchangemoreSeq,andalreadyby mq minutehairgivegroupmember
        $groupUserChangeSeq = $this->delightfulGroupDomainService->createGroupUserChangeSeq($dataIsolation, $groupEntity, $seqContent, $controlMessageType);
        $seqCreateEvent = $this->delightfulControlDomainService->getControlSeqCreatedEvent($groupUserChangeSeq);
        $this->delightfulControlDomainService->dispatchSeq($seqCreateEvent);
        return $groupUserChangeSeq;
    }

    private function noticeGroupChangeSeq(DelightfulSeqEntity $seqEntity): array
    {
        // coroutinenotifyuserotherdevice,putintransactionoutsidesurface
        co(function () use ($seqEntity) {
            $this->delightfulControlDomainService->pushControlSequence($seqEntity);
        });
        // returnforcurrentoperationauthorgenerate seq
        return SeqAssembler::getClientSeqStruct($seqEntity)->toArray();
    }
}

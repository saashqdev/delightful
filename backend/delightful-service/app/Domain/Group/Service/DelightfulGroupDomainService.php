<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Group\Service;

use App\Domain\Chat\DTO\Message\ControlMessage\GroupCreateMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\GroupInfoUpdateMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\GroupOwnerChangeMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\GroupUserAddMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\GroupUserRemoveMessage;
use App\Domain\Chat\DTO\PageResponseDTO\GroupsPageResponseDTO;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Service\AbstractDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Group\Entity\DelightfulGroupEntity;
use App\Domain\Group\Entity\ValueObject\GroupStatusEnum;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\Traits\DataIsolationTrait;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Hyperf\Codec\Json;
use Hyperf\DbConnection\Db;
use Throwable;

class DelightfulGroupDomainService extends AbstractDomainService
{
    use DataIsolationTrait;

    // creategroup
    public function createGroup(DelightfulGroupEntity $delightfulGroupDTO, DataIsolation $dataIsolation): DelightfulGroupEntity
    {
        $delightfulGroupDTO->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $delightfulGroupDTO->setGroupOwner($dataIsolation->getCurrentUserId());
        $delightfulGroupDTO->setGroupNotice('');
        $delightfulGroupDTO->setGroupTag('');
        return $this->delightfulGroupRepository->createGroup($delightfulGroupDTO);
    }

    public function addUsersToGroup(DelightfulGroupEntity $delightfulGroupEntity, array $userIds): bool
    {
        return $this->delightfulGroupRepository->addUsersToGroup($delightfulGroupEntity, $userIds);
    }

    // 减少群member
    public function removeUsersFromGroup(DelightfulGroupEntity $delightfulGroupEntity, array $userIds): int
    {
        // todo if是群主离开,need转移群主
        return $this->delightfulGroupRepository->removeUsersFromGroup($delightfulGroupEntity, $userIds);
    }

    public function GroupUpdateInfo(DelightfulGroupEntity $delightfulGroupDTO, DataIsolation $dataIsolation): DelightfulGroupEntity
    {
        $updateData = [];
        if (! empty($delightfulGroupDTO->getGroupName())) {
            $updateData['group_name'] = $delightfulGroupDTO->getGroupName();
        }
        if (! empty($delightfulGroupDTO->getGroupAvatar())) {
            $updateData['group_avatar'] = $delightfulGroupDTO->getGroupAvatar();
        }
        $this->delightfulGroupRepository->updateGroupById($delightfulGroupDTO->getId(), $updateData);
        $delightfulGroupEntity = $this->delightfulGroupRepository->getGroupInfoById($delightfulGroupDTO->getId(), $dataIsolation->getCurrentOrganizationCode());
        if ($delightfulGroupEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_NOT_FOUND);
        }
        return $delightfulGroupEntity;
    }

    public function getGroupInfoById(string $groupId, DataIsolation $dataIsolation): ?DelightfulGroupEntity
    {
        return $this->delightfulGroupRepository->getGroupInfoById($groupId, $dataIsolation->getCurrentOrganizationCode());
    }

    public function getGroupUserCount($groupId): int
    {
        return $this->delightfulGroupRepository->getGroupUserCount($groupId);
    }

    /**
     * @return DelightfulGroupEntity[]
     */
    public function getGroupsInfoByIds(array $groupIds, DataIsolation $dataIsolation, bool $keyById = false): array
    {
        return $this->delightfulGroupRepository->getGroupsInfoByIds($groupIds, $dataIsolation->getCurrentOrganizationCode(), $keyById);
    }

    public function getGroupUserList(string $groupId, string $pageToken, DataIsolation $dataIsolation, ?array $columns = ['*']): array
    {
        return $this->delightfulGroupRepository->getGroupUserList($groupId, $pageToken, $dataIsolation->getCurrentOrganizationCode(), $columns);
    }

    public function getGroupIdsByUserIds(array $userIds): array
    {
        return $this->delightfulGroupRepository->getGroupIdsByUserIds($userIds);
    }

    public function isUserInGroup(string $groupId, string $userId): bool
    {
        return $this->delightfulGroupRepository->isUserInGroup($groupId, $userId);
    }

    public function isUsersInGroup(string $groupId, array $userIds): bool
    {
        return $this->delightfulGroupRepository->isUsersInGroup($groupId, $userIds);
    }

    public function getUserGroupList(string $pageToken, DataIsolation $dataIsolation, int $pageSize): GroupsPageResponseDTO
    {
        $groupPageResponseDTO = $this->delightfulGroupRepository->getUserGroupList($pageToken, $dataIsolation->getCurrentUserId(), $pageSize);
        $groupDTOS = $groupPageResponseDTO->getItems();
        // userin这些group chatmiddle的sessionid
        $groupIds = array_column($groupDTOS, 'id');
        $conversations = $this->delightfulConversationRepository->getConversationsByReceiveIds($dataIsolation->getCurrentUserId(), $groupIds);
        /** @var DelightfulConversationEntity[] $conversations */
        $conversations = array_column($conversations, null, 'receive_id');
        $groupList = [];
        foreach ($groupDTOS as $groupDTO) {
            // returngroup chat对应的sessionid
            $groupId = $groupDTO->getId();
            $groupDTO->setConversationId($conversations[$groupId]->getId() ?? null);
            $groupList[] = $groupDTO;
        }
        $groupPageResponseDTO->setItems($groupList);
        return $groupPageResponseDTO;
    }

    /**
     * @throws Throwable
     */
    public function handlerMQGroupUserChangeSeq(DelightfulSeqEntity $groupUserChangeSeqEntity): void
    {
        Db::beginTransaction();
        try {
            $controlMessageType = $groupUserChangeSeqEntity->getSeqType();
            // 批quantitygenerate群member变moremessage
            /** @var GroupCreateMessage|GroupInfoUpdateMessage|GroupOwnerChangeMessage|GroupUserAddMessage|GroupUserRemoveMessage $content */
            $content = $groupUserChangeSeqEntity->getContent();
            $groupId = $content->getGroupId();
            $groupEntity = $this->delightfulGroupRepository->getGroupInfoById($groupId);
            if ($groupEntity === null) {
                return;
            }
            $userIds = $this->getGroupUpdateReceiveUsers($groupId);

            $changeUserIds = [];
            if (method_exists($content, 'getUserIds')) {
                foreach ($content->getUserIds() as $userId) {
                    $changeUserIds[] = $userId;
                }
            }
            $content = $content->toArray();
            // pass protobuf message结构,createdelightful chat的object,为弃use protobuf 做准备
            if (in_array($controlMessageType, [ControlMessageType::GroupUsersRemove, ControlMessageType::GroupDisband], true)) {
                // 这些user已经from群membertablemiddle移except,but是他们also未收tobe移except的message
                $userIds = array_values(array_unique(array_merge($userIds, $changeUserIds)));
                if ($controlMessageType === ControlMessageType::GroupDisband) {
                    // 解散group chat,所have人all是be移except的.这within减少streamquantityconsume.
                    $content['user_ids'] = [];
                }
            }
            $this->batchCreateGroupUserSeqEntity($userIds, $controlMessageType, $content);
            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function createGroupUserChangeSeq(
        DataIsolation $dataIsolation,
        DelightfulGroupEntity $groupEntity,
        array $seqContent,
        ControlMessageType $controlMessageType
    ): DelightfulSeqEntity {
        // returnsessionid,方便front端操作
        $userConversations = $this->getGroupUserConversationsByUserIds([$dataIsolation->getCurrentUserId()], $groupEntity->getId());
        $seqContent['conversation_id'] = $userConversations[$dataIsolation->getCurrentUserId()] ?? '';
        $seqEntity = $this->getGroupChangeSeqEntity($dataIsolation, $groupEntity, $seqContent, $controlMessageType);
        return $this->delightfulSeqRepository->createSequence($seqEntity->toArray());
    }

    public function deleteGroup(DelightfulGroupEntity $delightfulGroupEntity): int
    {
        return $this->delightfulGroupRepository->deleteGroup($delightfulGroupEntity);
    }

    public function getGroupControlSeq(DelightfulGroupEntity $delightfulGroupEntity, DataIsolation $dataIsolation, ControlMessageType $controlMessageType): ?DelightfulSeqEntity
    {
        // 群sessioninfo
        $conversation = $this->delightfulConversationRepository->getConversationsByReceiveIds($dataIsolation->getCurrentUserId(), [$delightfulGroupEntity->getId()])[0] ?? [];
        if (empty($conversation)) {
            return null;
        }
        return $this->delightfulSeqRepository->getConversationSeqByType(
            $dataIsolation->getCurrentDelightfulId(),
            $conversation->getId(),
            $controlMessageType
        );
    }

    public function transferGroupOwner(DelightfulGroupEntity $groupEntity, DataIsolation $dataIsolation, DelightfulGroupEntity $delightfulGroupDTO): bool
    {
        // checkuserwhether是群主
        $oldGroupOwner = $groupEntity->getGroupOwner();
        if ($oldGroupOwner !== $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(ChatErrorCode::GROUP_ONLY_OWNER_CAN_TRANSFER);
        }
        // checkbe转让的userwhetheringroup chatmiddle
        $groupId = $groupEntity->getId();
        $newOwnerUserId = $delightfulGroupDTO->getGroupOwner();
        if (! $this->isUserInGroup($groupId, $newOwnerUserId)) {
            ExceptionBuilder::throw(ChatErrorCode::USER_NOT_FOUND);
        }
        // 转让群主
        return $this->delightfulGroupRepository->transferGroupOwner($groupId, $oldGroupOwner, $newOwnerUserId);
    }

    protected function getGroupChangeSeqEntity(DataIsolation $dataIsolation, DelightfulGroupEntity $groupEntity, array $seqContent, ControlMessageType $controlMessageType): DelightfulSeqEntity
    {
        $id = (string) IdGenerator::getSnowId();
        $time = date('Y-m-d H:i:s');
        $seqData = [
            'id' => $id,
            'organization_code' => $groupEntity->getOrganizationCode(),
            'object_type' => ConversationType::User->value,
            'object_id' => $dataIsolation->getCurrentDelightfulId(),
            'seq_id' => $id,
            'seq_type' => $controlMessageType->value,
            'content' => Json::encode($seqContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'receive_list' => '',
            'delightful_message_id' => '',
            'message_id' => $id,
            'refer_message_id' => '',
            'sender_message_id' => '',
            'conversation_id' => $seqContent['conversation_id'] ?? '',
            'status' => DelightfulMessageStatus::Read->value, // 控制messagenotneed已读回执
            'created_at' => $time,
            'updated_at' => $time,
            'app_message_id' => '',
        ];
        return SeqAssembler::getSeqEntity($seqData);
    }

    private function getGroupUpdateReceiveUsers(string $groupId): array
    {
        // 批quantitygenerate群member变moremessage
        $groupEntity = $this->delightfulGroupRepository->getGroupInfoById($groupId);
        if ($groupEntity === null || $groupEntity->getGroupStatus() === GroupStatusEnum::Disband) {
            return [];
        }
        // 找to群member
        $groupUsers = $this->delightfulGroupRepository->getGroupUserList($groupId, '', null, ['user_id']);
        return array_column($groupUsers, 'user_id');
    }

    private function getGroupUserConversationsByUserIds(array $groupUserIds, string $groupId): array
    {
        $userConversations = $this->delightfulConversationRepository->batchGetConversations($groupUserIds, $groupId, ConversationType::Group);
        return array_column($userConversations, 'id', 'user_id');
    }

    private function batchCreateGroupUserSeqEntity(array $userIds, ControlMessageType $controlMessageType, array $content): void
    {
        $operateUserId = $content['operate_user_id'] ?? '';
        // 批quantitygetuserEntity
        $users = $this->delightfulUserRepository->getUserByIds($userIds);
        $users = array_column($users, null, 'user_id');
        $time = date('Y-m-d H:i:s');
        $seqListCreateDTO = [];
        $groupId = $content['group_id'] ?? '';
        // 群member增加o clock,为新加入的memberreturnsessionid
        $userConversations = $this->getGroupUserConversationsByUserIds(array_keys($users), $groupId);
        $userContent = $content;
        foreach ($users as $user) {
            $userId = $user['user_id'] ?? null;
            if (empty($userId)) {
                continue;
            }
            // not为操作者重复generateseq. 因为in投mq之front,已经为操作者generate了seq
            if ($userId === $operateUserId) {
                continue;
            }
            $conversationId = $userConversations[$userId] ?? '';
            $userContent['conversation_id'] = $conversationId;
            if (! empty($userContent['user_ids'])) {
                $userContent['user_ids'] = array_values(array_unique($userContent['user_ids']));
            }
            $seqId = (string) IdGenerator::getSnowId();
            $seqData = [
                'id' => $seqId,
                'organization_code' => $user['organization_code'],
                'object_type' => $user['user_type'],
                'object_id' => $user['delightful_id'],
                'seq_id' => $seqId,
                'seq_type' => $controlMessageType->value,
                'content' => Json::encode($userContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'receive_list' => '',
                'delightful_message_id' => '', // 控制messagenot能have delightful_message_id
                'message_id' => $seqId,
                'refer_message_id' => '',
                'sender_message_id' => '',
                'conversation_id' => $conversationId,
                'status' => DelightfulMessageStatus::Read->value, // send方自己的message,default已读
                'created_at' => $time,
                'updated_at' => $time,
                'app_message_id' => '',
            ];
            $seqListCreateDTO[] = SeqAssembler::getSeqEntity($seqData);
        }
        if (! empty($seqListCreateDTO)) {
            $this->delightfulSeqRepository->batchCreateSeq($seqListCreateDTO);
            $this->batchPushControlSeqList($seqListCreateDTO);
        }
    }
}

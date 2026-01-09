<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Service;

use App\Domain\Chat\DTO\ConversationListQueryDTO;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationEndInputMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationHideMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationMuteMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationStartInputMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationTopMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationWindowOpenMessage;
use App\Domain\Chat\DTO\PageResponseDTO\ConversationsPageResponseDTO;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationStatus;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Entity\ValueObject\SocketEventType;
use App\Domain\Chat\Event\ConversationCreatedEvent;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Group\Entity\DelightfulGroupEntity;
use App\ErrorCode\ChatErrorCode;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\SocketIO\SocketIOUtil;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Chat\Assembler\MessageAssembler;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Hyperf\Codec\Json;
use Hyperf\DbConnection\Db;
use Throwable;

use function Hyperf\Coroutine\co;

class DelightfulConversationDomainService extends AbstractDomainService
{
    /**
     * create/更新conversation窗口.
     */
    public function saveConversation(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): DelightfulConversationEntity
    {
        // 从messageStruct中parse出来conversation窗口详情
        $messageType = $messageDTO->getMessageType();
        if (! $messageType instanceof ControlMessageType) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR);
        }
        /** @var ConversationWindowOpenMessage $messageStruct */
        $messageStruct = $messageDTO->getContent();
        $conversationDTO = new DelightfulConversationEntity();
        $conversationDTO->setUserId($dataIsolation->getCurrentUserId());
        $conversationDTO->setUserOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $conversationDTO->setReceiveId($messageStruct->getReceiveId());
        $conversationDTO->setReceiveType(ConversationType::from($messageStruct->getReceiveType()));
        // 判断 uid 和 receiverId 是否已经存在conversation
        $existsConversation = $this->delightfulConversationRepository->getConversationByUserIdAndReceiveId($conversationDTO);
        if ($existsConversation) {
            // 改变messagetype,从createconversation窗口,变更为打开conversation窗口
            $conversationEntity = $existsConversation;
            $messageTypeInterface = MessageAssembler::getMessageStructByArray(
                $messageType->getName(),
                $messageDTO->getContent()->toArray()
            );
            // need同时修改type和content,才能把message内容变更为打开conversation窗口
            $messageDTO->setMessageType($messageTypeInterface->getMessageTypeEnum());
            $messageDTO->setContent($messageTypeInterface);
            $messageDTO->setReceiveType($conversationEntity->getReceiveType());
            // 更新conversation窗口status
            if (in_array($messageDTO->getMessageType(), [ControlMessageType::CreateConversation, ControlMessageType::OpenConversation], true)) {
                $this->delightfulConversationRepository->updateConversationById($conversationEntity->getId(), [
                    'status' => ConversationStatus::Normal->value,
                ]);
                $conversationEntity->setStatus(ConversationStatus::Normal);
            }
        } else {
            $conversationEntity = $this->getOrCreateConversation(
                $conversationDTO->getUserId(),
                $conversationDTO->getReceiveId(),
                $conversationDTO->getReceiveType()
            );
        }
        return $conversationEntity;
    }

    /**
     * 打开conversation窗口.
     * 控制message,只在seq表write数据,不在message表写.
     * @throws Throwable
     */
    public function openConversationWindow(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): array
    {
        Db::beginTransaction();
        try {
            $conversationEntity = $this->saveConversation($messageDTO, $dataIsolation);
            $result = $this->handleCommonControlMessage($messageDTO, $conversationEntity);
            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
        return $result;
    }

    /**
     * conversation窗口：置顶/移除/免打扰.
     * @throws Throwable
     */
    public function conversationOptionChange(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): array
    {
        /** @var ConversationHideMessage|ConversationMuteMessage|ConversationTopMessage $messageStruct */
        $messageStruct = $messageDTO->getContent();
        $conversationId = $messageStruct->getConversationId();
        $conversationEntity = $this->checkAndGetSelfConversation($conversationId, $dataIsolation);
        // according to要操作的type，更改数据库
        $updateData = [];
        if ($messageStruct instanceof ConversationTopMessage) {
            $updateData = ['is_top' => $messageStruct->getIsTop()];
        }
        if ($messageStruct instanceof ConversationMuteMessage) {
            $updateData = ['is_not_disturb' => $messageStruct->getIsNotDisturb()];
        }
        if ($messageStruct instanceof ConversationHideMessage) {
            $updateData = ['status' => ConversationStatus::Hidden->value];
        }
        Db::beginTransaction();
        try {
            if (! empty($updateData)) {
                $this->delightfulConversationRepository->updateConversationById($conversationEntity->getId(), $updateData);
            }
            // 给自己的message流generate序列.
            $seqEntity = $this->generateSenderSequenceByControlMessage($messageDTO, $conversationEntity->getId());
            $seqEntity->setConversationId($conversationEntity->getId());
            // notifyuser的其他设备,这里即使投递fail也不影响,所以放协程里,事务外.
            co(function () use ($seqEntity) {
                // asyncpushmessage给自己的其他设备
                $this->pushControlSequence($seqEntity);
            });
            // 将message流return给current客户端! 但是还是willasyncpush给user的所有online客户端.
            $result = SeqAssembler::getClientSeqStruct($seqEntity, $messageDTO)->toArray();
            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
        return $result;
    }

    /**
     * 正在输入中的status只needpush给对方,不need推给自己的设备.
     */
    public function clientOperateConversationStatus(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): array
    {
        // 从messageStruct中parse出来conversation窗口详情
        $messageType = $messageDTO->getMessageType();
        if (! in_array($messageType, [ControlMessageType::StartConversationInput, ControlMessageType::EndConversationInput], true)) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR);
        }

        if (in_array($messageType, [ControlMessageType::StartConversationInput, ControlMessageType::EndConversationInput], true)) {
            /** @var ConversationEndInputMessage|ConversationStartInputMessage $messageStruct */
            $messageStruct = $messageDTO->getContent();
            // conversation不存在直接return
            if (! $messageStruct->getConversationId()) {
                return [];
            }
            $this->checkAndGetSelfConversation($messageStruct->getConversationId(), $dataIsolation);
            // generate控制message,push给收发双发
            $receiveConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($messageStruct->getConversationId());
            if ($receiveConversationEntity === null) {
                // check对方是否存在conversation,如果不存在直接return
                return [];
            }
            // 替换conversationid为receive方自己的
            $messageStruct->setConversationId($receiveConversationEntity->getId());
            $messageDTO->setContent($messageStruct);
            // 给对方的message流generate序列.
            $seqEntity = $this->generateReceiveSequenceByControlMessage($messageDTO, $receiveConversationEntity);
            // notify对方的所有
            $this->pushControlSequence($seqEntity);
        }
        // 告知客户端请求success
        return [];
    }

    /**
     * 智能体触发conversation的start输入或者end输入.
     * 直接操作对方的conversation窗口，而不是把message发在自己的conversation窗口然后再经由message分发模块forward到对方的conversation窗口.
     * @deprecated user端call agentOperateConversationStatusV2 method代替
     */
    public function agentOperateConversationStatus(ControlMessageType $controlMessageType, string $agentConversationId): bool
    {
        // 查找对方的conversation窗口
        $receiveConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($agentConversationId);
        if ($receiveConversationEntity === null) {
            return true;
        }
        $receiveUserEntity = $this->delightfulUserRepository->getUserById($receiveConversationEntity->getUserId());
        if ($receiveUserEntity === null) {
            return true;
        }
        if (! in_array($controlMessageType, [ControlMessageType::StartConversationInput, ControlMessageType::EndConversationInput], true)) {
            return true;
        }
        $messageDTO = new DelightfulMessageEntity();
        if ($controlMessageType === ControlMessageType::StartConversationInput) {
            $content = new ConversationStartInputMessage();
        } else {
            $content = new ConversationEndInputMessage();
        }
        $messageDTO->setMessageType($controlMessageType);
        $messageDTO->setContent($content);
        /** @var ConversationEndInputMessage|ConversationStartInputMessage $messageStruct */
        $messageStruct = $messageDTO->getContent();
        // generate控制message,push收件方
        $messageStruct->setConversationId($receiveConversationEntity->getId());
        $messageDTO->setContent($messageStruct);
        // generatemessage流generate序列.
        $seqEntity = $this->generateReceiveSequenceByControlMessage($messageDTO, $receiveConversationEntity);
        // notify收件方所有设备
        $this->pushControlSequence($seqEntity);
        return true;
    }

    /**
     * use intermediate 事件进行中间态messagepush，不持久化message. 支持话题级别的“正在输入中”
     * 直接操作对方的conversation窗口，而不是把message发在自己的conversation窗口然后再经由message分发模块forward到对方的conversation窗口.
     */
    public function agentOperateConversationStatusV2(ControlMessageType $controlMessageType, string $agentConversationId, ?string $topicId = null): bool
    {
        // 查找对方的conversation窗口
        $receiveConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($agentConversationId);
        if ($receiveConversationEntity === null) {
            return true;
        }
        $receiveUserEntity = $this->delightfulUserRepository->getUserById($receiveConversationEntity->getUserId());
        if ($receiveUserEntity === null) {
            return true;
        }
        if (! in_array($controlMessageType, [ControlMessageType::StartConversationInput, ControlMessageType::EndConversationInput], true)) {
            return true;
        }
        $messageDTO = new DelightfulMessageEntity();
        if ($controlMessageType === ControlMessageType::StartConversationInput) {
            $content = new ConversationStartInputMessage();
        } else {
            $content = new ConversationEndInputMessage();
        }
        $messageDTO->setMessageType($controlMessageType);
        $messageDTO->setContent($content);
        /** @var ConversationEndInputMessage|ConversationStartInputMessage $messageStruct */
        $messageStruct = $messageDTO->getContent();
        // generate控制message,push收件方
        $messageStruct->setConversationId($receiveConversationEntity->getId());
        $messageStruct->setTopicId($topicId);
        $messageDTO->setContent($messageStruct);
        $time = date('Y-m-d H:i:s');
        // generatemessage流generate序列.
        $seqData = [
            'organization_code' => $receiveConversationEntity->getUserOrganizationCode(),
            'object_type' => $receiveUserEntity->getUserType()->value,
            'object_id' => $receiveUserEntity->getDelightfulId(),
            'seq_type' => $messageDTO->getMessageType()->getName(),
            'content' => $content->toArray(),
            'conversation_id' => $receiveConversationEntity->getId(),
            'status' => DelightfulMessageStatus::Read->value, // 控制message不need已读回执
            'created_at' => $time,
            'updated_at' => $time,
            'app_message_id' => $messageDTO->getAppMessageId(),
            'extra' => [
                'topic_id' => $topicId,
            ],
        ];
        $seqEntity = SeqAssembler::getSeqEntity($seqData);
        // seq 也加上 topicId
        $pushData = SeqAssembler::getClientSeqStruct($seqEntity)->toArray();
        // 直接pushmessage给收件方
        SocketIOUtil::sendIntermediate(SocketEventType::Intermediate, $receiveUserEntity->getDelightfulId(), $pushData);
        return true;
    }

    /**
     * 为指定群成员createconversation窗口.
     */
    public function batchCreateGroupConversationByUserIds(DelightfulGroupEntity $groupEntity, array $userIds): array
    {
        $users = $this->delightfulUserRepository->getUserByIds($userIds);
        $users = array_column($users, null, 'user_id');
        // 判断这些user是否已经存在conversation窗口,只是窗口status被mark为delete
        $conversations = $this->delightfulConversationRepository->batchGetConversations($userIds, $groupEntity->getId(), ConversationType::Group);
        /** @var DelightfulConversationEntity[] $conversations */
        $conversations = array_column($conversations, null, 'user_id');
        // 给这些群成员批量generatecreateconversation窗口message
        $conversationsCreateDTO = [];
        $conversationsUpdateIds = [];
        foreach ($users as $user) {
            $userId = $user['user_id'] ?? null;
            $delightfulId = $user['delightful_id'] ?? null;
            if (empty($userId) || empty($delightfulId)) {
                $this->logger->error(sprintf(
                    'batchCreateGroupConversations 群成员没有匹配到 $users:%s $groupEntity:%s',
                    Json::encode($users),
                    Json::encode($groupEntity->toArray()),
                ));
                continue;
            }
            if (isset($conversations[$userId]) && ! empty($conversations[$userId]->getId())) {
                $conversationsUpdateIds[] = $conversations[$userId]->getId();
            } else {
                $conversationId = (string) IdGenerator::getSnowId();
                $conversationsCreateDTO[] = [
                    'id' => $conversationId,
                    'user_id' => $userId,
                    'user_organization_code' => $user['organization_code'],
                    'receive_type' => ConversationType::Group->value,
                    'receive_id' => $groupEntity->getId(),
                    'receive_organization_code' => $groupEntity->getOrganizationCode(),
                ];
            }
        }
        if (! empty($conversationsCreateDTO)) {
            $this->delightfulConversationRepository->batchAddConversation($conversationsCreateDTO);
        }
        if (! empty($conversationsUpdateIds)) {
            $this->delightfulConversationRepository->batchUpdateConversations($conversationsUpdateIds, [
                'status' => ConversationStatus::Normal->value,
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => null,
            ]);
        }
        return $conversationsCreateDTO;
    }

    /**
     * 为群主和群成员deleteconversation窗口.
     */
    public function batchDeleteGroupConversationByUserIds(DelightfulGroupEntity $groupEntity, array $userIds): int
    {
        return $this->delightfulConversationRepository->batchRemoveConversations($userIds, $groupEntity->getId(), ConversationType::Group);
    }

    public function getConversationById(string $conversationId, DataIsolation $dataIsolation): DelightfulConversationEntity
    {
        return $this->checkAndGetSelfConversation($conversationId, $dataIsolation);
    }

    public function getConversationByIdWithoutCheck(string $conversationId): ?DelightfulConversationEntity
    {
        return $this->delightfulConversationRepository->getConversationById($conversationId);
    }

    /**
     * 获取conversation窗口，不存在则create.支持user/group chat/ai.
     */
    public function getOrCreateConversation(string $senderUserId, string $receiveId, ?ConversationType $receiverType = null): DelightfulConversationEntity
    {
        // according to $receiverType ，对 receiveId 进行parse，判断是否存在
        $receiverTypeCallable = match ($receiverType) {
            null, ConversationType::User, ConversationType::Ai => function () use ($receiveId) {
                $receiverUserEntity = $this->delightfulUserRepository->getUserById($receiveId);
                if ($receiverUserEntity === null) {
                    ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
                }
                return ConversationType::from($receiverUserEntity->getUserType()->value);
            },
            ConversationType::Group => function () use ($receiveId) {
                $receiverGroupEntity = $this->delightfulGroupRepository->getGroupInfoById($receiveId);
                if ($receiverGroupEntity === null) {
                    ExceptionBuilder::throw(ChatErrorCode::RECEIVER_NOT_FOUND);
                }
                return ConversationType::Group;
            },
            default => static function () {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_TYPE_ERROR);
            }
        };
        $receiverType = $receiverTypeCallable();
        $conversationDTO = new DelightfulConversationEntity();
        $conversationDTO->setUserId($senderUserId);
        $conversationDTO->setReceiveId($receiveId);
        $conversationDTO->setReceiveType($receiverType);
        // 判断 uid 和 receiverId 是否已经存在conversation
        $conversationEntity = $this->delightfulConversationRepository->getConversationByUserIdAndReceiveId($conversationDTO);
        if ($conversationEntity === null) {
            if (in_array($conversationDTO->getReceiveType(), [ConversationType::User, ConversationType::Ai], true)) {
                # createconversation窗口
                $conversationDTO = $this->parsePrivateChatConversationReceiveType($conversationDTO);
                # 准备generate一个conversation窗口
                $conversationEntity = $this->delightfulConversationRepository->addConversation($conversationDTO);

                # 触发conversationcreate事件
                event_dispatch(new ConversationCreatedEvent($conversationEntity));
            }

            if (isset($conversationEntity)) {
                return $conversationEntity;
            }
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        return $conversationEntity;
    }

    public function getConversationByUserIdAndReceiveId(DelightfulConversationEntity $conversation): ?DelightfulConversationEntity
    {
        return $this->delightfulConversationRepository->getConversationByUserIdAndReceiveId($conversation);
    }

    public function getConversations(DataIsolation $dataIsolation, ConversationListQueryDTO $queryDTO): ConversationsPageResponseDTO
    {
        $conversationDTO = new DelightfulConversationEntity();
        $conversationDTO->setUserId($dataIsolation->getCurrentUserId());
        return $this->delightfulConversationRepository->getConversationsByUserIds($conversationDTO, $queryDTO, [$dataIsolation->getCurrentUserId()]);
    }

    public function saveInstruct(DelightfulUserAuthorization $authenticatable, array $instruct, string $conversationId): array
    {
        $this->getConversationById($conversationId, DataIsolation::create($authenticatable->getOrganizationCode(), $authenticatable->getId()));

        $this->delightfulConversationRepository->saveInstructs($conversationId, $instruct);

        $delightfulSeqEntity = new DelightfulSeqEntity();

        $delightfulSeqEntity->setOrganizationCode($authenticatable->getOrganizationCode());

        return $instruct;
    }

    public function batchUpdateInstruct(array $updateData): void
    {
        $this->delightfulConversationRepository->batchUpdateInstructs($updateData);
    }

    /**
     * 获取user与多个receive者的conversationID映射.
     * @param string $userId userID
     * @param array $receiveIds receive者IDarray
     * @return array receive者ID => conversationID的映射array
     */
    public function getConversationIdMappingByReceiveIds(string $userId, array $receiveIds): array
    {
        if (empty($receiveIds)) {
            return [];
        }

        $conversations = $this->delightfulConversationRepository->getConversationsByReceiveIds(
            $userId,
            $receiveIds
        );

        $conversationMap = [];
        foreach ($conversations as $conversation) {
            $conversationMap[$conversation->getReceiveId()] = $conversation->getId();
        }

        return $conversationMap;
    }
}

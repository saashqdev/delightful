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
     * create/updateconversationwindow.
     */
    public function saveConversation(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): DelightfulConversationEntity
    {
        // frommessageStructmiddleparseoutcomeconversationwindowdetail
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
        // 判断 uid and receiverId whetheralready经存inconversation
        $existsConversation = $this->delightfulConversationRepository->getConversationByUserIdAndReceiveId($conversationDTO);
        if ($existsConversation) {
            // 改变messagetype,fromcreateconversationwindow,变moreforopenconversationwindow
            $conversationEntity = $existsConversation;
            $messageTypeInterface = MessageAssembler::getMessageStructByArray(
                $messageType->getName(),
                $messageDTO->getContent()->toArray()
            );
            // needmeanwhilemodifytypeandcontent,才canmessagecontent变moreforopenconversationwindow
            $messageDTO->setMessageType($messageTypeInterface->getMessageTypeEnum());
            $messageDTO->setContent($messageTypeInterface);
            $messageDTO->setReceiveType($conversationEntity->getReceiveType());
            // updateconversationwindowstatus
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
     * openconversationwindow.
     * 控制message,只inseq表writedata,notinmessage表写.
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
     * conversationwindow：置top/移except/免打扰.
     * @throws Throwable
     */
    public function conversationOptionChange(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): array
    {
        /** @var ConversationHideMessage|ConversationMuteMessage|ConversationTopMessage $messageStruct */
        $messageStruct = $messageDTO->getContent();
        $conversationId = $messageStruct->getConversationId();
        $conversationEntity = $this->checkAndGetSelfConversation($conversationId, $dataIsolation);
        // according towant操astype，more改database
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
            // givefrom己messagestreamgenerate序column.
            $seqEntity = $this->generateSenderSequenceByControlMessage($messageDTO, $conversationEntity->getId());
            $seqEntity->setConversationId($conversationEntity->getId());
            // notifyuserother设备,thiswithineven if投递failalsonot影响,所by放协程within,transactionoutside.
            co(function () use ($seqEntity) {
                // asyncpushmessagegivefrom己other设备
                $this->pushControlSequence($seqEntity);
            });
            // willmessagestreamreturngivecurrentcustomer端! butisalsoiswillasyncpushgiveuser所haveonlinecustomer端.
            $result = SeqAssembler::getClientSeqStruct($seqEntity, $messageDTO)->toArray();
            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
        return $result;
    }

    /**
     * justininputmiddlestatus只needpushgiveto方,notneed推givefrom己设备.
     */
    public function clientOperateConversationStatus(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): array
    {
        // frommessageStructmiddleparseoutcomeconversationwindowdetail
        $messageType = $messageDTO->getMessageType();
        if (! in_array($messageType, [ControlMessageType::StartConversationInput, ControlMessageType::EndConversationInput], true)) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR);
        }

        if (in_array($messageType, [ControlMessageType::StartConversationInput, ControlMessageType::EndConversationInput], true)) {
            /** @var ConversationEndInputMessage|ConversationStartInputMessage $messageStruct */
            $messageStruct = $messageDTO->getContent();
            // conversationnot存in直接return
            if (! $messageStruct->getConversationId()) {
                return [];
            }
            $this->checkAndGetSelfConversation($messageStruct->getConversationId(), $dataIsolation);
            // generate控制message,pushgive收hairdoublehair
            $receiveConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($messageStruct->getConversationId());
            if ($receiveConversationEntity === null) {
                // checkto方whether存inconversation,ifnot存in直接return
                return [];
            }
            // 替换conversationidforreceive方from己
            $messageStruct->setConversationId($receiveConversationEntity->getId());
            $messageDTO->setContent($messageStruct);
            // giveto方messagestreamgenerate序column.
            $seqEntity = $this->generateReceiveSequenceByControlMessage($messageDTO, $receiveConversationEntity);
            // notifyto方所have
            $this->pushControlSequence($seqEntity);
        }
        // 告知customer端requestsuccess
        return [];
    }

    /**
     * 智canbody触hairconversationstartinputor者endinput.
     * 直接操asto方conversationwindow，whilenotismessagehairinfrom己conversationwindow然backagain经bymessageminutehair模pieceforwardtoto方conversationwindow.
     * @deprecated user端call agentOperateConversationStatusV2 method代替
     */
    public function agentOperateConversationStatus(ControlMessageType $controlMessageType, string $agentConversationId): bool
    {
        // findto方conversationwindow
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
        // generate控制message,push收item方
        $messageStruct->setConversationId($receiveConversationEntity->getId());
        $messageDTO->setContent($messageStruct);
        // generatemessagestreamgenerate序column.
        $seqEntity = $this->generateReceiveSequenceByControlMessage($messageDTO, $receiveConversationEntity);
        // notify收item方所have设备
        $this->pushControlSequence($seqEntity);
        return true;
    }

    /**
     * use intermediate eventconductmiddlebetweenstatemessagepush，not持久化message. support话题level别“justininputmiddle”
     * 直接操asto方conversationwindow，whilenotismessagehairinfrom己conversationwindow然backagain经bymessageminutehair模pieceforwardtoto方conversationwindow.
     */
    public function agentOperateConversationStatusV2(ControlMessageType $controlMessageType, string $agentConversationId, ?string $topicId = null): bool
    {
        // findto方conversationwindow
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
        // generate控制message,push收item方
        $messageStruct->setConversationId($receiveConversationEntity->getId());
        $messageStruct->setTopicId($topicId);
        $messageDTO->setContent($messageStruct);
        $time = date('Y-m-d H:i:s');
        // generatemessagestreamgenerate序column.
        $seqData = [
            'organization_code' => $receiveConversationEntity->getUserOrganizationCode(),
            'object_type' => $receiveUserEntity->getUserType()->value,
            'object_id' => $receiveUserEntity->getDelightfulId(),
            'seq_type' => $messageDTO->getMessageType()->getName(),
            'content' => $content->toArray(),
            'conversation_id' => $receiveConversationEntity->getId(),
            'status' => DelightfulMessageStatus::Read->value, // 控制messagenotneedalready读return执
            'created_at' => $time,
            'updated_at' => $time,
            'app_message_id' => $messageDTO->getAppMessageId(),
            'extra' => [
                'topic_id' => $topicId,
            ],
        ];
        $seqEntity = SeqAssembler::getSeqEntity($seqData);
        // seq alsoaddup topicId
        $pushData = SeqAssembler::getClientSeqStruct($seqEntity)->toArray();
        // 直接pushmessagegive收item方
        SocketIOUtil::sendIntermediate(SocketEventType::Intermediate, $receiveUserEntity->getDelightfulId(), $pushData);
        return true;
    }

    /**
     * forfinger定群membercreateconversationwindow.
     */
    public function batchCreateGroupConversationByUserIds(DelightfulGroupEntity $groupEntity, array $userIds): array
    {
        $users = $this->delightfulUserRepository->getUserByIds($userIds);
        $users = array_column($users, null, 'user_id');
        // 判断thistheseuserwhetheralready经存inconversationwindow,只iswindowstatusbemarkfordelete
        $conversations = $this->delightfulConversationRepository->batchGetConversations($userIds, $groupEntity->getId(), ConversationType::Group);
        /** @var DelightfulConversationEntity[] $conversations */
        $conversations = array_column($conversations, null, 'user_id');
        // givethisthese群memberbatchquantitygeneratecreateconversationwindowmessage
        $conversationsCreateDTO = [];
        $conversationsUpdateIds = [];
        foreach ($users as $user) {
            $userId = $user['user_id'] ?? null;
            $delightfulId = $user['delightful_id'] ?? null;
            if (empty($userId) || empty($delightfulId)) {
                $this->logger->error(sprintf(
                    'batchCreateGroupConversations 群membernothavematchto $users:%s $groupEntity:%s',
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
     * for群主and群memberdeleteconversationwindow.
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
     * getconversationwindow，not存inthencreate.supportuser/group chat/ai.
     */
    public function getOrCreateConversation(string $senderUserId, string $receiveId, ?ConversationType $receiverType = null): DelightfulConversationEntity
    {
        // according to $receiverType ，to receiveId conductparse，判断whether存in
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
        // 判断 uid and receiverId whetheralready经存inconversation
        $conversationEntity = $this->delightfulConversationRepository->getConversationByUserIdAndReceiveId($conversationDTO);
        if ($conversationEntity === null) {
            if (in_array($conversationDTO->getReceiveType(), [ConversationType::User, ConversationType::Ai], true)) {
                # createconversationwindow
                $conversationDTO = $this->parsePrivateChatConversationReceiveType($conversationDTO);
                # 准备generateoneconversationwindow
                $conversationEntity = $this->delightfulConversationRepository->addConversation($conversationDTO);

                # 触hairconversationcreateevent
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
     * getuserand多receive者conversationIDmapping.
     * @param string $userId userID
     * @param array $receiveIds receive者IDarray
     * @return array receive者ID => conversationIDmappingarray
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

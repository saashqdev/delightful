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
        // judge uid and receiverId whetheralreadyalready existsinconversation
        $existsConversation = $this->delightfulConversationRepository->getConversationByUserIdAndReceiveId($conversationDTO);
        if ($existsConversation) {
            // altermessagetype,fromcreateconversationwindow,changemoreforopenconversationwindow
            $conversationEntity = $existsConversation;
            $messageTypeInterface = MessageAssembler::getMessageStructByArray(
                $messageType->getName(),
                $messageDTO->getContent()->toArray()
            );
            // needmeanwhilemodifytypeandcontent,onlycanmessagecontentchangemoreforopenconversationwindow
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
     * controlmessage,onlyinseqtablewritedata,notinmessagetablewrite.
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
     * conversationwindow:settop/moveexcept/do not disturb.
     * @throws Throwable
     */
    public function conversationOptionChange(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): array
    {
        /** @var ConversationHideMessage|ConversationMuteMessage|ConversationTopMessage $messageStruct */
        $messageStruct = $messageDTO->getContent();
        $conversationId = $messageStruct->getConversationId();
        $conversationEntity = $this->checkAndGetSelfConversation($conversationId, $dataIsolation);
        // according towantoperationastype,morechangedatabase
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
            // givefromselfmessagestreamgeneratesequencecolumn.
            $seqEntity = $this->generateSenderSequenceByControlMessage($messageDTO, $conversationEntity->getId());
            $seqEntity->setConversationId($conversationEntity->getId());
            // notifyuserotherdevice,thiswithineven ifdeliverfailalsonotimpact, byputcoroutinewithin,transactionoutside.
            co(function () use ($seqEntity) {
                // asyncpushmessagegivefromselfotherdevice
                $this->pushControlSequence($seqEntity);
            });
            // willmessagestreamreturngivecurrentcustomerclient! butisalsoiswillasyncpushgiveuser haveonlinecustomerclient.
            $result = SeqAssembler::getClientSeqStruct($seqEntity, $messageDTO)->toArray();
            Db::commit();
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
        return $result;
    }

    /**
     * justininputmiddlestatusonlyneedpushgivetoside,notneedpushgivefromselfdevice.
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
            // conversationnotexistsindirectlyreturn
            if (! $messageStruct->getConversationId()) {
                return [];
            }
            $this->checkAndGetSelfConversation($messageStruct->getConversationId(), $dataIsolation);
            // generatecontrolmessage,pushgivereceivehairdoublehair
            $receiveConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($messageStruct->getConversationId());
            if ($receiveConversationEntity === null) {
                // checktosidewhetherexistsinconversation,ifnotexistsindirectlyreturn
                return [];
            }
            // replaceconversationidforreceivesidefromself
            $messageStruct->setConversationId($receiveConversationEntity->getId());
            $messageDTO->setContent($messageStruct);
            // givetosidemessagestreamgeneratesequencecolumn.
            $seqEntity = $this->generateReceiveSequenceByControlMessage($messageDTO, $receiveConversationEntity);
            // notifytolocationhave
            $this->pushControlSequence($seqEntity);
        }
        // informcustomerclientrequestsuccess
        return [];
    }

    /**
     * intelligencecanbodytouchhairconversationstartinputorpersonendinput.
     * directlyoperationastosideconversationwindow,whilenotismessagehairinfromselfconversationwindowthenbackagainalreadybymessageminutehairmodepieceforwardtotosideconversationwindow.
     * @deprecated userclientcall agentOperateConversationStatusV2 methodreplace
     */
    public function agentOperateConversationStatus(ControlMessageType $controlMessageType, string $agentConversationId): bool
    {
        // findtosideconversationwindow
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
        // generatecontrolmessage,pushreceiveitemside
        $messageStruct->setConversationId($receiveConversationEntity->getId());
        $messageDTO->setContent($messageStruct);
        // generatemessagestreamgeneratesequencecolumn.
        $seqEntity = $this->generateReceiveSequenceByControlMessage($messageDTO, $receiveConversationEntity);
        // notifyreceiveitemlocationhavedevice
        $this->pushControlSequence($seqEntity);
        return true;
    }

    /**
     * use intermediate eventconductmiddlebetweenstatemessagepush,notpersistencemessage. supporttopiclevelother“justininputmiddle”
     * directlyoperationastosideconversationwindow,whilenotismessagehairinfromselfconversationwindowthenbackagainalreadybymessageminutehairmodepieceforwardtotosideconversationwindow.
     */
    public function agentOperateConversationStatusV2(ControlMessageType $controlMessageType, string $agentConversationId, ?string $topicId = null): bool
    {
        // findtosideconversationwindow
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
        // generatecontrolmessage,pushreceiveitemside
        $messageStruct->setConversationId($receiveConversationEntity->getId());
        $messageStruct->setTopicId($topicId);
        $messageDTO->setContent($messageStruct);
        $time = date('Y-m-d H:i:s');
        // generatemessagestreamgeneratesequencecolumn.
        $seqData = [
            'organization_code' => $receiveConversationEntity->getUserOrganizationCode(),
            'object_type' => $receiveUserEntity->getUserType()->value,
            'object_id' => $receiveUserEntity->getDelightfulId(),
            'seq_type' => $messageDTO->getMessageType()->getName(),
            'content' => $content->toArray(),
            'conversation_id' => $receiveConversationEntity->getId(),
            'status' => DelightfulMessageStatus::Read->value, // controlmessagenotneedalreadyreadreturnexecute
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
        // directlypushmessagegivereceiveitemside
        SocketIOUtil::sendIntermediate(SocketEventType::Intermediate, $receiveUserEntity->getDelightfulId(), $pushData);
        return true;
    }

    /**
     * forfingerset groupmembercreateconversationwindow.
     */
    public function batchCreateGroupConversationByUserIds(DelightfulGroupEntity $groupEntity, array $userIds): array
    {
        $users = $this->delightfulUserRepository->getUserByIds($userIds);
        $users = array_column($users, null, 'user_id');
        // judgethistheseuserwhetheralreadyalready existsinconversationwindow,onlyiswindowstatusbemarkfordelete
        $conversations = $this->delightfulConversationRepository->batchGetConversations($userIds, $groupEntity->getId(), ConversationType::Group);
        /** @var DelightfulConversationEntity[] $conversations */
        $conversations = array_column($conversations, null, 'user_id');
        // givethisthesegroupmemberbatchquantitygeneratecreateconversationwindowmessage
        $conversationsCreateDTO = [];
        $conversationsUpdateIds = [];
        foreach ($users as $user) {
            $userId = $user['user_id'] ?? null;
            $delightfulId = $user['delightful_id'] ?? null;
            if (empty($userId) || empty($delightfulId)) {
                $this->logger->error(sprintf(
                    'batchCreateGroupConversations groupmembernothavematchto $users:%s $groupEntity:%s',
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
     * forgroup ownerandgroupmemberdeleteconversationwindow.
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
     * getconversationwindow,notexistsinthencreate.supportuser/group chat/ai.
     */
    public function getOrCreateConversation(string $senderUserId, string $receiveId, ?ConversationType $receiverType = null): DelightfulConversationEntity
    {
        // according to $receiverType ,to receiveId conductparse,judgewhetherexistsin
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
        // judge uid and receiverId whetheralreadyalready existsinconversation
        $conversationEntity = $this->delightfulConversationRepository->getConversationByUserIdAndReceiveId($conversationDTO);
        if ($conversationEntity === null) {
            if (in_array($conversationDTO->getReceiveType(), [ConversationType::User, ConversationType::Ai], true)) {
                # createconversationwindow
                $conversationDTO = $this->parsePrivateChatConversationReceiveType($conversationDTO);
                # preparegenerateoneconversationwindow
                $conversationEntity = $this->delightfulConversationRepository->addConversation($conversationDTO);

                # touchhairconversationcreateevent
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
     * getuserandmultiplereceivepersonconversationIDmapping.
     * @param string $userId userID
     * @param array $receiveIds receivepersonIDarray
     * @return array receivepersonID => conversationIDmappingarray
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

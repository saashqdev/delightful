<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Service;

use App\Domain\Chat\DTO\Agent\SenderExtraDTO;
use App\Domain\Chat\DTO\Message\ControlMessage\AddFriendMessage;
use App\Domain\Chat\DTO\Request\Common\ControlRequestData;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Event\Agent\UserCallAgentEvent;
use App\Domain\Chat\Event\Agent\UserCallAgentFailEvent;
use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\SocketIO\SocketIOUtil;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Hyperf\Codec\Json;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Coroutine\Coroutine;
use RuntimeException;
use Throwable;

use function Hyperf\Support\retry;

/**
 * handlemessagestream(seq)relatedclose.
 */
class DelightfulSeqDomainService extends AbstractDomainService
{
    /**
     * messagepush. toalreadyalreadygenerateseq,pushgiveseqownhaveperson.
     * @throws Throwable
     */
    public function pushSeq(string $seqId): void
    {
        $seqEntity = null;
        // checkseq,faildelaybackretry3time
        retry(3, function () use ($seqId, &$seqEntity) {
            $seqEntity = $this->delightfulSeqRepository->getSeqByMessageId($seqId);
            if ($seqEntity === null) {
                // maybeistransactionalsonotsubmit,mqalreadyalreadyconsume,delayretry
                ExceptionBuilder::throw(ChatErrorCode::SEQ_NOT_FOUND);
            }
        }, 100);
        if ($seqEntity === null) {
            $this->logger->error('messagePush seq not found:{seq_id} ', ['seq_id' => $seqId]);
            return;
        }
        $this->setRequestId($seqEntity->getAppMessageId());
        $this->logger->info(sprintf('messagePush preparestartpush seq:%s seqEntity:%s ', $seqId, Json::encode($seqEntity->toArray())));
        // judgemessagetype,iscontrolmessage,alsoischatmessage
        $seqUserEntity = $this->delightfulUserRepository->getUserByAccountAndOrganization($seqEntity->getObjectId(), $seqEntity->getOrganizationCode());
        if ($seqUserEntity === null) {
            $this->logger->error('messagePush delightful_id:{delightful_id} user not found', ['delightful_id' => $seqEntity->getObjectId()]);
            return;
        }

        if ($seqEntity->getSeqType() instanceof ControlMessageType) {
            // pushcontrolmessage. controlmessagegeneralnothave messageEntity (chatmessageactualbody)
            $this->pushControlSeq($seqEntity, $seqUserEntity);
        } else {
            $messageEntity = $this->delightfulMessageRepository->getMessageByDelightfulMessageId($seqEntity->getDelightfulMessageId());
            if ($messageEntity === null) {
                $this->logger->error('messagePush delightful_message_id:{delightful_message_id} message not found', ['delightful_message_id' => $seqEntity->getDelightfulMessageId()]);
                return;
            }
            // bydownischatmessagepush
            $this->pushChatSeq($seqEntity, $seqUserEntity, $messageEntity);
        }
    }

    /**
     * toalreadyalreadygenerateseq,pushgiveseqownhaveperson.
     */
    public function pushControlSeq(DelightfulSeqEntity $seqEntity, DelightfulUserEntity $seqUserEntity, ?DelightfulMessageEntity $messageEntity = null): void
    {
        // havethesecontrolmessage,notonlycontrolfromselfdevice,alsoneedcontroltosidedevice
        // controlmessagepush. todo:pendingoptimize,mergepushalreadyreadcontrolmessage
        if ($seqEntity->getObjectType() === ConversationType::User && ($seqEntity->getSeqType() instanceof ControlMessageType)) {
            SocketIOUtil::sendSequenceId($seqEntity);
        }

        if ($seqEntity->getObjectType() === ConversationType::Ai && ($seqEntity->getSeqType() instanceof ControlMessageType)) {
            // ifisgiveAIneedtouchhairflowprocesscontrolmessage,touchhairflowprocess
            $agentUserEntity = $seqUserEntity; // thiso clock seqUserEntity is AI
            $agentAccountEntity = $this->delightfulAccountRepository->getAccountInfoByDelightfulId($agentUserEntity->getDelightfulId());
            if ($agentAccountEntity === null) {
                $this->logger->error('UserCallAgentEventError delightful_id:{delightful_id} ai not found', ['delightful_id' => $agentUserEntity->getDelightfulId()]);
                return;
            }
            $senderUserEntity = null; // (personcategory)sendside user_entity
            if ($seqEntity->getConversationId()) {
                // thiswithinconversationwindowis aifromself,thatwhattosideispersoncategory(alsomaybeisanotherone ai,ifexistsin ai 互撩话)
                $conversationEntity = $this->delightfulConversationRepository->getConversationById($seqEntity->getConversationId());
                if ($conversationEntity === null) {
                    $this->logger->error('UserCallAgentEventError delightful_conversation_id:{delightful_conversation_id} conversation not found', ['delightful_conversation_id' => $seqEntity->getConversationId()]);
                    return;
                }
                $senderUserEntity = $this->delightfulUserRepository->getUserById($conversationEntity->getReceiveId());
            } elseif ($seqEntity->getSeqType() === ControlMessageType::AddFriendSuccess) {
                // factorforaddgoodfriendnothaveconversationwindow, byneedaccording tomessagesendidcheckouttoside user_entity
                /** @var AddFriendMessage $seqContent */
                $seqContent = $seqEntity->getContent();
                $senderUserEntity = $this->delightfulUserRepository->getUserById($seqContent->getUserId());
            }
            if ($senderUserEntity) {
                $this->userCallFlow($agentAccountEntity, $agentUserEntity, $senderUserEntity, $seqEntity);
            }
        }
    }

    /**
     * toalreadyalreadygenerateseq,pushgiveseqownhaveperson.
     * @throws Throwable
     */
    public function pushChatSeq(DelightfulSeqEntity $selfSeqEntity, DelightfulUserEntity $userEntity, DelightfulMessageEntity $messageEntity): void
    {
        // ifsequencecolumnnumberbelong toat ai,andhairitempersonis ai,notneedpush
        if ($selfSeqEntity->getObjectType() === ConversationType::Ai && $messageEntity->getSenderType() === ConversationType::Ai) {
            $this->logger->error(sprintf('UserCallAgentEventError seq:%s sequencecolumnnumberbelong toat ai,andhairitempersonis ai,notneedpush', Json::encode($selfSeqEntity->toArray())));
            return;
        }
        $receiveConversationType = $selfSeqEntity->getObjectType();
        $delightfulId = $selfSeqEntity->getObjectId();
        switch ($receiveConversationType) {
            case ConversationType::Ai:
                $aiAccountEntity = $this->delightfulAccountRepository->getAccountInfoByDelightfulId($delightfulId);
                if ($aiAccountEntity === null) {
                    $this->logger->error('UserCallAgentEventError delightful_id:{delightful_id} ai not found', ['delightful_id' => $delightfulId]);
                    return;
                }
                $senderUserEntity = $this->delightfulUserRepository->getUserById($messageEntity->getSenderId());
                // messagesendpersonnothavefindto
                if ($senderUserEntity === null || empty($selfSeqEntity->getSenderMessageId())) {
                    $this->logger->error('UserCallAgentEventError delightful_message_id:{delightful_message_id} sender_message_id not found', ['delightful_message_id' => $selfSeqEntity->getDelightfulMessageId()]);
                    return;
                }
                $messageSenderDelightfulId = $this->delightfulUserRepository->getUserById($messageEntity->getSenderId())?->getDelightfulId();
                if ($delightfulId === $messageSenderDelightfulId) {
                    // ai fromselfhairmessage,notcanagaintouchhairprocesscall!
                    $this->logger->error('UserCallAgentEventError delightful_id:{delightful_id} ai can not call flow', ['delightful_id' => $delightfulId]);
                    return;
                }
                try {
                    # ai sendalreadyreadreturnexecute
                    $this->aiSendReadStatusChangeReceipt($selfSeqEntity, $userEntity);
                    # call flow
                    // todo canmake optimizeflowresponsesuccessrate: syncetcpendingflowexecute,meticulousjudge,toatthisseq_id,uptimeflowresponsewhethertimeout,ifis,directlydiscard,notagainhairgiveflow
                    $this->userCallFlow($aiAccountEntity, $userEntity, $senderUserEntity, $selfSeqEntity);
                } catch (Throwable $throwable) {
                    $this->logger->error('UserCallAgentEventError', [
                        'message' => $throwable->getMessage(),
                        'file' => $throwable->getFile(),
                        'line' => $throwable->getLine(),
                        'code' => $throwable->getCode(),
                        'trace' => $throwable->getTraceAsString(),
                    ]);
                    throw $throwable;
                }
                break;
            case ConversationType::User:
                // todo onesetwantmake! publishsubscribeuserabbitmqimplement,notagainuseredispub/sub. meanwhile,pushbackneedcustomerclientreturnack,thenbackupdateseqstatus
                // todo onesetwantmake! onlypushseq_id,publishsubscribereceivetoseq_idback,againgodatabasecheckseqdetail,againpushgivecustomerclient
                $pushData = SeqAssembler::getClientSeqStruct($selfSeqEntity, $messageEntity)->toArray();
                // notprintsensitiveinfo
                $pushLogData = [
                    'delightful_id' => $pushData['seq']['delightful_id'],
                    'seq_id' => $pushData['seq']['seq_id'],
                    'message_id' => $pushData['seq']['message_id'],
                    'refer_message_id' => $pushData['seq']['refer_message_id'],
                    'sender_message_id' => $pushData['seq']['sender_message_id'],
                    'conversation_id' => $pushData['seq']['conversation_id'],
                    'organization_code' => $pushData['seq']['organization_code'],
                    'delightful_message_id' => $selfSeqEntity->getDelightfulMessageId(),
                    'app_message_id' => $selfSeqEntity->getAppMessageId(),
                    'topic_id' => $pushData['seq']['message']['topic_id'] ?? '',
                    'sender_id' => $pushData['seq']['message']['sender_id'] ?? '',
                    'message_type' => $pushData['seq']['message']['type'] ?? '',
                ];
                $this->logger->info(sprintf('messagePush to:"%s" pushData:"%s"', $delightfulId, Json::encode($pushLogData)));
                SocketIOUtil::sendSequenceId($selfSeqEntity);
                break;
            case ConversationType::Group:
                throw new RuntimeException('To be implemented');
            case ConversationType::System:
                throw new RuntimeException('To be implemented');
            case ConversationType::CloudDocument:
                throw new RuntimeException('To be implemented');
            case ConversationType::MultidimensionalTable:
                throw new RuntimeException('To be implemented');
            case ConversationType::Topic:
                throw new RuntimeException('To be implemented');
            case ConversationType::App:
                throw new RuntimeException('To be implemented');
        }
    }

    /**
     * Get seq entity list by delightful_message_id.
     * A delightful_message_id will create seq entities for both sender and receiver.
     *
     * @param string $delightfulMessageId The delightful_message_id
     * @return DelightfulSeqEntity[] Array of seq entities
     */
    public function getSeqEntitiesByDelightfulMessageId(string $delightfulMessageId): array
    {
        if (empty($delightfulMessageId)) {
            return [];
        }

        $seqList = $this->delightfulSeqRepository->getBothSeqListByDelightfulMessageId($delightfulMessageId);

        $seqEntities = [];
        foreach ($seqList as $seqData) {
            $seqEntities[] = SeqAssembler::getSeqEntity($seqData);
        }

        return $seqEntities;
    }

    /**
     * Get the minimum seq_id record for the same user (object_id) based on delightful_message_id
     * Used to find the original message when there are multiple edited versions.
     */
    public function getSelfMinSeqIdByDelightfulMessageId(DelightfulSeqEntity $delightfulSeqEntity): ?DelightfulSeqEntity
    {
        // Get all seq records with minimum seq_id for each user
        $seqList = $this->delightfulSeqRepository->getMinSeqListByDelightfulMessageId($delightfulSeqEntity->getDelightfulMessageId());

        if (empty($seqList)) {
            return null;
        }

        // Find the record belonging to the same user (object_id)
        $targetObjectId = $delightfulSeqEntity->getObjectId();
        foreach ($seqList as $seqData) {
            if ($seqData['object_id'] === $targetObjectId) {
                // Convert array data to DelightfulSeqEntity object
                return SeqAssembler::getSeqEntity($seqData);
            }
        }

        return null;
    }

    private function setRequestId(string $appMsgId): void
    {
        // use app_msg_id make request_id
        $requestId = empty($appMsgId) ? IdGenerator::getSnowId() : $appMsgId;
        CoContext::setRequestId((string) $requestId);
    }

    /**
     * sendalreadyreadreturnexecute.
     */
    private function aiSendReadStatusChangeReceipt(DelightfulSeqEntity $selfSeqEntity, DelightfulUserEntity $userEntity): void
    {
        $userAuth = new DelightfulUserAuthorization();
        $userAuth->setId($userEntity->getUserId());
        $userAuth->setOrganizationCode($selfSeqEntity->getOrganizationCode());
        // messagetypeandcontentabstractoutcome
        $messageDTO = $this->getControlMessageDTO($userAuth, $selfSeqEntity);
        // according tomessagetype,minutehairtotoshouldhandlemodepiece
        $dataIsolation = new DataIsolation();
        $dataIsolation->setCurrentOrganizationCode($userAuth->getOrganizationCode());
        $dataIsolation->setCurrentUserId($userAuth->getId());
        $dataIsolation->setUserType(UserType::Ai);
        try {
            $this->clientOperateMessageStatus($messageDTO, $dataIsolation);
        } catch (Throwable $exception) {
            $this->logger->error(sprintf(
                'UserCallAgentEventError aiSendReadStatusChangeReceipt error file:%s line:%s message:%s trace:%s',
                $exception->getFile(),
                $exception->getLine(),
                $exception->getMessage(),
                $exception->getTraceAsString()
            ));
        }
    }

    private function getControlMessageDTO(DelightfulUserAuthorization $userAuth, DelightfulSeqEntity $selfSeqEntity): DelightfulMessageEntity
    {
        $messageTypeString = ControlMessageType::SeenMessages->getName();
        $ControlRequestData = new ControlRequestData(
            [
                'message' => [
                    'type' => $messageTypeString,
                    $messageTypeString => [
                        'refer_message_ids' => [$selfSeqEntity->getSeqId()],
                    ],
                    'app_message_id' => IdGenerator::getUniqueId32(),
                ],
                'refer_message_id' => $selfSeqEntity->getSeqId(),
            ]
        );
        $content = $ControlRequestData->getMessage()->getDelightfulMessage();
        $messageType = $ControlRequestData->getMessage()->getDelightfulMessage()->getMessageTypeEnum();
        // controlmessagereceiveside,needaccording tocontrolmessagetypeagaincertain,thereforenotinthislocationhandle
        $time = date('Y-m-d H:i:s');
        $messageDTO = new DelightfulMessageEntity();
        $messageDTO->setSenderId($userAuth->getId());
        $messageDTO->setSenderType(ConversationType::Ai);
        $messageDTO->setSenderOrganizationCode($userAuth->getOrganizationCode());
        $messageDTO->setAppMessageId(IdGenerator::getUniqueId32());
        // messagetypeandcontentabstractoutcome
        $messageDTO->setContent($content);
        $messageDTO->setMessageType($messageType);
        $messageDTO->setSendTime($time);
        $messageDTO->setCreatedAt($time);
        $messageDTO->setUpdatedAt($time);
        $messageDTO->setDeletedAt(null);
        return $messageDTO;
    }

    /**
     * coroutinecalldelightful flow,handleuserhaircomemessage.
     */
    private function userCallFlow(AccountEntity $agentAccountEntity, DelightfulUserEntity $agentUserEntity, DelightfulUserEntity $senderUserEntity, DelightfulSeqEntity $seqEntity): void
    {
        if (empty($agentAccountEntity->getAiCode())) {
            ExceptionBuilder::throw(ChatErrorCode::AI_NOT_FOUND);
        }
        // getmessageEntity
        $messageEntity = $this->delightfulMessageRepository->getMessageByDelightfulMessageId($seqEntity->getDelightfulMessageId());

        // onlychatmessageandalreadyreadreturn執only觸hairflow
        $messageType = $messageEntity?->getMessageType();
        if ($messageType instanceof ChatMessageType || $seqEntity->canTriggerFlow()) {
            // getusertruename
            $senderAccountEntity = $this->delightfulAccountRepository->getAccountInfoByDelightfulId($senderUserEntity->getDelightfulId());
            // opencoroutine,copy requestId
            $requestId = CoContext::getRequestId();
            // coroutinetransparent transmissionlanguage
            $language = di(TranslatorInterface::class)->getLocale();

            $this->logger->info('userCallFlow language: ' . $language);
            // callflowmaybeveryconsumeo clock,notcanletcustomerclientone直etcpending
            Coroutine::create(function () use (
                $agentAccountEntity,
                $agentUserEntity,
                $senderAccountEntity,
                $senderUserEntity,
                $seqEntity,
                $messageEntity,
                $requestId,
                $language
            ) {
                $requestId = empty($requestId) ? $seqEntity->getAppMessageId() : $requestId;
                CoContext::setRequestId($requestId);
                di(TranslatorInterface::class)->setLocale($language);
                $this->logger->info('Coroutine  create userCallFlow language: ' . di(TranslatorInterface::class)->getLocale());
                try {
                    // touchhairevent
                    event_dispatch(new UserCallAgentEvent(
                        $agentAccountEntity,
                        $agentUserEntity,
                        $senderAccountEntity,
                        $senderUserEntity,
                        $seqEntity,
                        $messageEntity,
                        (new SenderExtraDTO())->setDelightfulEnvId($seqEntity->getExtra()?->getDelightfulEnvId())
                    ));
                } catch (Throwable $throwable) {
                    $this->logger->error('UserCallAgentEventError', [
                        'message' => $throwable->getMessage(),
                        'file' => $throwable->getFile(),
                        'line' => $throwable->getLine(),
                        'code' => $throwable->getCode(),
                        'trace' => $throwable->getTraceAsString(),
                    ]);
                    // returnoneiteminternationalizationerrormessage
                    event_dispatch(new UserCallAgentFailEvent($seqEntity));
                }
            });
        }
    }
}

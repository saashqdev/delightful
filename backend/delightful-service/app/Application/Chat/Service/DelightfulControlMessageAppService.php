<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Service\DelightfulControlDomainService;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Chat\Service\DelightfulSeqDomainService;
use App\Domain\Chat\Service\DelightfulTopicDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Group\Service\DelightfulGroupDomainService;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Throwable;

/**
 * controlmessagerelatedclose.
 */
class DelightfulControlMessageAppService extends DelightfulSeqAppService
{
    public function __construct(
        protected readonly DelightfulControlDomainService $controlDomainService,
        protected readonly DelightfulTopicDomainService $topicDomainService,
        protected readonly DelightfulConversationDomainService $conversationDomainService,
        protected readonly DelightfulGroupDomainService $groupDomainService,
        protected DelightfulSeqDomainService $delightfulSeqDomainService
    ) {
        parent::__construct($delightfulSeqDomainService);
    }

    /**
     * according tocustomerclienthaircomecontrolmessagetype,minutehairtotoshouldprocessmodepiece.
     * @throws Throwable
     */
    public function dispatchClientControlMessage(DelightfulMessageEntity $messageDTO, DelightfulUserAuthorization $userAuthorization): ?array
    {
        $controlType = $messageDTO->getMessageType();
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        if (! $controlType instanceof ControlMessageType) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR);
        }
        return match ($controlType) {
            ControlMessageType::CreateConversation,
            ControlMessageType::OpenConversation => $this->conversationDomainService->openConversationWindow($messageDTO, $dataIsolation),
            // settop,hidden,do not disturbsession
            ControlMessageType::HideConversation,
            ControlMessageType::MuteConversation,
            ControlMessageType::TopConversation => $this->conversationDomainService->conversationOptionChange($messageDTO, $dataIsolation),
            // withdraw,alreadyread,alreadyreadreturnexecute,editmessage
            ControlMessageType::SeenMessages,
            ControlMessageType::ReadMessage,
            ControlMessageType::RevokeMessage,
            ControlMessageType::EditMessage => $this->controlDomainService->clientOperateMessageStatus($messageDTO, $dataIsolation),
            // create,update,delete,settopic
            ControlMessageType::CreateTopic,
            ControlMessageType::UpdateTopic,
            ControlMessageType::DeleteTopic, => $this->clientOperateTopicMessage($messageDTO, $dataIsolation),
            // (singlechatsessionwindowmiddle)startinput/endinput
            ControlMessageType::StartConversationInput,
            ControlMessageType::EndConversationInput => $this->conversationDomainService->clientOperateConversationStatus($messageDTO, $dataIsolation),
            // setsessiontopic,preparedeprecated
            ControlMessageType::SetConversationTopic => [],
            default => ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR),
        };
    }

    /**
     * minutehairasyncmessagequeuemiddleseq.
     * such asaccording tohairitemsideseq,forreceiveitemsidegenerateseq,deliverseq.
     * @throws Throwable
     */
    public function dispatchMQControlMessage(DelightfulSeqEntity $delightfulSeqEntity): void
    {
        $controlMessageType = $delightfulSeqEntity->getSeqType();
        switch ($controlMessageType) {
            case ControlMessageType::SeenMessages:
            case ControlMessageType::ReadMessage:
                // alreadyreadreturnexecuteetcscenario,according tooneitemcontrolmessage,generateotherpersonseq.
                $this->controlDomainService->handlerMQReceiptSeq($delightfulSeqEntity);
                break;
            case ControlMessageType::RevokeMessage:
            case ControlMessageType::EditMessage:
                // withdrawmessage,editmessageetcscenario
                $this->controlDomainService->handlerMQUserSelfMessageChange($delightfulSeqEntity);
                break;
            case ControlMessageType::CreateTopic:
            case ControlMessageType::UpdateTopic:
            case ControlMessageType::DeleteTopic:
                // topicoperationas
                $this->handlerMQTopicControlMessage($delightfulSeqEntity);
                break;
            case ControlMessageType::GroupCreate:
            case ControlMessageType::GroupUsersAdd:
            case ControlMessageType::GroupUsersRemove:
            case ControlMessageType::GroupDisband:
            case ControlMessageType::GroupUpdate:
            case ControlMessageType::GroupOwnerChange:
                // group operationas
                $this->groupDomainService->handlerMQGroupUserChangeSeq($delightfulSeqEntity);
                break;
        }
    }

    public function clientOperateInstructMessage(DelightfulMessageEntity $messageEntity, string $conversationId): ?array
    {
        // givefromselfmessagestreamgeneratesequencecolumn.
        $seqEntity = $this->controlDomainService->generateSenderSequenceByControlMessage($messageEntity, $conversationId);
        // asyncwillgeneratemessagestreamnotifyuserotherdevice.
        $this->controlDomainService->pushControlSequence($seqEntity);
        // willmessagestreamreturngivecurrentcustomerclient! butisalsoiswillasyncpushgiveuser haveonlinecustomerclient.
        return SeqAssembler::getClientSeqStruct($seqEntity)->toArray();
    }

    private function clientOperateTopicMessage(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): array
    {
        $conversationId = $this->topicDomainService->clientOperateTopic($messageDTO, $dataIsolation);
        // givefromselfmessagestreamgeneratesequencecolumn.
        $seqEntity = $this->controlDomainService->generateSenderSequenceByControlMessage($messageDTO, $conversationId);
        // asyncwillgeneratemessagestreamnotifyuserotherdevice.
        $seqCreatedEvent = $this->controlDomainService->pushControlSequence($seqEntity);
        // asyncminutehaircontrolmessage,tosideoperationassessiontopic
        $this->controlDomainService->dispatchSeq($seqCreatedEvent);
        // willmessagestreamreturngivecurrentcustomerclient! butisalsoiswillasyncpushgiveuser haveonlinecustomerclient.
        return SeqAssembler::getClientSeqStruct($seqEntity)->toArray();
    }

    private function handlerMQTopicControlMessage(DelightfulSeqEntity $delightfulSeqEntity): void
    {
        $receiveSeqEntity = $this->topicDomainService->dispatchMQTopicOperation($delightfulSeqEntity);
        // asyncpushgivereceiveitemside,havenewtopic
        $receiveSeqEntity && $this->controlDomainService->pushControlSequence($receiveSeqEntity);
    }
}

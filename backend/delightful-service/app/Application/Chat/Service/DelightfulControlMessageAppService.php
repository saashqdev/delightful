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
 * 控制message相关.
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
     * according to客户端发来的控制messagetype,分发到对应的process模块.
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
            // 置顶,隐藏,免打扰session
            ControlMessageType::HideConversation,
            ControlMessageType::MuteConversation,
            ControlMessageType::TopConversation => $this->conversationDomainService->conversationOptionChange($messageDTO, $dataIsolation),
            // withdraw,已读,已读回执,editmessage
            ControlMessageType::SeenMessages,
            ControlMessageType::ReadMessage,
            ControlMessageType::RevokeMessage,
            ControlMessageType::EditMessage => $this->controlDomainService->clientOperateMessageStatus($messageDTO, $dataIsolation),
            // create,update,delete,set话题
            ControlMessageType::CreateTopic,
            ControlMessageType::UpdateTopic,
            ControlMessageType::DeleteTopic, => $this->clientOperateTopicMessage($messageDTO, $dataIsolation),
            // （单聊的session窗口中）开始输入/结束输入
            ControlMessageType::StartConversationInput,
            ControlMessageType::EndConversationInput => $this->conversationDomainService->clientOperateConversationStatus($messageDTO, $dataIsolation),
            // setsession话题，准备废弃
            ControlMessageType::SetConversationTopic => [],
            default => ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR),
        };
    }

    /**
     * 分发asyncmessagequeue中的seq.
     * such asaccording to发件方的seq,为收件方generateseq,投递seq.
     * @throws Throwable
     */
    public function dispatchMQControlMessage(DelightfulSeqEntity $delightfulSeqEntity): void
    {
        $controlMessageType = $delightfulSeqEntity->getSeqType();
        switch ($controlMessageType) {
            case ControlMessageType::SeenMessages:
            case ControlMessageType::ReadMessage:
                // 已读回执等场景,according to一条控制message,generate其他人的seq.
                $this->controlDomainService->handlerMQReceiptSeq($delightfulSeqEntity);
                break;
            case ControlMessageType::RevokeMessage:
            case ControlMessageType::EditMessage:
                // withdrawmessage,editmessage等场景
                $this->controlDomainService->handlerMQUserSelfMessageChange($delightfulSeqEntity);
                break;
            case ControlMessageType::CreateTopic:
            case ControlMessageType::UpdateTopic:
            case ControlMessageType::DeleteTopic:
                // 话题操作
                $this->handlerMQTopicControlMessage($delightfulSeqEntity);
                break;
            case ControlMessageType::GroupCreate:
            case ControlMessageType::GroupUsersAdd:
            case ControlMessageType::GroupUsersRemove:
            case ControlMessageType::GroupDisband:
            case ControlMessageType::GroupUpdate:
            case ControlMessageType::GroupOwnerChange:
                // 群操作
                $this->groupDomainService->handlerMQGroupUserChangeSeq($delightfulSeqEntity);
                break;
        }
    }

    public function clientOperateInstructMessage(DelightfulMessageEntity $messageEntity, string $conversationId): ?array
    {
        // 给自己的messagestreamgenerate序列.
        $seqEntity = $this->controlDomainService->generateSenderSequenceByControlMessage($messageEntity, $conversationId);
        // async将generate的messagestreamnotifyuser的其他设备.
        $this->controlDomainService->pushControlSequence($seqEntity);
        // 将messagestreamreturn给current客户端! 但是还是willasyncpush给user的所有online客户端.
        return SeqAssembler::getClientSeqStruct($seqEntity)->toArray();
    }

    private function clientOperateTopicMessage(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): array
    {
        $conversationId = $this->topicDomainService->clientOperateTopic($messageDTO, $dataIsolation);
        // 给自己的messagestreamgenerate序列.
        $seqEntity = $this->controlDomainService->generateSenderSequenceByControlMessage($messageDTO, $conversationId);
        // async将generate的messagestreamnotifyuser的其他设备.
        $seqCreatedEvent = $this->controlDomainService->pushControlSequence($seqEntity);
        // async分发控制message,对方操作了session的话题
        $this->controlDomainService->dispatchSeq($seqCreatedEvent);
        // 将messagestreamreturn给current客户端! 但是还是willasyncpush给user的所有online客户端.
        return SeqAssembler::getClientSeqStruct($seqEntity)->toArray();
    }

    private function handlerMQTopicControlMessage(DelightfulSeqEntity $delightfulSeqEntity): void
    {
        $receiveSeqEntity = $this->topicDomainService->dispatchMQTopicOperation($delightfulSeqEntity);
        // asyncpush给收件方,有new话题
        $receiveSeqEntity && $this->controlDomainService->pushControlSequence($receiveSeqEntity);
    }
}

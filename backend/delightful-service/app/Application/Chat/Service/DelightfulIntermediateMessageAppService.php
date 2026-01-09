<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Chat\DTO\DelightfulMessageDTO;
use App\Domain\Chat\DTO\Message\ChatMessage\RawMessage;
use App\Domain\Chat\DTO\Request\ChatRequest;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationStatus;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\IntermediateMessageType;
use App\Domain\Chat\Entity\ValueObject\SocketEventType;
use App\Domain\Chat\Service\DelightfulChatDomainService;
use App\Domain\Chat\Service\DelightfulIntermediateDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\SocketIO\SocketIOUtil;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Chat\Assembler\MessageAssembler;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Throwable;

/**
 * 控制message相close.
 */
class DelightfulIntermediateMessageAppService extends AbstractAppService
{
    public function __construct(
        protected readonly DelightfulIntermediateDomainService $delightfulIntermediateDomainService,
        protected readonly DelightfulChatDomainService $delightfulChatDomainService,
    ) {
    }

    /**
     * according tocustomer端haircome控制messagetype,minutehairtoto应process模piece.
     * @throws Throwable
     */
    public function dispatchClientIntermediateMessage(ChatRequest $chatRequest, DelightfulUserAuthorization $userAuthorization): ?array
    {
        $conversationEntity = $this->delightfulChatDomainService->getConversationById($chatRequest->getData()->getConversationId());
        if ($conversationEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        $senderUserEntity = $this->delightfulChatDomainService->getUserInfo($conversationEntity->getUserId());
        $messageDTO = MessageAssembler::getIntermediateMessageDTO(
            $chatRequest,
            $conversationEntity,
            $senderUserEntity
        );
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        // message鉴权
        $this->checkSendMessageAuth($conversationEntity, $dataIsolation);

        $messageContent = $messageDTO->getContent();
        if ($messageContent instanceof RawMessage) {
            $this->handleRawMessage($messageDTO, $conversationEntity, $chatRequest);
            return null;
        }

        match ($messageDTO->getMessageType()) {
            IntermediateMessageType::BeDelightfulInstruction => $this->delightfulIntermediateDomainService->handleBeDelightfulInstructionMessage(
                $messageDTO,
                $dataIsolation,
                $conversationEntity,
            ),
            default => ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR),
        };
        return null;
    }

    public function checkSendMessageAuth(DelightfulConversationEntity $conversationEntity, DataIsolation $dataIsolation): void
    {
        // checkconversation id所属organization，andwhenfront传入organizationencodingone致property
        if ($conversationEntity->getUserOrganizationCode() !== $dataIsolation->getCurrentOrganizationCode()) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // conversationwhetheralreadybedelete
        if ($conversationEntity->getStatus() === ConversationStatus::Delete) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_DELETED);
        }
    }

    private function handleRawMessage(DelightfulMessageDTO $messageDTO, DelightfulConversationEntity $conversationEntity, ChatRequest $chatRequest): void
    {
        $receiveUserEntity = $this->delightfulChatDomainService->getUserInfo($conversationEntity->getReceiveId());

        $messageEntity = new DelightfulMessageEntity();
        $messageEntity->setMessageType(ChatMessageType::Raw);
        $messageEntity->setContent($messageDTO->getContent());
        $seqEntity = new DelightfulSeqEntity();
        $seqEntity->setSeqType(ChatMessageType::Raw);
        $seqEntity->setContent($messageDTO->getContent());
        $seqEntity->setConversationId($chatRequest->getData()->getConversationId());
        $seqEntity->setExtra(new SeqExtra(['topic_id' => $messageDTO->getTopicId()]));
        $seqEntity->setAppMessageId($messageDTO->getAppMessageId());
        $clientSeqStruct = SeqAssembler::getClientSeqStruct($seqEntity, $messageEntity);
        $pushData = $clientSeqStruct->toArray();
        SocketIOUtil::sendIntermediate(SocketEventType::Intermediate, $receiveUserEntity->getDelightfulId(), $pushData);
    }
}

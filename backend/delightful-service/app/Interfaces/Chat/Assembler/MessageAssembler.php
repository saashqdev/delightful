<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Assembler;

use App\Domain\Chat\DTO\DelightfulMessageDTO;
use App\Domain\Chat\DTO\Message\ChatMessage\AggregateAISearchCardMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\AggregateAISearchCardMessageV2;
use App\Domain\Chat\DTO\Message\ChatMessage\AIImageCardMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\FilesMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\ImageConvertHighCardMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\ImagesMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\MarkdownMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\RawMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\RichTextMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\BeAgentMessageInterface;
use App\Domain\Chat\DTO\Message\ChatMessage\TextFormMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\TextMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\UnknowChatMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\VideoMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\VoiceMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\AddFriendMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationEndInputMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationHideMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationMuteMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationSetTopicMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationStartInputMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationTopMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationWindowCreateMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\ConversationWindowOpenMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\GroupCreateMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\GroupDisbandMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\GroupInfoUpdateMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\GroupOwnerChangeMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\GroupUserAddMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\GroupUserRemoveMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\GroupUserRoleChangeMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\InstructMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\MessageRevoked;
use App\Domain\Chat\DTO\Message\ControlMessage\MessagesSeen;
use App\Domain\Chat\DTO\Message\ControlMessage\TopicCreateMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\TopicDeleteMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\TopicUpdateMessage;
use App\Domain\Chat\DTO\Message\ControlMessage\UnknowControlMessage;
use App\Domain\Chat\DTO\Message\IntermediateMessage\BeDelightfulInstructionMessage;
use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Domain\Chat\DTO\Request\ChatRequest;
use App\Domain\Chat\DTO\Request\ControlRequest;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\IntermediateMessageType;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Throwable;

class MessageAssembler
{
    public static function getMessageType(string $messageTypeString): ChatMessageType|ControlMessageType|IntermediateMessageType
    {
        $messageTypeString = strtolower(string_to_line($messageTypeString));
        $messageType = ChatMessageType::tryFrom($messageTypeString);
        $messageType = $messageType ?? ControlMessageType::tryFrom($messageTypeString);
        $messageType = $messageType ?? IntermediateMessageType::tryFrom($messageTypeString);
        if ($messageType === null) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR);
        }
        return $messageType;
    }

    /**
     * according toarraygetmessage结构.
     */
    public static function getMessageStructByArray(ChatMessageType|ControlMessageType|string $messageTypeString, array $messageStructArray): MessageInterface
    {
        if (is_string($messageTypeString)) {
            $messageTypeEnum = self::getMessageType($messageTypeString);
        } else {
            $messageTypeEnum = $messageTypeString;
        }
        try {
            if ($messageTypeEnum instanceof ControlMessageType) {
                return self::getControlMessageStruct($messageTypeEnum, $messageStructArray);
            }
            if ($messageTypeEnum instanceof ChatMessageType) {
                return self::getChatMessageStruct($messageTypeEnum, $messageStructArray);
            }
            /* @phpstan-ignore-next-line */
            if ($messageTypeEnum instanceof IntermediateMessageType) {
                return self::getIntermediateMessageStruct($messageTypeEnum, $messageStructArray);
            }
        } catch (BusinessException$exception) {
            throw $exception;
        } catch (Throwable $exception) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, throwable: $exception);
        }
        /* @phpstan-ignore-next-line */
        ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR);
    }

    public static function getMessageEntity(?array $message): ?DelightfulMessageEntity
    {
        if (empty($message)) {
            return null;
        }
        return new DelightfulMessageEntity($message);
    }

    public static function getChatMessageDTOByRequest(
        ChatRequest $chatRequest,
        DelightfulConversationEntity $conversationEntity,
        DelightfulUserEntity $senderUserEntity
    ): DelightfulMessageEntity {
        $time = date('Y-m-d H:i:s');
        $appMessageId = $chatRequest->getData()->getMessage()->getAppMessageId();
        $requestMessage = $chatRequest->getData()->getMessage();
        // messagetypeandcontentabstractoutcome
        $messageDTO = new DelightfulMessageEntity();
        $messageDTO->setSenderId($conversationEntity->getUserId());
        // TODO sessiontableshould冗remainderrecord收hairdouble方usertype，目front只record收item方，need补充
        $senderType = ConversationType::from($senderUserEntity->getUserType()->value);
        $messageDTO->setSenderType($senderType);
        $messageDTO->setSenderOrganizationCode($conversationEntity->getUserOrganizationCode());
        $messageDTO->setReceiveId($conversationEntity->getReceiveId());
        $messageDTO->setReceiveType($conversationEntity->getReceiveType());
        $messageDTO->setReceiveOrganizationCode($conversationEntity->getReceiveOrganizationCode());
        $messageDTO->setAppMessageId($appMessageId);
        $messageDTO->setContent($requestMessage->getDelightfulMessage());
        $messageDTO->setMessageType($requestMessage->getDelightfulMessage()->getMessageTypeEnum());
        $messageDTO->setSendTime($time);
        $messageDTO->setCreatedAt($time);
        $messageDTO->setUpdatedAt($time);
        $messageDTO->setDeletedAt(null);
        return $messageDTO;
    }

    public static function getIntermediateMessageDTO(
        ChatRequest $chatRequest,
        DelightfulConversationEntity $conversationEntity,
        DelightfulUserEntity $senderUserEntity
    ): DelightfulMessageDTO {
        $time = date('Y-m-d H:i:s');
        $appMessageId = $chatRequest->getData()->getMessage()->getAppMessageId();
        $requestMessage = $chatRequest->getData()->getMessage();
        $topicId = $chatRequest->getData()->getMessage()->getTopicId();
        // messagetypeandcontentabstractoutcome
        $messageDTO = new DelightfulMessageDTO();
        $messageDTO->setSenderId($conversationEntity->getUserId());
        // TODO sessiontableshould冗remainderrecord收hairdouble方usertype，目front只record收item方，need补充
        $senderType = ConversationType::from($senderUserEntity->getUserType()->value);
        $messageDTO->setSenderType($senderType);
        $messageDTO->setSenderOrganizationCode($conversationEntity->getUserOrganizationCode());
        $messageDTO->setReceiveId($conversationEntity->getReceiveId());
        $messageDTO->setReceiveType($conversationEntity->getReceiveType());
        $messageDTO->setReceiveOrganizationCode($conversationEntity->getReceiveOrganizationCode());
        $messageDTO->setAppMessageId($appMessageId);
        $messageDTO->setContent($requestMessage->getDelightfulMessage());
        $messageDTO->setMessageType($requestMessage->getDelightfulMessage()->getMessageTypeEnum());
        $messageDTO->setSendTime($time);
        $messageDTO->setCreatedAt($time);
        $messageDTO->setUpdatedAt($time);
        $messageDTO->setDeletedAt(null);
        $messageDTO->setTopicId($topicId);
        return $messageDTO;
    }

    /**
     * according to protobuf message结构,getto应messageobject.
     */
    public static function getControlMessageDTOByRequest(ControlRequest $controlRequest, DelightfulUserAuthorization $userAuthorization, ConversationType $conversationType): DelightfulMessageEntity
    {
        $appMessageId = $controlRequest->getData()->getMessage()->getAppMessageId();
        $messageStruct = $controlRequest->getData()->getMessage()->getDelightfulMessage();
        # willprotobufmessageconvertforto应object
        $messageEntity = new DelightfulMessageEntity();
        $messageEntity->setSenderId($userAuthorization->getId());
        $messageEntity->setSenderType($conversationType);
        $messageEntity->setSenderOrganizationCode($userAuthorization->getOrganizationCode());
        $time = date('Y-m-d H:i:s');
        $messageEntity->setAppMessageId($appMessageId);
        // messagetypeandcontentabstractoutcome
        $messageEntity->setContent($messageStruct);
        $messageEntity->setMessageType($messageStruct->getMessageTypeEnum());
        $messageEntity->setSendTime($time);
        $messageEntity->setCreatedAt($time);
        $messageEntity->setUpdatedAt($time);
        $messageEntity->setDeletedAt(null);
        return $messageEntity;
    }

    /**
     * getchatmessage结构.
     */
    public static function getChatMessageStruct(ChatMessageType $messageTypeEnum, array $messageStructArray): MessageInterface
    {
        return match ($messageTypeEnum) {
            # chatmessage
            ChatMessageType::Text => new TextMessage($messageStructArray),
            ChatMessageType::RichText => new RichTextMessage($messageStructArray),
            ChatMessageType::Markdown => new MarkdownMessage($messageStructArray),
            ChatMessageType::AggregateAISearchCard => new AggregateAISearchCardMessage($messageStructArray),
            ChatMessageType::AggregateAISearchCardV2 => new AggregateAISearchCardMessageV2($messageStructArray),
            ChatMessageType::AIImageCard => new AIImageCardMessage($messageStructArray),
            ChatMessageType::ImageConvertHighCard => new ImageConvertHighCardMessage($messageStructArray),
            ChatMessageType::Files => new FilesMessage($messageStructArray),
            ChatMessageType::Image => new ImagesMessage($messageStructArray),
            ChatMessageType::Video => new VideoMessage($messageStructArray),
            ChatMessageType::Voice => new VoiceMessage($messageStructArray),
            ChatMessageType::BeAgentCard => make(BeAgentMessageInterface::class, ['messageStruct' => $messageStructArray]),
            ChatMessageType::TextForm => new TextFormMessage($messageStructArray),
            ChatMessageType::Raw => new RawMessage($messageStructArray),
            default => new UnknowChatMessage()
        };
    }

    /**
     * get控制message结构.
     */
    public static function getControlMessageStruct(ControlMessageType $messageTypeEnum, array $messageStructArray): MessageInterface
    {
        // 其实can直接use protobuf generate php object,butis暂o clocknothavetimeallquantity替换,bybackagain说.
        return match ($messageTypeEnum) {
            # 控制message
            ControlMessageType::CreateConversation => new ConversationWindowCreateMessage($messageStructArray),
            ControlMessageType::OpenConversation => new ConversationWindowOpenMessage($messageStructArray),
            ControlMessageType::TopConversation => new ConversationTopMessage($messageStructArray),
            ControlMessageType::HideConversation => new ConversationHideMessage($messageStructArray),
            ControlMessageType::MuteConversation => new ConversationMuteMessage($messageStructArray),
            ControlMessageType::SeenMessages => new MessagesSeen($messageStructArray), // 已读
            ControlMessageType::RevokeMessage => new MessageRevoked($messageStructArray), // withdraw
            ControlMessageType::CreateTopic => new TopicCreateMessage($messageStructArray),
            ControlMessageType::UpdateTopic => new TopicUpdateMessage($messageStructArray),
            ControlMessageType::DeleteTopic => new TopicDeleteMessage($messageStructArray),
            ControlMessageType::SetConversationTopic => new ConversationSetTopicMessage($messageStructArray),
            ControlMessageType::StartConversationInput => new ConversationStartInputMessage($messageStructArray),
            ControlMessageType::EndConversationInput => new ConversationEndInputMessage($messageStructArray),
            ControlMessageType::GroupUsersAdd => new GroupUserAddMessage($messageStructArray),
            ControlMessageType::GroupUsersRemove => new GroupUserRemoveMessage($messageStructArray),
            ControlMessageType::GroupUpdate => new GroupInfoUpdateMessage($messageStructArray),
            ControlMessageType::GroupDisband => new GroupDisbandMessage($messageStructArray),
            ControlMessageType::GroupCreate => new GroupCreateMessage($messageStructArray),
            ControlMessageType::GroupUserRoleChange => new GroupUserRoleChangeMessage($messageStructArray),
            ControlMessageType::GroupOwnerChange => new GroupOwnerChangeMessage($messageStructArray),
            ControlMessageType::AgentInstruct => new InstructMessage($messageStructArray),
            ControlMessageType::AddFriendSuccess => new AddFriendMessage($messageStructArray),
            default => new UnknowControlMessage()
        };
    }

    /**
     * gettemporarymessage结构.
     */
    public static function getIntermediateMessageStruct(IntermediateMessageType $messageTypeEnum, array $messageStructArray): MessageInterface
    {
        return match ($messageTypeEnum) {
            IntermediateMessageType::BeDelightfulInstruction => new BeDelightfulInstructionMessage($messageStructArray),
        };
    }

    /**
     * Builds a length-limited chat history context.
     * To ensure context coherence, this method prioritizes keeping the most recent messages.
     * Current user's messages are kept complete, while other users' messages are truncated to 500 characters.
     *
     * @param array $chatHistoryMessages Chat history messages
     * @param int $maxLength Maximum string length
     * @param string $currentUserNickname Current user's nickname for prioritization
     */
    public static function buildHistoryContext(array $chatHistoryMessages, int $maxLength = 3000, string $currentUserNickname = ''): string
    {
        if (empty($chatHistoryMessages)) {
            return '';
        }

        $limitedMessages = [];
        $currentLength = 0;
        $messageCount = 0;

        // Iterate through messages in reverse to prioritize recent ones
        foreach (array_reverse($chatHistoryMessages) as $message) {
            $role = $message['role'] ?? 'user';
            $content = $message['content'] ?? '';

            if (empty(trim($content))) {
                continue;
            }

            // ifnotiscurrentusermessage，andcontent超pass500character，thentruncate
            if (! empty($currentUserNickname) && $role !== $currentUserNickname && mb_strlen($content, 'UTF-8') > 500) {
                $content = mb_substr($content, 0, 500, 'UTF-8') . '...';
            }

            $formattedMessage = sprintf("%s: %s\n", $role, $content);
            $messageLength = mb_strlen($formattedMessage, 'UTF-8');

            // ifistheoneitemmessage，even if超passlength限制also要contain
            if ($messageCount === 0) {
                array_unshift($limitedMessages, $formattedMessage);
                $currentLength += $messageLength;
                ++$messageCount;
                continue;
            }

            if ($currentLength + $messageLength > $maxLength) {
                // Stop adding messages if the current one exceeds the length limit
                break;
            }

            // Prepend the message to the array to maintain the original chronological order
            array_unshift($limitedMessages, $formattedMessage);
            $currentLength += $messageLength;
            ++$messageCount;
        }

        return implode('', $limitedMessages);
    }
}

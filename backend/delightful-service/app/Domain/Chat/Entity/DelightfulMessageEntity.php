<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity;

use App\Domain\Chat\DTO\Message\ChatMessage\Item\ChatInstruction;
use App\Domain\Chat\DTO\Message\EmptyMessage;
use App\Domain\Chat\DTO\Message\DelightfulMessageStruct;
use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\IntermediateMessageType;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Interfaces\Chat\Assembler\MessageAssembler;
use Hyperf\Codec\Json;
use InvalidArgumentException;

/**
 * message的content详情. 一条message可能被多个session/序列号关联，所以messagecontent是独立的.
 */
class DelightfulMessageEntity extends AbstractEntity
{
    protected string $id = '';

    protected string $senderId = '';

    /**
     * 发送方type. user:user(ai也被认为是user)，group：group chat，system：系统message，cloud_document：云文档，multidimensional_table：多维table格 topic：话题 app：应用message.
     */
    protected ConversationType $senderType;

    /**
     * 发送者所属organization编码.
     */
    protected string $senderOrganizationCode = '';

    protected string $receiveId = '';

    /**
     * 接收objecttype. user:user(ai也被认为是user)，group：group chat，system：系统message，cloud_document：云文档，multidimensional_table：多维table格 topic：话题 app：应用message.
     * @see UserType
     */
    protected ConversationType $receiveType;

    // 收件人organization编码
    protected string $receiveOrganizationCode = '';

    protected string $appMessageId = '';

    protected string $delightfulMessageId = '';

    protected ChatMessageType|ControlMessageType|IntermediateMessageType $messageType;

    protected string $sendTime = '';

    // create/修改/deletetime
    protected string $createdAt = '';

    protected string $updatedAt = '';

    protected ?string $deletedAt = null;

    protected string $language = '';

    protected MessageInterface $content;

    protected ?string $currentVersionId;

    public function __construct(?array $data = [])
    {
        if (! empty($data['content'])) {
            if (is_string($data['content'])) {
                $data['content'] = Json::decode($data['content']);
            }
            $messageInterface = MessageAssembler::getMessageStructByArray(
                $data['message_type'],
                $data['content']
            );
            $data['content'] = $messageInterface;
            $data['message_type'] = $messageInterface->getMessageTypeEnum();
        } else {
            $emptyMessage = new EmptyMessage();
            $data['content'] = $emptyMessage;
            $data['message_type'] = $emptyMessage->getMessageTypeEnum();
        }
        if (! empty($data['language'])) {
            $this->language = $data['language'];
        }
        parent::__construct($data);
    }

    public function getCurrentVersionId(): ?string
    {
        return $this->currentVersionId ?? null;
    }

    public function setCurrentVersionId(null|int|string $currentVersionId): static
    {
        if (is_numeric($currentVersionId)) {
            $this->currentVersionId = (string) $currentVersionId;
        }
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(int|string $id): static
    {
        if (is_int($id)) {
            $id = (string) $id;
        }
        $this->id = $id;
        return $this;
    }

    public function getReceiveOrganizationCode(): string
    {
        return $this->receiveOrganizationCode;
    }

    public function setReceiveOrganizationCode(string $receiveOrganizationCode): static
    {
        $this->receiveOrganizationCode = $receiveOrganizationCode;
        return $this;
    }

    public function getMessageType(): ChatMessageType|ControlMessageType|IntermediateMessageType
    {
        return $this->messageType;
    }

    public function setMessageType(ChatMessageType|ControlMessageType|IntermediateMessageType|string $messageType): static
    {
        if (! is_string($messageType)) {
            $this->messageType = $messageType;
            return $this;
        }

        $this->messageType = $this->parseMessageTypeFromString($messageType);
        return $this;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(string $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?string $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getSenderOrganizationCode(): string
    {
        return $this->senderOrganizationCode;
    }

    public function setSenderOrganizationCode(string $senderOrganizationCode): static
    {
        $this->senderOrganizationCode = $senderOrganizationCode;
        return $this;
    }

    public function getAppMessageId(): string
    {
        return $this->appMessageId;
    }

    public function setAppMessageId(string $appMessageId): static
    {
        $this->appMessageId = $appMessageId;
        return $this;
    }

    public function getContent(): MessageInterface
    {
        return $this->content;
    }

    /**
     * @deprecated use getContent 替代
     */
    public function getMessageContent(): MessageInterface
    {
        return $this->getContent();
    }

    public function setContent(MessageInterface $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return array<ChatInstruction>
     */
    public function getChatInstructions(): array
    {
        $messageContent = $this->getContent();
        if (! $messageContent instanceof DelightfulMessageStruct) {
            return [];
        }
        return $messageContent->getInstructs() ?? [];
    }

    public function getReceiveType(): ConversationType
    {
        return $this->receiveType;
    }

    public function setReceiveType(ConversationType|int $receiveType): static
    {
        if (is_int($receiveType)) {
            $enumValue = ConversationType::tryFrom($receiveType);
            if ($enumValue === null) {
                throw new InvalidArgumentException("Invalid value provided for ConversationType: {$receiveType}");
            }
            $this->receiveType = $enumValue;
        } else {
            $this->receiveType = $receiveType;
        }
        return $this;
    }

    public function getReceiveId(): string
    {
        return $this->receiveId;
    }

    public function setReceiveId(int|string $receiveId): static
    {
        if (is_int($receiveId)) {
            $receiveId = (string) $receiveId;
        }
        $this->receiveId = $receiveId;
        return $this;
    }

    public function getSenderId(): string
    {
        return $this->senderId;
    }

    public function setSenderId(int|string $senderId): static
    {
        if (is_int($senderId)) {
            $senderId = (string) $senderId;
        }
        $this->senderId = $senderId;
        return $this;
    }

    public function getSenderType(): ConversationType
    {
        return $this->senderType;
    }

    public function setSenderType(ConversationType|int $senderType): static
    {
        if (is_int($senderType)) {
            $this->senderType = ConversationType::tryFrom($senderType);
        } else {
            $this->senderType = $senderType;
        }
        return $this;
    }

    public function getSendTime(): string
    {
        return $this->sendTime;
    }

    public function setSendTime(string $sendTime): static
    {
        $this->sendTime = $sendTime;
        return $this;
    }

    public function getDelightfulMessageId(): ?string
    {
        return $this->delightfulMessageId;
    }

    public function setDelightfulMessageId(string $id): static
    {
        $this->delightfulMessageId = $id;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;
        return $this;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['message_type'] = $this->getMessageType()->getName();
        $data['content'] = $this->getContent()->toArray();
        return $data;
    }

    private function parseMessageTypeFromString(string $messageType): ChatMessageType|ControlMessageType|IntermediateMessageType
    {
        $chatMessageType = ChatMessageType::tryFrom($messageType);
        if ($chatMessageType !== null) {
            return $chatMessageType;
        }

        $controlMessageType = ControlMessageType::tryFrom($messageType);
        if ($controlMessageType !== null) {
            return $controlMessageType;
        }

        $intermediateMessageType = IntermediateMessageType::tryFrom($messageType);
        if ($intermediateMessageType !== null) {
            return $intermediateMessageType;
        }

        throw new InvalidArgumentException("Invalid message type: {$messageType}");
    }
}

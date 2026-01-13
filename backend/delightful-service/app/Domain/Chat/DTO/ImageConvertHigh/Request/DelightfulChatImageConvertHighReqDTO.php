<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\ImageConvertHigh\Request;

use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Domain\ImageGenerate\ValueObject\ImageGenerateSourceEnum;

class DelightfulChatImageConvertHighReqDTO
{
    public MessageInterface $userMessage;

    public string $conversationId;

    public string $topicId = ''; // topic id,canfornull

    public string $appMessageId;

    public string $language = 'en_US';

    public ?string $requestId = null;

    public string $originImageUrl = '';

    public string $originImageId = '';

    public ?string $referText = null;

    public string $referMessageId = '';

    public ?string $radio = null;

    public ImageGenerateSourceEnum $sourceType;

    public string $sourceId;

    public function getReferText(): ?string
    {
        return $this->referText;
    }

    public function setReferText(?string $referText): DelightfulChatImageConvertHighReqDTO
    {
        $this->referText = $referText;
        return $this;
    }

    public function getReferMessageId(): ?string
    {
        return $this->referMessageId;
    }

    public function setReferMessageId(?string $referMessageId): DelightfulChatImageConvertHighReqDTO
    {
        $this->referMessageId = $referMessageId;
        return $this;
    }

    public function getOriginImageUrl(): string
    {
        return $this->originImageUrl;
    }

    public function setOriginImageUrl(string $originImageUrl): DelightfulChatImageConvertHighReqDTO
    {
        $this->originImageUrl = $originImageUrl;
        return $this;
    }

    public function getUserMessage(): MessageInterface
    {
        return $this->userMessage;
    }

    public function setUserMessage(MessageInterface $userMessage): DelightfulChatImageConvertHighReqDTO
    {
        $this->userMessage = $userMessage;
        return $this;
    }

    public function getConversationId(): string
    {
        return $this->conversationId;
    }

    public function setConversationId(string $conversationId): DelightfulChatImageConvertHighReqDTO
    {
        $this->conversationId = $conversationId;
        return $this;
    }

    public function getTopicId(): string
    {
        return $this->topicId;
    }

    public function setTopicId(string $topicId): DelightfulChatImageConvertHighReqDTO
    {
        $this->topicId = $topicId;
        return $this;
    }

    public function getAppMessageId(): string
    {
        return $this->appMessageId;
    }

    public function setAppMessageId(string $appMessageId): DelightfulChatImageConvertHighReqDTO
    {
        $this->appMessageId = $appMessageId;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): DelightfulChatImageConvertHighReqDTO
    {
        $this->language = $language;
        return $this;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setRequestId(?string $requestId): DelightfulChatImageConvertHighReqDTO
    {
        $this->requestId = $requestId;
        return $this;
    }

    public function getOriginImageId(): string
    {
        return $this->originImageId;
    }

    public function setOriginImageId(string $originImageId): DelightfulChatImageConvertHighReqDTO
    {
        $this->originImageId = $originImageId;
        return $this;
    }

    public function getRadio(): ?string
    {
        return $this->radio;
    }

    public function setRadio(?string $radio): DelightfulChatImageConvertHighReqDTO
    {
        $this->radio = $radio;
        return $this;
    }

    public function getSourceType(): ImageGenerateSourceEnum
    {
        return $this->sourceType;
    }

    public function setSourceType(ImageGenerateSourceEnum $sourceType): self
    {
        $this->sourceType = $sourceType;
        return $this;
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    public function setSourceId(string $sourceId): self
    {
        $this->sourceId = $sourceId;
        return $this;
    }
}

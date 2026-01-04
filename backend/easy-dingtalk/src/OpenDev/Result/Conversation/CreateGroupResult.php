<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\EasyDingTalk\OpenDev\Result\Conversation;

use Dtyq\EasyDingTalk\Kernel\Exceptions\InvalidResultException;
use Dtyq\EasyDingTalk\OpenDev\Result\AbstractResult;

class CreateGroupResult extends AbstractResult
{
    private string $chatid;

    private string $openConversationId;

    private int $conversationTag;

    public function buildByRawData(array $rawData): void
    {
        if (empty($rawData['chatid'])) {
            throw new InvalidResultException('chatid cannot be empty');
        }
        if (empty($rawData['openConversationId'])) {
            throw new InvalidResultException('openConversationId cannot be empty');
        }

        $this->chatid = $rawData['chatid'];
        $this->openConversationId = $rawData['openConversationId'];
        $this->conversationTag = $rawData['conversationTag'] ?? 0;
    }

    public function getChatid(): string
    {
        return $this->chatid;
    }

    public function setChatid(string $chatid): self
    {
        $this->chatid = $chatid;
        return $this;
    }

    public function getOpenConversationId(): string
    {
        return $this->openConversationId;
    }

    public function setOpenConversationId(string $openConversationId): self
    {
        $this->openConversationId = $openConversationId;
        return $this;
    }

    public function getConversationTag(): int
    {
        return $this->conversationTag;
    }

    public function setConversationTag(int $conversationTag): self
    {
        $this->conversationTag = $conversationTag;
        return $this;
    }
}

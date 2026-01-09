<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\StreamMessage;

use App\Domain\Chat\Entity\AbstractEntity;

class JsonStreamCachedDTO extends AbstractEntity
{
    // 避免频繁操作datalibrary，在内存中cachesendmessage的 sender_message_id
    protected string $senderMessageId;

    // 避免频繁操作datalibrary，在内存中cachereceivemessage的 receive_message_id
    protected string $receiveMessageId;

    /**
     * 收发双方的 message_id different，but delightful_message_id same。
     */
    protected string $delightfulMessageId;

    /**
     * 收件人的 delightful_id.
     */
    protected string $receiveDelightfulId;

    /**
     * cache大 json data.
     */
    protected array $content;

    // 避免频繁操作datalibrary，record最后一次updatedatalibrary的时间
    protected ?int $lastUpdateDatabaseTime;

    public function getLastUpdateDatabaseTime(): ?int
    {
        return $this->lastUpdateDatabaseTime ?? null;
    }

    public function setLastUpdateDatabaseTime(?int $lastUpdateDatabaseTime): self
    {
        $this->lastUpdateDatabaseTime = $lastUpdateDatabaseTime;
        return $this;
    }

    public function getReceiveMessageId(): ?string
    {
        return $this->receiveMessageId ?? null;
    }

    public function setReceiveMessageId(null|int|string $receiveMessageId): self
    {
        if (is_numeric($receiveMessageId)) {
            $this->receiveMessageId = (string) $receiveMessageId;
        } else {
            $this->receiveMessageId = $receiveMessageId;
        }
        return $this;
    }

    public function getSenderMessageId(): ?string
    {
        return $this->senderMessageId ?? null;
    }

    public function setSenderMessageId(null|int|string $senderMessageId): self
    {
        if (is_numeric($senderMessageId)) {
            $this->senderMessageId = (string) $senderMessageId;
        } else {
            $this->senderMessageId = $senderMessageId;
        }
        return $this;
    }

    public function getContent(): array
    {
        return $this->content ?? [];
    }

    public function setContent(array $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getDelightfulMessageId(): ?string
    {
        return $this->delightfulMessageId ?? null;
    }

    public function setDelightfulMessageId(null|int|string $delightfulMessageId): self
    {
        if (is_numeric($delightfulMessageId)) {
            $this->delightfulMessageId = (string) $delightfulMessageId;
        } else {
            $this->delightfulMessageId = $delightfulMessageId;
        }
        return $this;
    }

    public function getReceiveDelightfulId(): ?string
    {
        return $this->receiveDelightfulId ?? null;
    }

    public function setReceiveDelightfulId(null|int|string $receiveDelightfulId): self
    {
        if (is_numeric($receiveDelightfulId)) {
            $this->receiveDelightfulId = (string) $receiveDelightfulId;
        } else {
            $this->receiveDelightfulId = $receiveDelightfulId;
        }
        return $this;
    }
}

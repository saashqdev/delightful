<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Event\Seq;

use App\Domain\Chat\Entity\ValueObject\MessagePriority;
use App\Domain\Chat\Event\ChatEventInterface;
use App\Infrastructure\Core\AbstractEvent;

class SeqCreatedEvent extends AbstractEvent implements ChatEventInterface
{
    protected array $seqIds = [];

    // ifexistsin,consumeo clockbyconversation id addfromrotatelock,exhaustedquantityguaranteereceivehairdoublesidemessageorderoneto
    protected ?string $conversationId = null;

    protected MessagePriority $seqPriority;

    public function __construct(array $seqIds)
    {
        $this->seqIds = $seqIds;
    }

    public function getSeqIds(): array
    {
        return $this->seqIds;
    }

    public function setSeqIds(array $seqIds): void
    {
        $this->seqIds = $seqIds;
    }

    public function getPriority(): MessagePriority
    {
        return $this->seqPriority;
    }

    public function setPriority(MessagePriority $priority): void
    {
        $this->seqPriority = $priority;
    }

    public function getConversationId(): string
    {
        return $this->conversationId;
    }

    public function setConversationId(?string $conversationId): void
    {
        $this->conversationId = $conversationId;
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Request\Common;

use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Domain\Chat\Entity\AbstractEntity;
use App\Interfaces\Chat\Assembler\MessageAssembler;

class Message extends AbstractEntity
{
    protected string $appMessageId;

    protected int $sendTime;

    protected string $topicId;

    /**
     * 控制messageorchatmessage的type.
     * according to type 来确定messagetype是哪one.
     */
    protected string $type;

    /**
     * according to type 的type,来确定 DelightfulMessage 的具体type.
     */
    protected MessageInterface $delightfulMessage;

    public function __construct(array $data)
    {
        parent::__construct($data);
        $messageType = $this->getType();
        if ($data[$messageType] instanceof MessageInterface) {
            $this->delightfulMessage = $data[$messageType];
        } else {
            $this->delightfulMessage = MessageAssembler::getMessageStructByArray($messageType, $data[$messageType]);
        }
    }

    public function getAppMessageId(): string
    {
        return $this->appMessageId ?? '';
    }

    public function setAppMessageId(?string $appMessageId): void
    {
        $this->appMessageId = $appMessageId ?? '';
    }

    public function getSendTime(): int
    {
        return $this->sendTime ?? time();
    }

    public function setSendTime(?int $sendTime): void
    {
        $this->sendTime = $sendTime ?? time();
    }

    public function getTopicId(): string
    {
        return $this->topicId ?? '';
    }

    public function setTopicId(?string $topicId): void
    {
        $this->topicId = $topicId ?? '';
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getDelightfulMessage(): MessageInterface
    {
        return $this->delightfulMessage;
    }

    public function setDelightfulMessage(MessageInterface $delightfulMessage): void
    {
        $this->delightfulMessage = $delightfulMessage;
    }
}

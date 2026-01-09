<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Response\Common;

use App\Domain\Chat\DTO\Message\ChatMessage\UnknowChatMessage;
use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Domain\Chat\Entity\AbstractEntity;
use App\Interfaces\Chat\Assembler\MessageAssembler;
use Throwable;

/**
 * customer端收to的message结构body.
 */
class ClientMessage extends AbstractEntity
{
    // service端generate的message唯一id，all局唯一。useatwithdraw、editmessage。
    protected string $delightfulMessageId;

    // customer端generate，needios/安卓/web三端共同确定一generate算法。useat告知customer端，delightful_message_id的由来
    protected ?string $appMessageId;

    // 话题id
    protected ?string $topicId;

    // message的小category。控制message的小category：已读回执；withdraw；edit；入群/退群；organization架构变动; 。 showmessage：text,voice,img,file,videoetc

    protected string $type;

    // 回显未读person数,ifuserpoint击了detail,againrequestspecific的messagecontent
    protected ?int $unreadCount;

    // messagesend者,自己or者他person
    protected string $senderId;

    // messagesendtime，与 delightful_message_id 一起，useatwithdraw、editmessageo clock的唯一property校验。
    protected int $sendTime;

    // chatmessagestatus:unread | seen | read |revoked  .对应middle文释义：未读|已读|已view（non纯文本的复杂typemessage，userpoint击了detail）  | withdraw
    protected ?string $status;

    protected MessageInterface $content;

    public function __construct(array $data)
    {
        if (! $data['content'] instanceof MessageInterface) {
            // 避免eachtype bug 导致user完all无法拉message，这within做一down兜bottom
            try {
                $data['content'] = MessageAssembler::getMessageStructByArray($data['type'], $data['content']);
            } catch (Throwable) {
                $data['content'] = new UnknowChatMessage();
            }
        }
        parent::__construct($data);
    }

    public function toArray(bool $filterNull = false): array
    {
        return [
            'delightful_message_id' => $this->getDelightfulMessageId(),
            'app_message_id' => $this->getAppMessageId(),
            'topic_id' => $this->getTopicId(),
            'type' => $this->getType(),
            'unread_count' => $this->getUnreadCount(),
            'sender_id' => $this->getSenderId(),
            'send_time' => $this->getSendTime(),
            'status' => $this->getStatus(),
            // 这within key 是 $this->getType() 对应messagetype，value 是messagecontent
            $this->type => $this->content->toArray($filterNull),
        ];
    }

    public function getDelightfulMessageId(): string
    {
        return $this->delightfulMessageId ?? '';
    }

    public function setDelightfulMessageId(string $delightfulMessageId): void
    {
        $this->delightfulMessageId = $delightfulMessageId;
    }

    public function getAppMessageId(): ?string
    {
        return $this->appMessageId ?? null;
    }

    public function setAppMessageId(?string $appMessageId): void
    {
        $this->appMessageId = $appMessageId;
    }

    public function getTopicId(): ?string
    {
        return $this->topicId ?? null;
    }

    public function setTopicId(?string $topicId): void
    {
        $this->topicId = $topicId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getUnreadCount(): ?int
    {
        return $this->unreadCount ?? null;
    }

    public function setUnreadCount(?int $unreadCount): void
    {
        $this->unreadCount = $unreadCount;
    }

    public function getSenderId(): string
    {
        return $this->senderId ?? '';
    }

    public function setSenderId(string $senderId): void
    {
        $this->senderId = $senderId;
    }

    public function getSendTime(): int
    {
        return $this->sendTime;
    }

    public function setSendTime(int $sendTime): void
    {
        $this->sendTime = $sendTime;
    }

    public function getStatus(): ?string
    {
        return $this->status ?? null;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getContent(): MessageInterface
    {
        return $this->content;
    }

    public function setContent(MessageInterface $content): void
    {
        $this->content = $content;
    }
}

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
 * customerclient receivetomessagestructurebody.
 */
class ClientMessage extends AbstractEntity
{
    // serviceclientgeneratemessageuniqueoneid,alllocally uniqueone.useatwithdraw,editmessage.
    protected string $delightfulMessageId;

    // customerclientgenerate,needios/Android/webthreeclient commoncertainonegeneratealgorithm.useatinformcustomerclient,delightful_message_idbycome
    protected ?string $appMessageId;

    // topicid
    protected ?string $topicId;

    // messagesmallcategory.controlmessagesmallcategory:alreadyreadreturnexecute;withdraw;edit;join group/leave group;organizationarchitecture change; . showmessage:text,voice,img,file,videoetc

    protected string $type;

    // returndisplaynotreadpersoncount,ifuserpoint击detail,againrequestspecificmessagecontent
    protected ?int $unreadCount;

    // messagesendperson,fromselforpersonheperson
    protected string $senderId;

    // messagesendtime,and delightful_message_id oneup,useatwithdraw,editmessageo clockuniqueonepropertyvalidation.
    protected int $sendTime;

    // chatmessagestatus:unread | seen | read |revoked  .toshouldmiddletext explanation:notread|alreadyread|alreadyview(nonpuretextcomplextypemessage,userpoint击detail)  | withdraw
    protected ?string $status;

    protected MessageInterface $content;

    public function __construct(array $data)
    {
        if (! $data['content'] instanceof MessageInterface) {
            // avoideachtype bug causeusercompleteallnoFarahmessage,thiswithinmakeonedownfallbackbottom
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
            // thiswithin key is $this->getType() toshouldmessagetype,value ismessagecontent
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

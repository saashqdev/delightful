<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Memory;

use App\Application\Flow\ExecuteManager\Attachment\AttachmentInterface;
use App\Application\Flow\ExecuteManager\Attachment\AttachmentUtil;
use App\Application\Flow\ExecuteManager\Attachment\ExternalAttachment;
use App\Domain\Chat\DTO\Message\TextContentInterface;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Flow\Entity\DelightfulFlowMemoryHistoryEntity;
use Hyperf\Odin\Contract\Message\MessageInterface;
use Hyperf\Odin\Message\AssistantMessage;
use Hyperf\Odin\Message\Role;
use Hyperf\Odin\Message\UserMessage;
use Hyperf\Odin\Message\UserMessageContent;

class LLMMemoryMessage
{
    /**
     * @var Role messagerole
     */
    private Role $role;

    /**
     * @var string message文本content
     */
    private string $textContent;

    /**
     * @var string messageID
     */
    private string $messageId;

    private string $mountId = '';

    /**
     * @var AttachmentInterface[] attachment列表
     */
    private array $attachments = [];

    /**
     * @var string originalmessage分析结果(多模态分析)
     */
    private string $analysisResult = '';

    /**
     * @var array originalmessagecontent
     */
    private array $originalContent = [];

    /**
     * @var string conversationID
     */
    private string $conversationId = '';

    private string $topicId = '';

    private string $uid = '';

    private string $requestId = '';

    /**
     * @var string messagetypestring
     */
    private string $messageTypeString = '';

    public function __construct(Role $role, string $textContent, string $messageId)
    {
        $this->role = $role;
        $this->textContent = $textContent;
        $this->messageId = $messageId;
    }

    public function toOdinMessage(): ?MessageInterface
    {
        if (! $this->isValid()) {
            return null;
        }
        $message = null;
        switch ($this->role) {
            case Role::Assistant:
                $message = new AssistantMessage($this->textContent);
                break;
            case Role::User:
                $images = $this->getImages();
                $message = new UserMessage($this->textContent);
                if (! empty($images)) {
                    $message->addContent(UserMessageContent::text($this->textContent));
                    foreach ($images as $image) {
                        $message->addContent(UserMessageContent::imageUrl($image->getUrl()));
                    }
                }
                break;
            default:
        }
        $message?->setIdentifier($this->getMessageId());
        $message?->setParams([
            'attachments' => $this->attachments,
            'analysis_result' => $this->analysisResult,
        ]);
        return $message;
    }

    /**
     * @return array<AttachmentInterface>
     */
    public function getImages(): array
    {
        $images = [];
        foreach ($this->attachments as $attachment) {
            if ($attachment->isImage()) {
                $images[] = $attachment;
            }
        }
        return $images;
    }

    public static function createByChatMemory(DelightfulMessageEntity $delightfulMessageEntity): ?self
    {
        $messageContent = $delightfulMessageEntity->getContent();
        $textContent = '';
        if ($messageContent instanceof TextContentInterface) {
            $textContent = $messageContent->getTextContent();
        }

        // getattachment
        $attachments = AttachmentUtil::getByDelightfulMessageEntity($delightfulMessageEntity);
        if ($textContent === '' && empty($attachments)) {
            return null;
        }

        // according tomessagetypecreate对应的message
        $messageType = $delightfulMessageEntity->getSenderType() ?? ConversationType::Ai;
        $role = ($messageType === ConversationType::Ai) ? Role::Assistant : Role::User;

        $customMessage = new LLMMemoryMessage($role, $textContent, $delightfulMessageEntity->getDelightfulMessageId());
        $customMessage->setAttachments($attachments);
        $customMessage->setOriginalContent($delightfulMessageEntity->toArray());
        return $customMessage;
    }

    public static function createByFlowMemory(DelightfulFlowMemoryHistoryEntity $delightfulFlowMemoryHistoryEntity): ?self
    {
        $role = Role::tryFrom($delightfulFlowMemoryHistoryEntity->getRole());
        if (! $role) {
            return null;
        }

        $content = $delightfulFlowMemoryHistoryEntity->getContent();

        // get文本content
        $textContent = $content['text']['content'] ?? '';

        // createcustomizemessage
        $customMessage = new LLMMemoryMessage($role, $textContent, $delightfulFlowMemoryHistoryEntity->getMessageId());
        $customMessage->setConversationId($delightfulFlowMemoryHistoryEntity->getConversationId());
        $customMessage->setOriginalContent($content);

        // settingmessagetype
        $customMessage->setMessageTypeString($content['type'] ?? '');

        // processattachment
        if (isset($content['flow_attachments']) && is_array($content['flow_attachments'])) {
            $attachments = [];
            foreach ($content['flow_attachments'] as $attachment) {
                if (isset($attachment['url'])) {
                    $attachments[] = new ExternalAttachment($attachment['url']);
                }
            }
            $customMessage->setAttachments($attachments);
        }

        // verify是否是valid的
        if (! $customMessage->isValid()) {
            return null;
        }

        return $customMessage;
    }

    public function isValid(): bool
    {
        if ($this->textContent === '' && empty($this->attachments)) {
            return false;
        }
        return true;
    }

    public function getTextContent(): string
    {
        return $this->textContent;
    }

    public function setTextContent(string $textContent): self
    {
        $this->textContent = $textContent;
        return $this;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function setMessageId(string $messageId): self
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * @return AttachmentInterface[]
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @param AttachmentInterface[] $attachments
     */
    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function addAttachment(AttachmentInterface $attachment): self
    {
        $this->attachments[] = $attachment;
        return $this;
    }

    public function getAnalysisResult(): string
    {
        return $this->analysisResult;
    }

    public function setAnalysisResult(string $analysisResult): self
    {
        $this->analysisResult = $analysisResult;
        return $this;
    }

    public function getOriginalContent(): array
    {
        return $this->originalContent;
    }

    public function setOriginalContent(array $originalContent): self
    {
        $this->originalContent = $originalContent;
        return $this;
    }

    public function getConversationId(): string
    {
        return $this->conversationId;
    }

    public function setConversationId(string $conversationId): self
    {
        $this->conversationId = $conversationId;
        return $this;
    }

    public function getTopicId(): string
    {
        return $this->topicId;
    }

    public function setTopicId(string $topicId): void
    {
        $this->topicId = $topicId;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function setUid(string $uid): void
    {
        $this->uid = $uid;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function setRequestId(string $requestId): void
    {
        $this->requestId = $requestId;
    }

    public function getMountId(): string
    {
        return $this->mountId;
    }

    public function setMountId(string $mountId): void
    {
        $this->mountId = $mountId;
    }

    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }

    public function getMessageTypeString(): string
    {
        return $this->messageTypeString;
    }

    public function setMessageTypeString(string $messageTypeString): self
    {
        $this->messageTypeString = $messageTypeString;
        return $this;
    }
}

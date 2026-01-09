<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\ExecutionData;

use App\Application\Flow\ExecuteManager\Attachment\AbstractAttachment;
use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use DateTime;
use Delightful\FlowExprEngine\Component;
use Delightful\FlowExprEngine\ComponentFactory;

class TriggerData
{
    // assistantencoding
    private string $agentKey = '';

    private TriggerDataUserExtInfo $userExtInfo;

    private bool $isAssistantParamCall = false;

    public function __construct(
        private readonly DateTime $triggerTime,
        private readonly array $userInfo = [],
        private readonly array $messageInfo = [],
        private array $params = [],
        private readonly array $paramsForm = [],
        private readonly ?Component $globalVariable = null,
        /** @var array<AbstractAttachment> $attachments */
        private array $attachments = [],
        private readonly array $systemParams = [],
        private readonly bool $isIgnoreMessageEntity = false,
        private readonly ?TriggerDataUserExtInfo $triggerDataUserExtInfo = null,
    ) {
        if (empty($this->userInfo['user_entity']) || ! $this->userInfo['user_entity'] instanceof DelightfulUserEntity) {
            ExceptionBuilder::throw(GenericErrorCode::SystemError, 'sender_user_not_found');
        }
        if (! $this->isIgnoreMessageEntity && (empty($this->messageInfo['message_entity']) || ! $this->messageInfo['message_entity'] instanceof DelightfulMessageEntity)) {
            ExceptionBuilder::throw(GenericErrorCode::SystemError, 'sender_message_not_found');
        }

        if (is_null($this->triggerDataUserExtInfo)) {
            $this->userExtInfo = new TriggerDataUserExtInfo(
                organizationCode: $this->getUserEntity()->getOrganizationCode(),
                userId: $this->getUserEntity()->getUserId(),
                nickname: $this->getUserEntity()->getNickname(),
                realName: $this->getAccountEntity()?->getRealName() ?? $this->getUserEntity()->getNickname(),
            );
        } else {
            $this->userExtInfo = $this->triggerDataUserExtInfo;
        }

        // merge paramsForm to params
        $form = ComponentFactory::fastCreate($this->paramsForm);
        if ($form?->isForm()) {
            $formResult = $form->getForm()->getKeyValue();
            if (is_array($formResult)) {
                $this->params = array_merge($this->params, $formResult);
            }
        }
    }

    public function getAgentKey(): string
    {
        return $this->agentKey;
    }

    public function setAgentKey(string $agentKey): void
    {
        $this->agentKey = $agentKey;
    }

    public static function createUserEntity(string $userId, string $nickname, string $organizationCode = ''): DelightfulUserEntity
    {
        $userEntity = new DelightfulUserEntity();
        $userEntity->setOrganizationCode($organizationCode);
        $userEntity->setUserId($userId);
        $userEntity->setNickname($nickname);
        $userEntity->setUserType(UserType::Human);
        return $userEntity;
    }

    public static function createMessageEntity(MessageInterface $message): DelightfulMessageEntity
    {
        $messageEntity = new DelightfulMessageEntity();
        $id = uniqid('AC_');
        $messageEntity->setId($id);
        $messageEntity->setDelightfulMessageId($id);
        $messageEntity->setMessageType($message->getMessageTypeEnum());
        $messageEntity->setContent($message);
        return $messageEntity;
    }

    public function getTriggerTime(): DateTime
    {
        return $this->triggerTime;
    }

    public function getUserInfo(): array
    {
        return $this->userInfo;
    }

    public function getMessageInfo(): array
    {
        return $this->messageInfo;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getUserEntity(): DelightfulUserEntity
    {
        return $this->userInfo['user_entity'];
    }

    public function getAccountEntity(): ?AccountEntity
    {
        if (($this->userInfo['account_entity'] ?? null) instanceof AccountEntity) {
            return $this->userInfo['account_entity'];
        }
        return null;
    }

    public function getMessageEntity(): ?DelightfulMessageEntity
    {
        return $this->messageInfo['message_entity'];
    }

    public function getSeqEntity(): ?DelightfulSeqEntity
    {
        return $this->messageInfo['seq_entity'] ?? null;
    }

    public function getGlobalVariable(): ?Component
    {
        return $this->globalVariable;
    }

    public function getParamsForm(): array
    {
        return $this->paramsForm;
    }

    public function getSystemParams(): array
    {
        return $this->systemParams;
    }

    public function getContent(): array
    {
        return [
            'type' => camelize($this->getMessageEntity()->getMessageType()->getName()),
            $this->getMessageEntity()->getMessageType()->getName() => $this->getMessageEntity()->getContent()->toArray(),
        ];
    }

    /**
     * @param array<AbstractAttachment> $attachments
     */
    public function setAttachments(array $attachments): void
    {
        $this->attachments = $attachments;
    }

    public function addAttachment(AbstractAttachment $attachment): void
    {
        $this->attachments[] = $attachment;
    }

    /**
     * @return AbstractAttachment[]
     */
    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function getAttachmentImageUrls(): array
    {
        $imageUrls = [];
        foreach ($this->getAttachments() as $attachment) {
            if ($attachment->isImage()) {
                $imageUrls[] = $attachment->getUrl();
            }
        }
        return $imageUrls;
    }

    public function getUserExtInfo(): TriggerDataUserExtInfo
    {
        return $this->userExtInfo;
    }

    public function isAssistantParamCall(): bool
    {
        return $this->isAssistantParamCall;
    }

    public function setIsAssistantParamCall(bool $isAssistantParamCall): void
    {
        $this->isAssistantParamCall = $isAssistantParamCall;
    }
}

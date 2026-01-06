<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Event;

use App\Domain\Contact\Entity\DelightfulUserEntity;

class InitDefaultAssistantConversationEvent
{
    public function __construct(
        public DelightfulUserEntity $userEntity,
        public ?array $defaultConversationAICodes = null,
    ) {
    }

    public function getDefaultConversationAICodes(): ?array
    {
        return $this->defaultConversationAICodes;
    }

    public function setDefaultConversationAICodes(?array $defaultConversationAICodes): InitDefaultAssistantConversationEvent
    {
        $this->defaultConversationAICodes = $defaultConversationAICodes;
        return $this;
    }

    public function getUserEntity(): DelightfulUserEntity
    {
        return $this->userEntity;
    }

    public function setUserEntity(DelightfulUserEntity $userEntity): InitDefaultAssistantConversationEvent
    {
        $this->userEntity = $userEntity;
        return $this;
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Listener;

use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Event\ConversationCreatedEvent;
use App\Domain\Chat\Service\DelightfulTopicDomainService;
use Hyperf\Event\Contract\ListenerInterface;

class ConversationCreatedListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            ConversationCreatedEvent::class,
        ];
    }

    /**
     * processconversationcreateevent.
     */
    public function process(object $event): void
    {
        if (! $event instanceof ConversationCreatedEvent) {
            return;
        }

        $conversation = $event->getConversation();

        // onlyforAIconversationfrom动createtopic
        if ($conversation->getReceiveType() === ConversationType::Ai) {
            $topicDomainService = di(DelightfulTopicDomainService::class);
            $topicDomainService->agentSendMessageGetTopicId($conversation, 0);
        }
    }
}

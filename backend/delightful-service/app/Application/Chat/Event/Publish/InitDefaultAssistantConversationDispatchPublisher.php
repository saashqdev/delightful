<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Event\Publish;

use App\Domain\Agent\Event\InitDefaultAssistantConversationEvent;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;

/**
 * initialize默认助手conversation分发模块.
 */
#[Producer(exchange: 'init_default_assistant_conversation', routingKey: 'init_default_assistant_conversation')]
class InitDefaultAssistantConversationDispatchPublisher extends ProducerMessage
{
    public function __construct(InitDefaultAssistantConversationEvent $event)
    {
        $this->payload = [
            'user_entity' => $event->getUserEntity(),
            'default_conversation_ai_codes' => $event->getDefaultConversationAICodes(),
        ];
    }
}

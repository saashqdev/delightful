<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Event\Publish;

use App\Domain\Chat\Entity\ValueObject\AmqpTopicType;
use App\Domain\Chat\Event\Seq\SeqCreatedEvent;
use App\Infrastructure\Core\Traits\ChatAmqpTrait;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;

/**
 * message分发模块,可能need根据oneseq,生成oneor多个seq.
 */
#[Producer]
class MessageDispatchPublisher extends ProducerMessage
{
    use ChatAmqpTrait;

    protected AmqpTopicType $topic = AmqpTopicType::Message;

    public function __construct(SeqCreatedEvent $event)
    {
        $this->exchange = $this->getExchangeName($this->topic);
        $this->routingKey = $this->getRoutingKeyName($this->topic, $event->getPriority());
        $this->payload = $event->toArray();
    }
}

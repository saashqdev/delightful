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
 * messagepush模块.
 * 直接pushseq给客户端.
 */
#[Producer]
class MessagePushPublisher extends ProducerMessage
{
    use ChatAmqpTrait;

    protected AmqpTopicType $topic = AmqpTopicType::Seq;

    public function __construct(SeqCreatedEvent $event)
    {
        $this->exchange = $this->getExchangeName($this->topic);
        $this->routingKey = $this->getRoutingKeyName($this->topic, $event->getPriority());
        $this->payload = ['seqIds' => $event->getSeqIds()];
    }
}

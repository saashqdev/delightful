<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Publish;

use Delightful\BeDelightful\Domain\SuperAgent\Event\TopicMessageprocess Event;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPtable ;
/** * topic Messageprocess Published. * NoticeHaveMessageneed process PassConcreteMessageContent. */ #[Producer(exchange: 'super_magic_topic_message_process', routingKey: 'super_magic_topic_message_process')]

class TopicMessageprocess Publisher extends ProducerMessage 
{
 /** * Function. */ 
    public function __construct(TopicMessageprocess Event $event) 
{
 $this->payload = $event->toArray(); // Set AMQP MessageProperty $this->properties = [ 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, // Message 'application_headers' => new AMQPtable ([ 'x-original-timestamp' => time(), // Set original Timestampseconds  'x-event-type' => 'topic_message_process', // EventType ]), ]; 
}
 
}
 

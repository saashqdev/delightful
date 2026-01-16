<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Publish;

use Delightful\BeDelightful\Domain\SuperAgent\Event\StopRunningTaskEvent;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPtable ;
/** * StopRunningTaskMessagePublished. */ #[Producer(exchange: 'super_magic_stop_task', routingKey: 'super_magic_stop_task')]

class StopRunningTaskPublisher extends ProducerMessage 
{
 /** * Function. */ 
    public function __construct(StopRunningTaskEvent $event) 
{
 $this->payload = $event->toArray(); // Set AMQP MessagePropertyIncludeoriginal Timestamp $this->properties = [ 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, // Message 'application_headers' => new AMQPtable ([ 'x-original-timestamp' => time(), // Set original Timestampseconds  'x-data-type' => $event->getDataType()->value, // Set DataTyperoute 'x-organization-code' => $event->getOrganizationCode(), // Set organization code ]), ]; 
}
 
}
 

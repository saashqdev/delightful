<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Event\Publish;

use Delightful\BeDelightful\Domain\BeAgent\Event\StopRunningTaskEvent;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Stop running task message publisher.
 */
#[Producer(exchange: 'be_delightful_stop_task', routingKey: 'be_delightful_stop_task')]
class StopRunningTaskPublisher extends ProducerMessage
{
    /**
     * Constructor.
     */
    public function __construct(StopRunningTaskEvent $event)
    {
        $this->payload = $event->toArray();

        // Set AMQP message properties, including original timestamp
        $this->properties = [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, // Keep message persistent
            'application_headers' => new AMQPTable([
                'x-original-timestamp' => time(), // Set original timestamp (seconds)
                'x-data-type' => $event->getDataType()->value, // Set data type for routing and monitoring
                'x-organization-code' => $event->getOrganizationCode(), // Set organization code
            ]),
        ];
    }
}

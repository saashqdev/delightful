<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Publish;

use Delightful\BeDelightful\Domain\SuperAgent\Event\FileBatchMoveEvent;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;

/**
 * File batch move message publisher.
 */
#[Producer(exchange: 'be_delightful_file_batch_move', routingKey: 'be_delightful_file_batch_move')]
class FileBatchMovePublisher extends ProducerMessage
{
    public function __construct(FileBatchMoveEvent $event)
    {
        $this->payload = $event->toArray();
    }
}

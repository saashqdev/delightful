<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Event\Publish;

use Delightful\BeDelightful\Domain\BeAgent\Event\FileBatchCopyEvent;
use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;

/**
 * File batch copy message publisher.
 */
#[Producer(exchange: 'be_delightful_file_batch_copy', routingKey: 'be_delightful_file_batch_copy')]
class FileBatchCopyPublisher extends ProducerMessage
{
    public function __construct(FileBatchCopyEvent $event)
    {
        $this->payload = $event->toArray();
    }
}

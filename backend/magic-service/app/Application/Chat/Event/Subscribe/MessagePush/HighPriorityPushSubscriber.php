<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Event\Subscribe\MessagePush;

use App\Domain\Chat\Entity\ValueObject\MessagePriority;
use Hyperf\Amqp\Annotation\Consumer;

#[Consumer(nums: 1)]
class HighPriorityPushSubscriber extends AbstractSeqPushSubscriber
{
    protected MessagePriority $priority = MessagePriority::High;
}

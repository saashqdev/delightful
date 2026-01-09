<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Traits;

use App\Domain\Chat\Entity\ValueObject\AmqpTopicType;
use App\Domain\Chat\Entity\ValueObject\MessagePriority;

trait ChatAmqpTrait
{
    public function getExchangeName(AmqpTopicType $topicType): string
    {
        return $topicType->value;
    }

    // pathbyitem
    public function getRoutingKeyName(AmqpTopicType $topicType, MessagePriority $priority): string
    {
        return sprintf('%s.%s', $topicType->value, $priority->name);
    }
}

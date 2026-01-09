<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum AmqpTopicType: string
{
    // 生产message(消费eachtypecustomer端产生的message,generate序columnnumber)
    case Message = 'delightful-chat-message';

    // 投递message(消费序columnnumber)
    case Seq = 'delightful-chat-seq';

    // 录音message
    case Recording = 'delightful-chat-recording';
}

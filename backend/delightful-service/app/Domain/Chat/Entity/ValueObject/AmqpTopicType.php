<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum AmqpTopicType: string
{
    // 生产消息(消费各类型客户端产生的消息,生成序列号)
    case Message = 'magic-chat-message';

    // 投递消息(消费序列号)
    case Seq = 'magic-chat-seq';

    // 录音消息
    case Recording = 'magic-chat-recording';
}

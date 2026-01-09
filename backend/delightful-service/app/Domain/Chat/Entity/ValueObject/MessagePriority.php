<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * message优先级.
 * 按照 rabbitmq 的suggestion,most大优先级not超过5
 * different优先级的messagewillbe投递to对应的queue中.
 */
enum MessagePriority: int
{
    // 待定,defaultvalue
    case Tbd = 0;

    // 低
    case Low = 2;

    // 中
    case Medium = 3;

    // 高
    case High = 4;

    // most高
    case Highest = 5;
}

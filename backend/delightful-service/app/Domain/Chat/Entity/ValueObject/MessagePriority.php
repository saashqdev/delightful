<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * message优先level.
 * 按照 rabbitmq 的suggestion,most大优先levelnot超过5
 * different优先level的messagewillbe投递to对应的queuemiddle.
 */
enum MessagePriority: int
{
    // 待定,defaultvalue
    case Tbd = 0;

    // 低
    case Low = 2;

    // middle
    case Medium = 3;

    // 高
    case High = 4;

    // most高
    case Highest = 5;
}

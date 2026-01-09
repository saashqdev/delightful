<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * message优先level.
 * 按照 rabbitmq suggestion,mostbig优先levelnot超pass5
 * different优先levelmessagewillbe投递toto应queuemiddle.
 */
enum MessagePriority: int
{
    // 待定,defaultvalue
    case Tbd = 0;

    // low
    case Low = 2;

    // middle
    case Medium = 3;

    // high
    case High = 4;

    // mosthigh
    case Highest = 5;
}

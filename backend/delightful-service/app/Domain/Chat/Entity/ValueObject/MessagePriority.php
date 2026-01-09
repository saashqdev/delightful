<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * messageprioritylevel.
 * according to rabbitmq suggestion,mostbigprioritylevelnot超pass5
 * differentprioritylevelmessagewillbedelivertoto应queuemiddle.
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

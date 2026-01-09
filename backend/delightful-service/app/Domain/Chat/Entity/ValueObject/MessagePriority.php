<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * message优先级.
 * 按照 rabbitmq 的建议,最大优先级不超过5
 * 不同优先级的message会被投递到对应的queue中.
 */
enum MessagePriority: int
{
    // 待定,默认值
    case Tbd = 0;

    // 低
    case Low = 2;

    // 中
    case Medium = 3;

    // 高
    case High = 4;

    // 最高
    case Highest = 5;
}

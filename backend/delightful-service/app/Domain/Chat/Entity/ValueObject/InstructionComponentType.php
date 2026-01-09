<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum InstructionComponentType: int
{
    // 单option
    case Radio = 1;

    // 开关
    case Switch = 2;

    // 文本type
    case Text = 3;

    // statustype
    case Status = 4;
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum InstructionComponentType: int
{
    // 单选项
    case Radio = 1;

    // 开关
    case Switch = 2;

    // 文本类型
    case Text = 3;

    // status类型
    case Status = 4;
}

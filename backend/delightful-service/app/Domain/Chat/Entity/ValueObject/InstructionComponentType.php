<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum InstructionComponentType: int
{
    // singleoption
    case Radio = 1;

    // switch
    case Switch = 2;

    // texttype
    case Text = 3;

    // statustype
    case Status = 4;
}

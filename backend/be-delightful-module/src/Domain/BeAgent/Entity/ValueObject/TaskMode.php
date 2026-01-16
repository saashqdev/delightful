<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

enum TaskMode: string
{
    case Chat = 'chat';
    case Plan = 'plan';
}

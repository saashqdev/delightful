<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperMagic\Domain\SuperAgent\Entity\ValueObject;

enum TaskMode: string
{
    case Chat = 'chat';
    case Plan = 'plan';
}

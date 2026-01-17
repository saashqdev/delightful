<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

enum ChatInstruction: string
{
    // Normal conversation
    case Normal = 'normal';

    // follow_up Follow-up question
    case FollowUp = 'follow_up';

    // interrupt Interrupt
    case Interrupted = 'interrupt';
}

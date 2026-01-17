<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

enum ChatInstruction: string
{
    // 正常对话，
    case Normal = 'normal';

    // follow_up 追问
    case FollowUp = 'follow_up';

    // interrupt 打断
    case Interrupted = 'interrupt';
}

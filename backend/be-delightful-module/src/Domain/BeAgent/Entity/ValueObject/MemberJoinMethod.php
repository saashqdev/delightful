<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

enum MemberJoinMethod: string
{
    case INTERNAL = 'internal';  // 团队内邀请
    case LINK = 'link';         // 邀请链接
}

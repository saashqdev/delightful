<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Admin\Entity\ValueObject;

enum AgentFilterType: int
{
    case ALL = -1;  // all部
    case SELECTED_DEFAULT_FRIEND = 1;  // 只showsettingfordefault好友columntable
    case NOT_SELECTED_DEFAULT_FRIEND = 2;  // 只show未besettingfordefault好友columntable
}

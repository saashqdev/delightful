<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Admin\Entity\ValueObject;

enum AgentFilterType: int
{
    case ALL = -1;  // 全部
    case SELECTED_DEFAULT_FRIEND = 1;  // 只展示setting为default好友的列table
    case NOT_SELECTED_DEFAULT_FRIEND = 2;  // 只展示未被setting为default好友的列table
}

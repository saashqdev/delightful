<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\ExecutionData;

enum ExecutionOrigin: string
{
    // 麦吉
    case Delightful = 'delightful';
    case DingTalk = 'dingTalk';
}

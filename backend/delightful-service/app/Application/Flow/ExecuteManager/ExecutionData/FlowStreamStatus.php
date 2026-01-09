<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\ExecutionData;

enum FlowStreamStatus: string
{
    // 未start
    case Pending = 'Pending';

    // conduct中
    case Processing = 'Processing';

    // end
    case Finished = 'Finished';

    public function isPending(): bool
    {
        return $this == self::Pending;
    }
}

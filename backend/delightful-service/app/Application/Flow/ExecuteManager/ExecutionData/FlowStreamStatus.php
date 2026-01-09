<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\ExecutionData;

enum FlowStreamStatus: string
{
    // notstart
    case Pending = 'Pending';

    // conductmiddle
    case Processing = 'Processing';

    // end
    case Finished = 'Finished';

    public function isPending(): bool
    {
        return $this == self::Pending;
    }
}

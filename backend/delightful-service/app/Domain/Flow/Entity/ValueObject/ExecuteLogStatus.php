<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject;

enum ExecuteLogStatus: int
{
    // preparerunline
    case Pending = 1;

    // runlinemiddle
    case Running = 2;

    // complete
    case Completed = 3;

    // fail
    case Failed = 4;

    // cancel
    case Canceled = 5;

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Failed]);
    }
}

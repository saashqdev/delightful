<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\AsyncEvent\Kernel\Event;

use Dtyq\AsyncEvent\Kernel\Persistence\Model\AsyncEventModel;

class AsyncEventRetryMaxEvent
{
    public AsyncEventModel $record;

    public function __construct(AsyncEventModel $record)
    {
        $this->record = $record;
    }
}

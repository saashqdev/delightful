<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\AsyncEvent\Kernel\Event;

use Delightful\AsyncEvent\Kernel\Persistence\Model\AsyncEventModel;

class AsyncEventRetryMaxEvent
{
    public AsyncEventModel $record;

    public function __construct(AsyncEventModel $record)
    {
        $this->record = $record;
    }
}

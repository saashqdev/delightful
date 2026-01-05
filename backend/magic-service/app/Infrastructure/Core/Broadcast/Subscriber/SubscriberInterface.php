<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Broadcast\Subscriber;

use Closure;

interface SubscriberInterface
{
    public function subscribe(string $channel, Closure $closure, bool $async = true): void;
}

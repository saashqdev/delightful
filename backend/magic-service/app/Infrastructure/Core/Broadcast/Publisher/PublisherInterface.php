<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Infrastructure\Core\Broadcast\Publisher;

interface PublisherInterface
{
    public function publish(string $channel, string $message);
}

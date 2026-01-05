<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Event;

use App\Domain\Chat\Entity\ValueObject\MessagePriority;

interface ChatEventInterface
{
    public function getPriority(): MessagePriority;

    public function setPriority(MessagePriority $priority): void;
}

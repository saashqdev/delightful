<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Cache;

enum CacheScope: string
{
    case User = 'user';
    case Topic = 'topic';
    case Agent = 'agent';
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\ValueObject;

enum StorageBucketType: string
{
    case Public = 'public';
    case Private = 'private';
    case SandBox = 'sandbox';
}

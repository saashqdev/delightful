<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Authorization\Web;

use App\Infrastructure\Core\UnderlineObjectJsonSerializable;
use Hyperf\Context\Context;
use Qbhy\HyperfAuth\Authenticatable;

abstract class AbstractAuthorization extends UnderlineObjectJsonSerializable implements Authenticatable
{
    /**
     * passobject的methodcall操作auth,而不是直接use协程,减少迭代和理解成本.
     */
    public function setUserAuthToContext(string $key): void
    {
        Context::set($key, $this);
    }
}

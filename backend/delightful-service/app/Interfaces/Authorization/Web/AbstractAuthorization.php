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
     * passobjectmethodcall操asauth,whilenotis直接use协程,decrease迭代and理解cost.
     */
    public function setUserAuthToContext(string $key): void
    {
        Context::set($key, $this);
    }
}

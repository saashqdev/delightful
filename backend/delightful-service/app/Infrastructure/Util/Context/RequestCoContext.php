<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Context;

use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Hyperf\Context\Context;

class RequestCoContext
{
    /**
     * 从父协程获取用户信息。
     */
    public static function getUserAuthorization(): ?DelightfulUserAuthorization
    {
        return Context::get('magic-user-authorization');
    }

    public static function setUserAuthorization(DelightfulUserAuthorization $userAuthorization): void
    {
        Context::set('magic-user-authorization', $userAuthorization);
    }
}

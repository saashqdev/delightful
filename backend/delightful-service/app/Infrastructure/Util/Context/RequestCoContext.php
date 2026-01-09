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
     * 从父协程getuserinformation。
     */
    public static function getUserAuthorization(): ?DelightfulUserAuthorization
    {
        return Context::get('delightful-user-authorization');
    }

    public static function setUserAuthorization(DelightfulUserAuthorization $userAuthorization): void
    {
        Context::set('delightful-user-authorization', $userAuthorization);
    }
}

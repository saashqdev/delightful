<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Traits;

use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestCoContext;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Qbhy\HyperfAuth\Authenticatable;

trait DelightfulUserAuthorizationTrait
{
    /**
     * @return DelightfulUserAuthorization
     */
    protected function getAuthorization(): Authenticatable
    {
        $magicUserAuthorization = RequestCoContext::getUserAuthorization();
        if (! $magicUserAuthorization) {
            ExceptionBuilder::throw(UserErrorCode::ACCOUNT_ERROR);
        }
        return $magicUserAuthorization;
    }
}

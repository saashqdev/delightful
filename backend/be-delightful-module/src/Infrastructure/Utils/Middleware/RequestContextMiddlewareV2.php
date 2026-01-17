<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\Utils\Middleware;

use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Hyperf\HttpMessage\Exception\UnauthorizedHttpException;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\AuthManager;
use Throwable;

class RequestContextMiddlewareV2 extends RequestContextMiddleware
{
    /**
     * @return DelightfulUserAuthorization
     */
    protected function getAuthorization(): Authenticatable
    {
        try {
            return di(AuthManager::class)->guard(name: 'web')->user();
        } catch (BusinessException $exception) {
            // If it's a business exception, rethrow directly without changing the exception type
            throw new UnauthorizedHttpException($exception->getMessage(), 401);
        } catch (Throwable $exception) {
            ExceptionBuilder::throw(UserErrorCode::ACCOUNT_ERROR, throwable: $exception);
        }
    }
}

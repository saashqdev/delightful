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
use App\Interfaces\Authorization\Web\MagicUserAuthorization;
use Hyperf\HttpMessage\Exception\UnauthorizedHttpException;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\AuthManager;
use Throwable;

class RequestContextMiddlewareV2 extends RequestContextMiddleware
{
    /**
     * @return MagicUserAuthorization
     */
    protected function getAuthorization(): Authenticatable
    {
        try {
            return di(AuthManager::class)->guard(name: 'web')->user();
        } catch (BusinessException $exception) {
            // 如果是业务异常，直接抛出，不改变异常类型
            throw new UnauthorizedHttpException($exception->getMessage(), 401);
        } catch (Throwable $exception) {
            ExceptionBuilder::throw(UserErrorCode::ACCOUNT_ERROR, throwable: $exception);
        }
    }
}

<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils\Middleware;

use App\ErrorCode\user ErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Middleware\RequestContextMiddleware;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Hyperf\HttpMessage\Exception\UnauthorizedHttpException;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\AuthManager;
use Throwable;

class RequestContextMiddlewareV2 extends RequestContextMiddleware 
{
 /** * @return Magicuser Authorization */ 
    protected function getAuthorization(): Authenticatable 
{
 try 
{
 return di(AuthManager::class)->guard(name: 'web')->user(); 
}
 catch (BusinessException $exception) 
{
 // If it is a business exception, throw directly without changing exception type throw new UnauthorizedHttpException($exception->getMessage(), 401); 
}
 catch (Throwable $exception) 
{
 ExceptionBuilder::throw(user ErrorCode::ACCOUNT_ERROR, throwable: $exception); 
}
 
}
 
}
 

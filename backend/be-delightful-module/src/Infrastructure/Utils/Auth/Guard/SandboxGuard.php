<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils\Auth\Guard;

use App\ErrorCode\user ErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Interfaces\Authorization\Web\SandboxAuthorization;
use Hyperf\Codec\Json;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Redis\Redis;
use Psr\Log\LoggerInterface;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\Guard\AbstractAuthGuard;
use Throwable;

class SandboxGuard extends AbstractAuthGuard 
{
 #[Inject] 
    protected LoggerInterface $logger; #[Inject] 
    protected Redis $redis; 
    public function login(Authenticatable $user) 
{
 
}
 
    public function user(): ?Authenticatable 
{
 $request = di(RequestInterface::class); $logger = di(LoggerInterface::class); $token = $request->header('token', ''); $userId = $request->header('user-id', ''); if (empty($token) || empty($userId)) 
{
 ExceptionBuilder::throw(user ErrorCode::TOKEN_NOT_FOUND); 
}
 $cacheKey = 'auth_user:' . md5($token . $userId); $cachedResult = $this->redis->get($cacheKey); if ($cachedResult) 
{
 $user = unserialize($cachedResult, ['allowed_classes' => [Magicuser Authorization::class]]); if ($user instanceof Magicuser Authorization) 
{
 return $user; 
}
 
}
 try 
{
 /** @var null|SandboxAuthorization $user */ $user = $this->userProvider->retrieveByCredentials([ 'token' => $token, 'userId' => $userId, ]); if ($user === null) 
{
 ExceptionBuilder::throw(user ErrorCode::USER_NOT_EXIST); 
}
 if (empty($user->getOrganizationCode())) 
{
 ExceptionBuilder::throw(user ErrorCode::ORGANIZATION_NOT_EXIST); 
}
 if ($user instanceof Magicuser Authorization) 
{
 $this->redis->setex($cacheKey, 60, serialize($user)); 
}
 $logger->info('SandboxGuard user Authorization', ['uid' => $user->getId(), 'name' => $user->getRealName(), 'organization' => $user->getOrganizationCode()]); return $user; 
}
 catch (Throwable $exception) 
{
 $errMsg = [ 'file' => $exception->getFile(), 'line' => $exception->getLine(), 'message' => $exception->getMessage(), 'code' => $exception->getCode(), 'trace' => $exception->getTraceAsString(), ]; $logger->error('Internaluser Guard ' . Json::encode($errMsg)); throw $exception; 
}
 
}
 
    public function logout() 
{
 
}
 
}
 

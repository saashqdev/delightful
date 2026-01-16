<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils\Middleware;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
/** * Sandbox Token Authentication Middleware * Used to validate token for internal API calls from sandbox. */

class SandboxTokenAuthMiddleware implements MiddlewareInterface 
{
 
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface 
{
 // // Get token from header // $token = $request->getHeader('token')[0] ?? ''; // // Validate token is not empty // if (empty($token)) 
{
 // ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_required'); // 
}
 // // Validate tokenEqualConfigurationValue // if ($token !== config('super-magic.sandbox.token', '')) 
{
 // ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_invalid'); // 
}
 // Validate ThroughContinueprocess Request return $handler->handle($request); 
}
 
}
 

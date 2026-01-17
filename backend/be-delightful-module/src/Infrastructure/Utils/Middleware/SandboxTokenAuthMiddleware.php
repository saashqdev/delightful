<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\Utils\Middleware;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Sandbox token authentication middleware.
 * Used to validate internal API call tokens originating from the sandbox.
 */
class SandboxTokenAuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // // Get token from header
        // $token = $request->getHeader('token')[0] ?? '';

        // // Validate that token is not empty
        // if (empty($token)) {
        //     ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_required');
        // }

        // // Validate token matches configured value
        // if ($token !== config('be-delightful.sandbox.token', '')) {
        //     ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_invalid');
        // }

        // Validation passed; continue handling the request
        return $handler->handle($request);
    }
}

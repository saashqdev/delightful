<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Middleware;

use App\Infrastructure\Util\Context\CoContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestIdMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // ifoutside部haverequest-id，then直接use
        $requestId = $request->getHeaderLine('request-id');
        if ($requestId) {
            CoContext::setRequestId($requestId);
        }

        // processheader的Delightful-User-Id 存in["usi_8","xxxxxxxxx"]issue，merge为"usi_8xxxxxxxxx"
        $delightfulUserId = $request->getHeader('delightful-user-id');
        if ($delightfulUserId && count($delightfulUserId) > 1) {
            $delightfulUserId = implode('', $delightfulUserId);
            $request = $request->withHeader('delightful-user-id', $delightfulUserId);
        }
        return $handler->handle($request);
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Auth\Guard;

use App\Domain\Chat\DTO\Request\Common\DelightfulContext;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Hyperf\WebSocketServer\Context as WebSocketContext;
use Qbhy\HyperfAuth\Authenticatable;
use Throwable;

/**
 * needparse websocket contextmiddle token information,therefore跟 WebUserGuard different.
 */
class WebsocketChatUserGuard extends WebUserGuard
{
    /**
     * @return DelightfulUserAuthorization
     * @throws Throwable
     */
    public function user(): Authenticatable
    {
        /** @var DelightfulContext $delightfulContext */
        $delightfulContext = WebSocketContext::get(DelightfulContext::class);
        $userAuthToken = $delightfulContext?->getAuthorization();
        if (empty($userAuthToken)) {
            ExceptionBuilder::throw(UserErrorCode::TOKEN_NOT_FOUND);
        }
        $organizationCode = $delightfulContext->getOrganizationCode();
        if (empty($organizationCode)) {
            ExceptionBuilder::throw(UserErrorCode::ORGANIZATION_NOT_EXIST);
        }
        // getuserinformationcache
        $contextKey = $this->resultKey($userAuthToken);
        if ($result = WebSocketContext::get($contextKey)) {
            if ($result instanceof Throwable) {
                throw $result;
            }
            if ($result instanceof DelightfulUserAuthorization) {
                return $result;
            }
            ExceptionBuilder::throw(UserErrorCode::TOKEN_NOT_FOUND);
        }
        // downsurfacethissegmentactualcallis DelightfulUserAuthorization  retrieveById method
        /** @var DelightfulUserAuthorization $user */
        $user = $this->userProvider->retrieveByCredentials([
            'authorization' => $userAuthToken,
            'organizationCode' => $organizationCode,
            'beDelightfulAgentUserId' => $delightfulContext->getBeDelightfulAgentUserId(),
        ]);
        if (empty($user->getOrganizationCode())) {
            ExceptionBuilder::throw(UserErrorCode::ORGANIZATION_NOT_EXIST);
        }
        $user->setUserAuthToContext($contextKey);
        return $user;
    }
}

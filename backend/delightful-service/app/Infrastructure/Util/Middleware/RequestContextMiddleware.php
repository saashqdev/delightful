<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Middleware;

use App\Application\ModelGateway\Service\LLMAppService;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestCoContext;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qbhy\HyperfAuth\Authenticatable;
use Qbhy\HyperfAuth\AuthManager;
use Throwable;

class RequestContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LLMAppService $llmAppService
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // notice!foriterationcan控,onlycanin api layerto协程context赋value
        $accessToken = $request->getHeaderLine('api-key');

        if (! empty($accessToken)) {
            $delightfulUserAuthorization = $this->getOpenPlatformAuthorization($request, $accessToken);
        } else {
            $delightfulUserAuthorization = $this->getAuthorization();
        }
        // willuserinformation存入协程context,convenient api layerget
        RequestCoContext::setUserAuthorization($delightfulUserAuthorization);
        return $handler->handle($request);
    }

    /**
     * @return DelightfulUserAuthorization
     */
    protected function getAuthorization(): Authenticatable
    {
        try {
            return di(AuthManager::class)->guard(name: 'web')->user();
        } catch (BusinessException $exception) {
            // ifisbusinessexception,directlythrow,notalterexceptiontype
            throw $exception;
        } catch (Throwable $exception) {
            ExceptionBuilder::throw(UserErrorCode::ACCOUNT_ERROR, throwable: $exception);
        }
    }

    protected function getOpenPlatformAuthorization(ServerRequestInterface $request, string $accessToken): DelightfulUserAuthorization
    {
        try {
            $delightfulUserId = $request->getHeaderLine('delightful-user-id');
            $organizationCode = $request->getHeaderLine('delightful-organization-code');

            $businessParams = [];
            if (! empty($organizationCode) && ! empty($delightfulUserId)) {
                $businessParams = [
                    'organization_code' => $organizationCode,
                    'user_id' => $delightfulUserId,
                ];
            }
            $modelGatewayDataIsolation = $this->llmAppService->createModelGatewayDataIsolationByAccessToken($accessToken, $businessParams);
            $delightfulUserAuthorization = new DelightfulUserAuthorization();
            $delightfulUserAuthorization->setId($modelGatewayDataIsolation->getCurrentUserId());
            $delightfulUserAuthorization->setOrganizationCode($modelGatewayDataIsolation->getCurrentOrganizationCode());
            $delightfulUserAuthorization->setDelightfulId($modelGatewayDataIsolation->getDelightfulId());
            $delightfulUserAuthorization->setThirdPlatformUserId($modelGatewayDataIsolation->getThirdPlatformUserId());
            $delightfulUserAuthorization->setThirdPlatformOrganizationCode($modelGatewayDataIsolation->getThirdPlatformOrganizationCode());
            $delightfulUserAuthorization->setDelightfulEnvId($modelGatewayDataIsolation->getEnvId());
            $delightfulUserAuthorization->setUserType(UserType::Human);
            return $delightfulUserAuthorization;
        } catch (BusinessException $exception) {
            // ifisbusinessexception,directlythrow,notalterexceptiontype
            throw $exception;
        } catch (Throwable $exception) {
            ExceptionBuilder::throw(UserErrorCode::ACCOUNT_ERROR, throwable: $exception);
        }
    }
}

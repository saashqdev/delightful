<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Authentication\Facade;

use App\Application\Chat\Service\DelightfulUserContactAppService;
use App\Domain\Authentication\DTO\LoginCheckDTO;
use App\Infrastructure\Core\Contract\Session\SessionInterface;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Redis\Redis;

#[ApiResponse(version: 'low_code')]
class AuthenticationApi
{
    #[Inject]
    protected Redis $redis;

    #[Inject]
    protected DelightfulUserContactAppService $userAppService;

    #[Inject]
    protected SessionInterface $sessionInterface;

    public function authCheck(RequestInterface $request): array
    {
        // according tologin码，getto应accessenvironment，go麦吉/day书validationwhetherhavepermission
        $authorization = (string) $request->input('authorization', '');
        if (empty($authorization)) {
            $authorization = (string) $request->header('authorization');
        }
        $organizationCode = $request->header('organization-code');
        $loginCode = (string) $request->input('login_code', '');
        $loginCheckDTO = new LoginCheckDTO();
        $loginCheckDTO->setAuthorization($authorization);
        $loginCheckDTO->setLoginCode($loginCode);
        $loginCheckDTO->setOrganizationCode($organizationCode);
        $delightfulEnvironmentEntity = $this->userAppService->getLoginCodeEnv($loginCheckDTO->getLoginCode());
        return $this->sessionInterface->LoginCheck($loginCheckDTO, $delightfulEnvironmentEntity, $loginCheckDTO->getOrganizationCode());
    }

    /**
     * front端from身业务use，get authorization to应private识别码
     */
    public function authEnvironment(RequestInterface $request): array
    {
        $authorization = (string) $request->header('authorization');
        $delightfulEnvironmentEntity = $this->userAppService->getEnvByAuthorization($authorization);
        return [
            'login_code' => $delightfulEnvironmentEntity?->getEnvironmentCode(),
        ];
    }
}

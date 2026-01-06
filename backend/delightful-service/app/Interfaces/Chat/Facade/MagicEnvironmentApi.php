<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Facade;

use App\Application\Chat\Service\DelightfulEnvironmentAppService;
use App\Application\Kernel\SuperPermissionEnum;
use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Auth\PermissionChecker;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * 处理与天书的组织架构同步.
 */
#[ApiResponse('low_code')]
class DelightfulEnvironmentApi extends AbstractApi
{
    public function __construct(
        private readonly DelightfulEnvironmentAppService $magicEnvironmentAppService,
    ) {
    }

    public function getDelightfulEnvironments(RequestInterface $request): array
    {
        $ids = $request->input('ids', []);
        $this->authCheck();
        return $this->magicEnvironmentAppService->getDelightfulEnvironments($ids);
    }

    public function createDelightfulEnvironment(RequestInterface $request): array
    {
        $data = $request->all();
        $this->authCheck();
        $magicEnvironmentEntity = new DelightfulEnvironmentEntity($data);
        $this->magicEnvironmentAppService->createDelightfulEnvironment($magicEnvironmentEntity);
        return $magicEnvironmentEntity->toArray();
    }

    public function updateDelightfulEnvironment(RequestInterface $request): array
    {
        $data = $request->all();
        $this->authCheck();
        $magicEnvironmentEntity = new DelightfulEnvironmentEntity($data);
        $this->magicEnvironmentAppService->updateDelightfulEnvironment($magicEnvironmentEntity);
        return $magicEnvironmentEntity->toArray();
    }

    private function authCheck(): void
    {
        $authorization = $this->getAuthorization();
        if (! PermissionChecker::mobileHasPermission($authorization->getMobile(), SuperPermissionEnum::Delightful_ENV_MANAGEMENT)) {
            ExceptionBuilder::throw(ChatErrorCode::OPERATION_FAILED);
        }
    }
}

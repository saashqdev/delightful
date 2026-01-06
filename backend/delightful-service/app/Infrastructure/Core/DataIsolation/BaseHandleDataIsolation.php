<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\DataIsolation;

use App\ErrorCode\AuthenticationErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Qbhy\HyperfAuth\Authenticatable;

class BaseHandleDataIsolation implements HandleDataIsolationInterface
{
    public function handleByAuthorization(Authenticatable $authorization, BaseDataIsolation $baseDataIsolation, int &$envId): void
    {
        match (true) {
            $authorization instanceof DelightfulUserAuthorization => $this->createByDelightfulUserAuthorization($authorization, $baseDataIsolation, $envId),
            default => ExceptionBuilder::throw(AuthenticationErrorCode::Error, 'unknown_authorization_type'),
        };
    }

    protected function createByDelightfulUserAuthorization(DelightfulUserAuthorization $authorization, BaseDataIsolation $baseDataIsolation, int &$envId): void
    {
        $baseDataIsolation->setCurrentOrganizationCode($authorization->getOrganizationCode());
        $baseDataIsolation->setCurrentUserId($authorization->getId());
        $baseDataIsolation->setDelightfulId($authorization->getDelightfulId());
        $baseDataIsolation->setThirdPlatformUserId($authorization->getThirdPlatformUserId());
        $baseDataIsolation->setThirdPlatformOrganizationCode($authorization->getThirdPlatformOrganizationCode());
        $envId = $authorization->getDelightfulEnvId();
    }
}

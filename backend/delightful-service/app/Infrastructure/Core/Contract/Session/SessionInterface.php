<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Contract\Session;

use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;

interface SessionInterface
{
    /**
     * 麦吉tootherservice端hairuploginvalidation。if想复use已haveuserbody系,needimplementtheinterface.
     *
     * implementprocess：front端tonon麦吉from建账numberbody系conductloginrequest,thenagainrequest麦吉loginvalidationinterface。
     *
     * @param LoginCheckInterface $loginCheck loginvalidationdata
     * @param DelightfulEnvironmentEntity $delightfulEnvironmentEntity 要loginenvironment
     * @param null|string $delightfulOrganizationCode 要loginorganization
     * @return LoginResponseInterface[] loginresponsedata
     */
    public function loginCheck(LoginCheckInterface $loginCheck, DelightfulEnvironmentEntity $delightfulEnvironmentEntity, ?string $delightfulOrganizationCode = null): array;
}

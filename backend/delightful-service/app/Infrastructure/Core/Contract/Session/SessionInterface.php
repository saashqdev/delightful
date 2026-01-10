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
     * Delightfultootherserviceclienthairuploginvalidation.ifwant to replyusealreadyhaveuserbodysystem,needimplementtheinterface.
     *
     * implementprocess:frontclienttononDelightfulfromcreate accountnumberbodysystemconductloginrequest,thenagainrequestDelightfulloginvalidationinterface.
     *
     * @param LoginCheckInterface $loginCheck loginvalidationdata
     * @param DelightfulEnvironmentEntity $delightfulEnvironmentEntity wantloginenvironment
     * @param null|string $delightfulOrganizationCode wantloginorganization
     * @return LoginResponseInterface[] loginresponsedata
     */
    public function loginCheck(LoginCheckInterface $loginCheck, DelightfulEnvironmentEntity $delightfulEnvironmentEntity, ?string $delightfulOrganizationCode = null): array;
}

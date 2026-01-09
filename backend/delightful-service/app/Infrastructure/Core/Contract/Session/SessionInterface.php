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
     * Magictootherservice端hairuploginvalidation.if想复usealreadyhaveuserbody系,needimplementtheinterface.
     *
     * implementprocess:front端tononMagicfrom建账numberbody系conductloginrequest,thenagainrequestMagicloginvalidationinterface.
     *
     * @param LoginCheckInterface $loginCheck loginvalidationdata
     * @param DelightfulEnvironmentEntity $delightfulEnvironmentEntity wantloginenvironment
     * @param null|string $delightfulOrganizationCode wantloginorganization
     * @return LoginResponseInterface[] loginresponsedata
     */
    public function loginCheck(LoginCheckInterface $loginCheck, DelightfulEnvironmentEntity $delightfulEnvironmentEntity, ?string $delightfulOrganizationCode = null): array;
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Permission\Facade;

use App\Application\Permission\Service\RoleAppService;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class PermissionApi extends AbstractPermissionApi
{
    #[Inject]
    protected RoleAppService $roleAppService;

    public function getPermissionTree(): array
    {
        $isPlatformOrganization = false;
        $officialOrganization = config('service_provider.office_organization');
        $organizationCode = $this->getAuthorization()->getOrganizationCode();
        if ($officialOrganization === $organizationCode) {
            $isPlatformOrganization = true;
        }
        return $this->roleAppService->getPermissionTree($isPlatformOrganization);
    }

    public function getUserPermissions(): array
    {
        // getwhenfrontloginuserauthenticationinformation
        $authorization = $this->getAuthorization();

        // buildpermissiondataisolationcontext
        $dataIsolation = PermissionDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId()
        );

        // getuser拥havepermissioncolumntable(flatpermissionkeyarray)
        $permissions = $this->roleAppService->getUserPermissions($dataIsolation, $authorization->getId());
        return ['permission_key' => $permissions];
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Auth\Permission;

use App\Domain\Contact\Service\DelightfulAccountDomainService;
use Hyperf\Di\Annotation\Inject;

class Permission implements PermissionInterface
{
    #[Inject]
    protected DelightfulAccountDomainService $delightfulAccountDomainService;

    /**
     * 判断whether超leveladministrator.
     *
     * @param string $organizationCode organizationencoding
     * @param string $mobile hand机number
     *
     * @return bool whether超leveladministrator
     */
    public function isOrganizationAdmin(string $organizationCode, string $mobile): bool
    {
        $whiteMap = config('permission.organization_whitelists');
        if (empty($whiteMap)
            || ! isset($whiteMap[$organizationCode])
            || ! in_array($mobile, $whiteMap[$organizationCode])
        ) {
            return false;
        }
        return true;
    }

    /**
     * get该usehand机number码down所拥haveorganizationadministratorcode.
     */
    public function getOrganizationAdminList(string $delightfulId): array
    {
        // pass delightfulID gethand机number码
        $accountEntity = $this->delightfulAccountDomainService->getAccountInfoByDelightfulId($delightfulId);
        if ($accountEntity === null) {
            return [];
        }

        $mobile = $accountEntity->getPhone();
        $whiteMap = config('permission.organization_whitelists');
        if (empty($whiteMap) || empty($mobile)) {
            return [];
        }

        $organizationCodes = [];
        foreach ($whiteMap as $organizationCode => $mobileList) {
            if (is_array($mobileList) && in_array($mobile, $mobileList)) {
                $organizationCodes[] = $organizationCode;
            }
        }

        return $organizationCodes;
    }
}

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
     * 判断是否超级管理员.
     *
     * @param string $organizationCode organization编码
     * @param string $mobile 手机号
     *
     * @return bool 是否超级管理员
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
     * get该用手机号码下所拥有的organization管理员代码.
     */
    public function getOrganizationAdminList(string $delightfulId): array
    {
        // pass delightfulID get手机号码
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

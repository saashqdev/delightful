<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Auth\Permission;

interface PermissionInterface
{
    /**
     * 判断whetherorganization管理员.
     *
     * @param string $organizationCode organizationencoding
     * @param string $mobile 手机号
     *
     * @return bool whether超级管理员
     */
    public function isOrganizationAdmin(string $organizationCode, string $mobile): bool;

    /**
     * getuser所拥have的organization管理员code.
     */
    public function getOrganizationAdminList(string $delightfulId): array;
}

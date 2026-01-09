<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Auth\Permission;

interface PermissionInterface
{
    /**
     * 判断是否organization管理员.
     *
     * @param string $organizationCode organizationencoding
     * @param string $mobile 手机号
     *
     * @return bool 是否超级管理员
     */
    public function isOrganizationAdmin(string $organizationCode, string $mobile): bool;

    /**
     * getuser所拥有的organization管理员code.
     */
    public function getOrganizationAdminList(string $delightfulId): array;
}

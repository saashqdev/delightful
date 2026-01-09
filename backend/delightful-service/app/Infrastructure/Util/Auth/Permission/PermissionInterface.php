<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Auth\Permission;

interface PermissionInterface
{
    /**
     * 判断whetherorganizationadministrator.
     *
     * @param string $organizationCode organizationencoding
     * @param string $mobile hand机number
     *
     * @return bool whether超leveladministrator
     */
    public function isOrganizationAdmin(string $organizationCode, string $mobile): bool;

    /**
     * getuser所拥have的organizationadministratorcode.
     */
    public function getOrganizationAdminList(string $delightfulId): array;
}

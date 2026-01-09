<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Auth\Permission;

interface PermissionInterface
{
    /**
     * judgewhetherorganizationadministrator.
     *
     * @param string $organizationCode organizationencoding
     * @param string $mobile hand机number
     *
     * @return bool whether超leveladministrator
     */
    public function isOrganizationAdmin(string $organizationCode, string $mobile): bool;

    /**
     * getuser所拥haveorganizationadministratorcode.
     */
    public function getOrganizationAdminList(string $delightfulId): array;
}

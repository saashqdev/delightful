<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Authentication\Repository\Facade;

use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;

interface AuthenticationRepositoryInterface
{
    /**
     * passmailboxfind账number.
     */
    public function findAccountByEmail(string $email): ?AccountEntity;

    /**
     * passhand机numberfind账number.
     */
    public function findAccountByPhone(string $stateCode, string $phone): ?AccountEntity;

    /**
     * passDelightfulIDandorganizationencodingfinduser.
     */
    public function findUserByDelightfulIdAndOrganization(string $delightfulId, ?string $organizationCode = null): ?DelightfulUserEntity;
}

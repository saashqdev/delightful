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
     * 通过邮箱查找账号.
     */
    public function findAccountByEmail(string $email): ?AccountEntity;

    /**
     * 通过手机号查找账号.
     */
    public function findAccountByPhone(string $stateCode, string $phone): ?AccountEntity;

    /**
     * 通过DelightfulID和organization编码查找user.
     */
    public function findUserByDelightfulIdAndOrganization(string $delightfulId, ?string $organizationCode = null): ?DelightfulUserEntity;
}

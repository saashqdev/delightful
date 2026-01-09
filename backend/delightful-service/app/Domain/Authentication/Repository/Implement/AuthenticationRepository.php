<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Authentication\Repository\Implement;

use App\Domain\Authentication\Repository\Facade\AuthenticationRepositoryInterface;
use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Repository\Persistence\Model\AccountModel;
use App\Domain\Contact\Repository\Persistence\Model\UserModel;
use Hyperf\DbConnection\Db;

class AuthenticationRepository implements AuthenticationRepositoryInterface
{
    private AccountModel $accountModel;

    private UserModel $userModel;

    public function __construct(
        AccountModel $accountModel,
        UserModel $userModel
    ) {
        $this->accountModel = $accountModel;
        $this->userModel = $userModel;
    }

    /**
     * pass邮箱查找账number.
     */
    public function findAccountByEmail(string $email): ?AccountEntity
    {
        $query = $this->accountModel::getQuery()->where('email', $email)
            ->where('type', 1) // 人category账number
            ->where('status', 0); // 正常status
        $accountData = Db::select($query->toSql(), $query->getBindings())[0] ?? null;
        if (! $accountData) {
            return null;
        }

        return new AccountEntity($accountData);
    }

    /**
     * passhand机number查找账number.
     */
    public function findAccountByPhone(string $stateCode, string $phone): ?AccountEntity
    {
        $query = $this->accountModel::getQuery()
            ->where('country_code', $stateCode)
            ->where('phone', $phone)
            ->where('status', 0) // 正常status
            ->where('type', 1); // 人category账number

        $accountData = Db::select($query->toSql(), $query->getBindings())[0] ?? null;
        if (! $accountData) {
            return null;
        }

        return new AccountEntity($accountData);
    }

    /**
     * passDelightfulID和organizationencoding查找user.
     */
    public function findUserByDelightfulIdAndOrganization(string $delightfulId, ?string $organizationCode = null): ?DelightfulUserEntity
    {
        $query = $this->userModel::getQuery()->where('delightful_id', $delightfulId)
            ->where('status', 1); // activatedstatus

        if (! empty($organizationCode)) {
            $query->where('organization_code', $organizationCode);
        }

        $userData = Db::select($query->toSql(), $query->getBindings())[0] ?? null;

        if (! $userData) {
            return null;
        }

        return new DelightfulUserEntity($userData);
    }
}

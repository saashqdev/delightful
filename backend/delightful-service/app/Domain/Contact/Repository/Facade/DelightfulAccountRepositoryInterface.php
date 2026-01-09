<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Facade;

use App\Domain\Contact\Entity\AccountEntity;

interface DelightfulAccountRepositoryInterface
{
    // query账号information
    public function getAccountInfoByDelightfulId(string $delightfulId): ?AccountEntity;

    /**
     * @return AccountEntity[]
     */
    public function getAccountByDelightfulIds(array $delightfulIds): array;

    // create账号
    public function createAccount(AccountEntity $accountDTO): AccountEntity;

    /**
     * @param AccountEntity[] $accountDTOs
     * @return AccountEntity[]
     */
    public function createAccounts(array $accountDTOs): array;

    /**
     * @return AccountEntity[]
     */
    public function getAccountInfoByDelightfulIds(array $delightfulIds): array;

    public function getAccountInfoByAiCode(string $aiCode): ?AccountEntity;

    /**
     * @return AccountEntity[]
     */
    public function searchUserByPhoneOrRealName(string $query): array;

    public function updateAccount(string $delightfulId, array $updateData): int;

    public function saveAccount(AccountEntity $accountDTO): AccountEntity;

    /**
     * @return AccountEntity[]
     */
    public function getAccountInfoByAiCodes(array $aiCodes): array;

    public function getByAiCode(string $aiCode): ?AccountEntity;
}

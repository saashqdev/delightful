<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Authentication\Service;

use App\Domain\Authentication\Repository\Facade\AuthenticationRepositoryInterface;
use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Token\Entity\DelightfulTokenEntity;
use App\Domain\Token\Entity\ValueObject\DelightfulTokenType;
use App\Domain\Token\Repository\Facade\DelightfulTokenRepositoryInterface;
use App\ErrorCode\AuthenticationErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Carbon\Carbon;

readonly class AuthenticationDomainService
{
    public function __construct(
        private AuthenticationRepositoryInterface $authenticationRepository,
        private DelightfulTokenRepositoryInterface $delightfulTokenRepository,
        private PasswordService $passwordService
    ) {
    }

    /**
     * verifyaccountnumbervoucher
     */
    public function verifyAccountCredentials(string $email, string $password): ?AccountEntity
    {
        $account = $this->authenticationRepository->findAccountByEmail($email);

        if (! $account) {
            return null;
        }

        // verifypassword
        if (! $this->passwordService->verifyPassword($password, $account->getPassword())) {
            ExceptionBuilder::throw(AuthenticationErrorCode::PasswordError);
        }

        return $account;
    }

    /**
     * inorganizationmiddlefinduser.
     */
    public function findUserInOrganization(string $delightfulId, ?string $organizationCode = null): ?DelightfulUserEntity
    {
        return $this->authenticationRepository->findUserByDelightfulIdAndOrganization($delightfulId, $organizationCode);
    }

    /**
     * generateaccountnumbertoken.
     *
     * byatDelightfulsupportotheraccountnumberbodysystemaccess,thereforefrontclientprocessis,firstgosomeaccountnumberbodysystemlogin,againbyDelightfulmakeloginvalidation.
     * therefore,even ifuseDelightfulfromown accountnumberbodysystem,alsoneedcomplythisprocess.
     */
    public function generateAccountToken(string $delightfulId): string
    {
        // write token table
        $authorization = IdGenerator::getUniqueIdSha256();
        $delightfulTokenEntity = new DelightfulTokenEntity();
        $delightfulTokenEntity->setType(DelightfulTokenType::Account);
        $delightfulTokenEntity->setTypeRelationValue($delightfulId);
        $delightfulTokenEntity->setToken($authorization);
        // default 30 day
        $carbon = Carbon::now()->addDays(30);
        $delightfulTokenEntity->setExpiredAt($carbon->toDateTimeString());
        $this->delightfulTokenRepository->createToken($delightfulTokenEntity);
        return $authorization;
    }
}

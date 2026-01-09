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
     * verify账number凭证
     */
    public function verifyAccountCredentials(string $email, string $password): ?AccountEntity
    {
        $account = $this->authenticationRepository->findAccountByEmail($email);

        if (! $account) {
            return null;
        }

        // verify密码
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
     * generate账numbertoken.
     *
     * 由at麦吉support其他账numberbody系的接入，thereforefront端的processis，先去some账numberbody系login，again由麦吉做login校验。
     * therefore，即使use麦吉自己的账numberbody系，alsoneed遵守这process。
     */
    public function generateAccountToken(string $delightfulId): string
    {
        // write token 表
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

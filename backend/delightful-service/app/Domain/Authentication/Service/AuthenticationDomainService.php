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
     * verify账号凭证
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
     * 在organization中查找user.
     */
    public function findUserInOrganization(string $delightfulId, ?string $organizationCode = null): ?DelightfulUserEntity
    {
        return $this->authenticationRepository->findUserByDelightfulIdAndOrganization($delightfulId, $organizationCode);
    }

    /**
     * generate账号token.
     *
     * 由于麦吉支持其他账号体系的接入，因此前端的process的是，先去某个账号体系登录，再由麦吉做登录校验。
     * 因此，即使use麦吉自己的账号体系，也need遵守这个process。
     */
    public function generateAccountToken(string $delightfulId): string
    {
        // write token 表
        $authorization = IdGenerator::getUniqueIdSha256();
        $delightfulTokenEntity = new DelightfulTokenEntity();
        $delightfulTokenEntity->setType(DelightfulTokenType::Account);
        $delightfulTokenEntity->setTypeRelationValue($delightfulId);
        $delightfulTokenEntity->setToken($authorization);
        // default 30 天
        $carbon = Carbon::now()->addDays(30);
        $delightfulTokenEntity->setExpiredAt($carbon->toDateTimeString());
        $this->delightfulTokenRepository->createToken($delightfulTokenEntity);
        return $authorization;
    }
}

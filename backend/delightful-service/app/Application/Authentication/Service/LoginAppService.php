<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Authentication\Service;

use App\Domain\Authentication\Repository\Facade\AuthenticationRepositoryInterface;
use App\Domain\Authentication\Service\AuthenticationDomainService;
use App\Domain\Authentication\Service\PasswordService;
use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\OrganizationEnvironment\Repository\Facade\EnvironmentRepositoryInterface;
use App\Domain\Token\Repository\Facade\DelightfulTokenRepositoryInterface;
use App\ErrorCode\AuthenticationErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authentication\DTO\CheckLoginRequest;
use App\Interfaces\Authentication\DTO\CheckLoginResponse;

readonly class LoginAppService
{
    public function __construct(
        protected DelightfulTokenRepositoryInterface $tokenRepository,
        protected EnvironmentRepositoryInterface $environmentRepository,
        protected AuthenticationDomainService $authenticationDomainService,
        protected DelightfulUserDomainService $userDomainService,
        protected AuthenticationRepositoryInterface $authenticationRepository,
        protected PasswordService $passwordService
    ) {
    }

    /**
     * checkuserlogininfoand颁hairtoken.
     */
    public function login(CheckLoginRequest $request): CheckLoginResponse
    {
        // verifyaccountinfoandgetaccount
        $account = $this->verifyAndGetAccount($request);

        // verifyuserinorganizationinsidewhether存in
        $user = $this->verifyAndGetUserInOrganization($account, $request->getOrganizationCode());

        // generatetoken
        $authorization = $this->authenticationDomainService->generateAccountToken($account->getDelightfulId());

        // buildresponse
        return $this->buildLoginResponse($authorization, $account, $user);
    }

    /**
     * according tologintypeverifyaccountinfoandreturnaccount实body.
     */
    private function verifyAndGetAccount(CheckLoginRequest $request): AccountEntity
    {
        return match ($request->getType()) {
            'phone_password' => $this->verifyPhoneAccount($request),
            default => $this->verifyEmailAccount($request),
        };
    }

    /**
     * verifyhand机numberlogin.
     */
    private function verifyPhoneAccount(CheckLoginRequest $request): AccountEntity
    {
        $account = $this->authenticationRepository->findAccountByPhone(
            $request->getStateCode(),
            $request->getPhone()
        );

        if (! $account) {
            ExceptionBuilder::throw(AuthenticationErrorCode::AccountNotFound);
        }

        // verifypassword
        if (! $this->passwordService->verifyPassword($request->getPassword(), $account->getPassword())) {
            ExceptionBuilder::throw(AuthenticationErrorCode::PasswordError);
        }
        return $account;
    }

    /**
     * verify邮箱login.
     */
    private function verifyEmailAccount(CheckLoginRequest $request): AccountEntity
    {
        $account = $this->authenticationRepository->findAccountByEmail($request->getEmail());

        if (! $account) {
            ExceptionBuilder::throw(AuthenticationErrorCode::AccountNotFound);
        }

        // verifypassword
        // useSHA256validationpassword
        if (! $this->passwordService->verifyPassword($request->getPassword(), $account->getPassword())) {
            ExceptionBuilder::throw(AuthenticationErrorCode::PasswordError);
        }
        return $account;
    }

    /**
     * verifyuserinorganizationinsidewhether存in.
     */
    private function verifyAndGetUserInOrganization(AccountEntity $account, string $organizationCode): DelightfulUserEntity
    {
        $user = $this->authenticationDomainService->findUserInOrganization(
            $account->getDelightfulId(),
            $organizationCode
        );

        if (! $user) {
            ExceptionBuilder::throw(AuthenticationErrorCode::UserNotFound);
        }

        return $user;
    }

    /**
     * buildloginresponse.
     */
    private function buildLoginResponse(string $authorization, AccountEntity $account, DelightfulUserEntity $user): CheckLoginResponse
    {
        $response = new CheckLoginResponse();

        // process国家codeformat
        $stateCode = $this->formatStateCode($account->getStateCode() ?? '+86');

        // builduserdata
        $userData = [
            'id' => $user->getUserId(),
            'real_name' => $user->getNickname(),
            'avatar' => $user->getAvatarUrl(),
            'description' => $user->getDescription(),
            'position' => '',
            'mobile' => $account->getPhone(),
            'state_code' => $stateCode,
        ];

        // buildresponsedata
        $responseData = [
            'access_token' => $authorization,
            'bind_phone' => ! empty($account->getPhone()),
            'is_perfect_password' => false,
            'user_info' => $userData,
        ];

        $response->setData($responseData);

        return $response;
    }

    /**
     * format化国家code,ensureby+openhead.
     */
    private function formatStateCode(string $stateCode): string
    {
        return str_starts_with($stateCode, '+') ? $stateCode : '+' . $stateCode;
    }
}

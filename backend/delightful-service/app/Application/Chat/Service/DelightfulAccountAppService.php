<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\AccountStatus;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\Contact\Service\DelightfulAccountDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Service\DelightfulFlowDomainService;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Kernel\Assembler\FileAssembler;
use Delightful\BeDelightful\Domain\BeAgent\Constant\AgentConstant;
use Random\RandomException;
use RedisException;
use Throwable;

class DelightfulAccountAppService extends AbstractAppService
{
    public function __construct(
        protected readonly DelightfulUserDomainService $userDomainService,
        protected readonly DelightfulAccountDomainService $accountDomainService,
        protected readonly LockerInterface $locker,
        protected readonly DelightfulFlowDomainService $delightfulFlowDomainService,
    ) {
    }

    /**
     * @throws RedisException
     */
    public function register(string $stateCode, string $phone, string $verifyCode, string $password): array
    {
        return $this->accountDomainService->humanRegister($stateCode, $phone, $verifyCode, $password);
    }

    public function addUserAndAccount(DelightfulUserEntity $userDTO, AccountEntity $accountDTO): void
    {
        $this->accountDomainService->addUserAndAccount($userDTO, $accountDTO);
    }

    /**
     * @throws RedisException
     * @throws RandomException
     */
    public function sendVerificationCode(string $stateCode, string $phone, string $type): array
    {
        return $this->accountDomainService->sendVerificationCode($stateCode, $phone, $type);
    }

    /**
     * @param null|AccountEntity $accountDTO 支持启用/禁用智能体
     * @throws Throwable
     */
    public function aiRegister(DelightfulUserEntity $userDTO, DelightfulUserAuthorization $authorization, string $aiCode, ?AccountEntity $accountDTO = null): DelightfulUserEntity
    {
        $userDTO->setAvatarUrl(FileAssembler::formatPath($userDTO->getAvatarUrl()));

        $spinLockKey = 'chat:aiRegister:lock:' . $aiCode;
        $spinLockKeyOwner = random_bytes(8);
        // 自旋lock
        $this->locker->spinLock($spinLockKey, $spinLockKeyOwner, 3);
        try {
            $userDTO->setUserType(UserType::Ai);
            if (empty($authorization->getDelightfulId()) && ! empty($authorization->getId())) {
                $delightfulInfo = $this->userDomainService->getUserById($authorization->getId());
                $authorization->setDelightfulId($delightfulInfo?->getDelightfulId());
                $authorization->setOrganizationCode($delightfulInfo?->getOrganizationCode());
            }
            // pass aiCode query delightful_flows 表get所属organization。
            // 注意超级麦吉when前是作为one没有write delightful_flows data库的 flow 存在。 SUPER_DELIGHTFUL_CODE write了 accounts 表。
            if ($aiCode !== AgentConstant::SUPER_DELIGHTFUL_CODE) {
                $disabledDataIsolation = FlowDataIsolation::create()->disabled();
                $delightfulFlowEntity = $this->delightfulFlowDomainService->getByCode($disabledDataIsolation, $aiCode);
                if (! $delightfulFlowEntity) {
                    ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
                }
                $authorization->setOrganizationCode($delightfulFlowEntity->getOrganizationCode());
            }

            $dataIsolation = $this->createDataIsolation($authorization);
            // 智能体账号information
            if (! isset($accountDTO)) {
                $accountDTO = new AccountEntity();
            }
            ! $accountDTO->getRealName() && $accountDTO->setRealName($userDTO->getNickname());
            ! $accountDTO->getAiCode() && $accountDTO->setAiCode($aiCode);
            ! $accountDTO->getStatus() && $accountDTO->setStatus(AccountStatus::Normal);
            return $this->accountDomainService->aiRegister($userDTO, $dataIsolation, $accountDTO);
        } finally {
            $this->locker->release($spinLockKey, $spinLockKeyOwner);
        }
    }

    public function loginByPhoneAndCode(string $mobile, string $code)
    {
        // checkverify码是否correct
    }

    public function getAccountInfoByDelightfulId(string $delightfulId): ?AccountEntity
    {
        return $this->accountDomainService->getAccountInfoByDelightfulId($delightfulId);
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Application\Chat\Service\MagicAccountAppService;
use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\MagicUserEntity;
use App\Domain\Contact\Entity\ValueObject\AccountStatus;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\Contact\Service\MagicUserDomainService;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\MagicUserAuthorization;
use Delightful\BeDelightful\Domain\BeAgent\Constant\AgentConstant;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class AccountAppService extends AbstractAppService
{
    protected LoggerInterface $logger;

    public function __construct(
        private readonly MagicAccountAppService $magicAccountAppService,
        private readonly MagicUserDomainService $userDomainService,
        protected LoggerFactory $loggerFactory,
    ) {
        $this->logger = $this->loggerFactory->get(get_class($this));
    }

    /**
     * @throws Throwable
     */
    public function initAccount(string $organizationCode): array
    {
        // 查询是否已经存在了超级麦吉账号，如果存在则不更新
        $dataIsolation = new DataIsolation();
        $dataIsolation->setCurrentOrganizationCode($organizationCode);
        $aiUserEntity = $this->userDomainService->getByAiCode($dataIsolation, AgentConstant::BE_DELIGHTFUL_CODE);
        if (! empty($aiUserEntity)) {
            ExceptionBuilder::throw(GenericErrorCode::SystemError, 'account.super_magic_already_created');
        }

        // 初始化账号
        $accountDTO = new AccountEntity();
        $accountDTO->setAiCode(AgentConstant::BE_DELIGHTFUL_CODE);
        $accountDTO->setStatus(AccountStatus::Normal);
        $accountDTO->setRealName('超级麦吉');

        $userDTO = new MagicUserEntity();
        $userDTO->setAvatarUrl('default');
        $userDTO->setNickName('超级麦吉');
        $userDTO->setDescription('超级麦吉账号，勿动');

        $authorization = new MagicUserAuthorization();
        $authorization->setOrganizationCode($organizationCode);
        $authorization->setUserType(UserType::Human);
        try {
            $userEntity = $this->magicAccountAppService->aiRegister($userDTO, $authorization, AgentConstant::BE_DELIGHTFUL_CODE, $accountDTO);
            return $userEntity->toArray();
        } catch (Throwable $e) {
            $this->logger->error('初始化超级麦吉账号失败，原因：' . $e->getMessage());
            throw $e;
        }
    }
}

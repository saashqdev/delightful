<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Application\Chat\Service\MagicAccountAppService;
use App\Domain\Contact\Entity\AccountEntity;
use App\Domain\Contact\Entity\Magicuser Entity;
use App\Domain\Contact\Entity\ValueObject\AccountStatus;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\user Type;
use App\Domain\Contact\Service\Magicuser DomainService;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Domain\SuperAgent\Constant\AgentConstant;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class AccountAppService extends AbstractAppService 
{
 
    protected LoggerInterface $logger; 
    public function __construct( 
    private readonly MagicAccountAppService $magicAccountAppService, 
    private readonly Magicuser DomainService $userDomainService, 
    protected LoggerFactory $loggerFactory, ) 
{
 $this->logger = $this->loggerFactory->get(get_class($this)); 
}
 /** * @throws Throwable */ 
    public function initAccount(string $organizationCode): array 
{
 // query whether already Existed Super MaggieAccountIfExistUpdate $dataIsolation = new DataIsolation(); $dataIsolation->setcurrent OrganizationCode($organizationCode); $aiuser Entity = $this->userDomainService->getByAiCode($dataIsolation, AgentConstant::SUPER_MAGIC_CODE); if (! empty($aiuser Entity)) 
{
 ExceptionBuilder::throw(GenericErrorCode::SystemError, 'account.super_magic_already_created'); 
}
 // InitializeAccount $accountDTO = new AccountEntity(); $accountDTO->setAiCode(AgentConstant::SUPER_MAGIC_CODE); $accountDTO->setStatus(AccountStatus::Normal); $accountDTO->setRealName('Super Maggie'); $userDTO = new Magicuser Entity(); $userDTO->setAvatarUrl('default'); $userDTO->setNickName('Super Maggie'); $userDTO->setDescription('Super Maggie account, do not modify'); $authorization = new Magicuser Authorization(); $authorization->setOrganizationCode($organizationCode); $authorization->setuser Type(user Type::Human); try 
{
 $userEntity = $this->magicAccountAppService->aiRegister($userDTO, $authorization, AgentConstant::SUPER_MAGIC_CODE, $accountDTO); return $userEntity->toArray(); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Initialize Super Maggie account failed, reason:' . $e->getMessage()); throw $e; 
}
 
}
 
}
 

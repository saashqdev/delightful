<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Authorization\Web;

use App\Domain\Contact\Service\MagicAccountDomainService;
use App\Domain\Contact\Service\Magicuser DomainService;
use App\ErrorCode\ChatErrorCode;
use App\ErrorCode\user ErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Qbhy\HyperfAuth\Authenticatable;

class SandboxAuthorization extends Magicuser Authorization 
{
 
    public 
    static function retrieveById($key): ?Authenticatable 
{
 $token = $key['token'] ?? ''; $userId = $key['userId'] ?? ''; if (empty($token) || empty($userId)) 
{
 ExceptionBuilder::throw(user ErrorCode::USER_NOT_EXIST); 
}
 // todo Dynamic token $sandboxToken = config('super-magic.sandbox.token', ''); if (empty($sandboxToken) || $sandboxToken !== $token) 
{
 ExceptionBuilder::throw(user ErrorCode::TOKEN_NOT_FOUND, 'token error'); 
}
 $userDomainService = di(Magicuser DomainService::class); $accountDomainService = di(MagicAccountDomainService::class); $userEntity = $userDomainService->getuser ById($userId); if ($userEntity === null) 
{
 ExceptionBuilder::throw(ChatErrorCode::LOGIN_FAILED); 
}
 $magicAccountEntity = $accountDomainService->getAccountinfo ByMagicId($userEntity->getMagicId()); if ($magicAccountEntity === null) 
{
 ExceptionBuilder::throw(ChatErrorCode::LOGIN_FAILED); 
}
 $magicuser info = new self(); $magicuser info ->setId($userEntity->getuser Id()); $magicuser info ->setNickname($userEntity->getNickname()); $magicuser info ->setAvatar($userEntity->getAvatarUrl()); $magicuser info ->setStatus((string) $userEntity->getStatus()->value); $magicuser info ->setOrganizationCode($userEntity->getOrganizationCode()); $magicuser info ->setMagicId($userEntity->getMagicId()); $magicuser info ->setMobile($magicAccountEntity->getPhone()); $magicuser info ->setCountryCode($magicAccountEntity->getCountryCode()); $magicuser info ->setRealName($magicAccountEntity->getRealName()); $magicuser info ->setuser Type($userEntity->getuser Type()); return $magicuser info ; 
}
 
}
 

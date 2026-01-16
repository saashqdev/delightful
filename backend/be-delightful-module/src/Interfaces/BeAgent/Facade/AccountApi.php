<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\Application\File\Service\FileAppService;
use App\Domain\Contact\Entity\ValueObject\user Type;
use App\Infrastructure\Util\Context\RequestContext;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\Service\AccountAppService;
use Delightful\BeDelightful\Domain\SuperAgent\Constant\AgentConstant;
use Hyperf\HttpServer\Contract\RequestInterface;
#[ApiResponse('low_code')]

class AccountApi extends AbstractApi 
{
 
    public function __construct( 
    private readonly AccountAppService $accountAppService, 
    protected RequestInterface $request, 
    private readonly FileAppService $fileAppService, ) 
{
 parent::__construct($request); 
}
 
    public function initAccount(RequestContext $requestContext): array 
{
 $token = $this->request->input('token', ''); $organizationCode = $this->request->input('organization_code', ''); if ($token !== md5(AgentConstant::SUPER_MAGIC_CODE)) 
{
 return ['result' => 'token failed']; 
}
 return $this->accountAppService->initAccount($organizationCode); 
}
 
    public function getStsToken(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setAuthorization($this->request->header('authorization', '')); $requestContext->setuser Authorization($this->getAuthorization()); $userAuthorization = new Magicuser Authorization(); $userAuthorization->setId($requestContext->getuser Id()); $userAuthorization->setOrganizationCode($requestContext->getOrganizationCode()); $userAuthorization->setuser Type(user Type::Human); $dir = $this->request->input('dir', ''); return $this->fileAppService->getStstemporary CredentialV2($requestContext->getOrganizationCode(), 'private', $dir, 3600 * 2); 
}
 
}
 

<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Share\Facade;

use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Util\Context\RequestContext;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\Share\Service\ResourceShareAppService;
use Delightful\BeDelightful\Interfaces\Share\DTO\Request\CreateShareRequestDTO;
use Delightful\BeDelightful\Interfaces\Share\DTO\Request\GetShareDetailDTO;
use Delightful\BeDelightful\Interfaces\Share\DTO\Request\Resourcelist RequestDTO;
use Exception;
use Hyperf\HttpServer\Contract\RequestInterface;
use Qbhy\HyperfAuth\AuthManager;
#[ApiResponse('low_code')]

class ShareApi extends AbstractApi 
{
 
    public function __construct( 
    protected RequestInterface $request, 
    protected ResourceShareAppService $shareAppService, ) 
{
 
}
 /** * CreateResourceShare. * * @param RequestContext $requestContext RequestContext * @return array Shareinfo * @throws BusinessException IfParameterInvalidor FailedThrowException * @throws Exception */ 
    public function createShare(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); $userAuthorization = $requestContext->getuser Authorization(); $dto = CreateShareRequestDTO::fromRequest($this->request); $data = $this->shareAppService->createShare($userAuthorization, $dto); return $data->toArray(); 
}
 /** * cancel ResourceShare. * * @param RequestContext $requestContext RequestContext * @param string $id ShareID * @return array cancel Result * @throws BusinessException IfParameterInvalidor FailedThrowException * @throws Exception */ 
    public function cancelShareByResourceId(RequestContext $requestContext, string $id): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); $userAuthorization = $requestContext->getuser Authorization(); $this->shareAppService->cancelShareByResourceId($userAuthorization, $id); return [ 'id' => $id, ]; 
}
 
    public function checkShare(RequestContext $requestContext, string $shareCode): array 
{
 // try Getuser info yes HaveMay beas null try 
{
 $requestContext->setuser Authorization(di(AuthManager::class)->guard(name: 'web')->user()); $userAuthorization = $requestContext->getuser Authorization(); 
}
 catch (Exception $exception) 
{
 $userAuthorization = null; 
}
 return $this->shareAppService->checkShare($userAuthorization, $shareCode); 
}
 
    public function getShareDetail(RequestContext $requestContext, string $shareCode): array 
{
 // try Getuser info yes HaveMay beas null try 
{
 $requestContext->setuser Authorization(di(AuthManager::class)->guard(name: 'web')->user()); $userAuthorization = $requestContext->getuser Authorization(); 
}
 catch (Exception $exception) 
{
 $userAuthorization = null; 
}
 $dto = GetShareDetailDTO::fromRequest($this->request); return $this->shareAppService->getShareDetail($userAuthorization, $shareCode, $dto); 
}
 
    public function getSharelist (RequestContext $requestContext): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $userAuthorization = $requestContext->getuser Authorization(); $dto = Resourcelist RequestDTO::fromRequest($this->request); return $this->shareAppService->getSharelist ($userAuthorization, $dto); 
}
 /** * ThroughSharecodeGetShareinfo . * * @param RequestContext $requestContext RequestContext * @param string $shareCode Sharecode * @return array Shareinfo * @throws BusinessException IfParameterInvalidor FailedThrowException * @throws Exception */ 
    public function getShareByCode(RequestContext $requestContext, string $shareCode): array 
{
 // try Getuser info yes HaveMay beas null $requestContext->setuser Authorization($this->getAuthorization()); $userAuthorization = $requestContext->getuser Authorization(); // directly call including PasswordMethod - At /code/
{
shareCode
}
 route in Using $dto = $this->shareAppService->getShareWithPasswordByCode($userAuthorization, $shareCode); return $dto->toArray(); 
}
 
}
 

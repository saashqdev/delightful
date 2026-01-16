<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\Infrastructure\Util\Context\RequestContext;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\Service\FileEditingAppService;
use Hyperf\HttpServer\Contract\RequestInterface;
#[ApiResponse('low_code')]

class FileEditingApi extends AbstractApi 
{
 
    public function __construct( 
    private readonly FileEditingAppService $fileEditingAppService, 
    protected RequestInterface $request, ) 
{
 parent::__construct($request); 
}
 /** * JoinEdit. */ 
    public function joinEditing(RequestContext $requestContext, string $fileId): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // call ApplyService $this->fileEditingAppService->joinEditing($requestContext, (int) $fileId); return []; 
}
 /** * Edit. */ 
    public function leaveEditing(RequestContext $requestContext, string $fileId): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // call ApplyService $this->fileEditingAppService->leaveEditing($requestContext, (int) $fileId); return []; 
}
 /** * GetEdituser Quantity. */ 
    public function getEditinguser s(RequestContext $requestContext, string $fileId): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // call ApplyService $userCount = $this->fileEditingAppService->getEditinguser s($requestContext, (int) $fileId); return [ 'editing_user_count' => $userCount, ]; 
}
 
}
 

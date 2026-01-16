<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Util\Context\RequestContext;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\Service\TopicAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\WorkspaceAppService;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\WorkspaceArchiveStatus;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetWorkspaceTopicsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\SaveWorkspaceRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\Workspacelist RequestDTO;
use Hyperf\HttpServer\Contract\RequestInterface;
use Throwable;
#[ApiResponse('low_code')]

class WorkspaceApi extends AbstractApi 
{
 
    public function __construct( 
    protected RequestInterface $request, 
    protected WorkspaceAppService $workspaceAppService, 
    protected TopicAppService $topicAppService, ) 
{
 parent::__construct($request); 
}
 /** * Getworkspace list . */ 
    public function getWorkspacelist (RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setAuthorization($this->request->header('authorization', '')); $requestContext->setuser Authorization($this->getAuthorization()); // FromRequestCreateDTO $requestDTO = Workspacelist RequestDTO::fromRequest($this->request); // call ApplyService return $this->workspaceAppService->getWorkspacelist ($requestContext, $requestDTO)->toArray(); 
}
 /** * Getworkspace Details. */ 
    public function getWorkspaceDetail(RequestContext $requestContext, string $id): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // call ApplyService return $this->workspaceAppService->getWorkspaceDetail($requestContext, (int) $id)->toArray(); 
}
 /** * Getworkspace under topic list . */ 
    public function getWorkspaceTopics(RequestContext $requestContext): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $dto = GetWorkspaceTopicsRequestDTO::fromRequest($this->request); return $this->workspaceAppService->getWorkspaceTopics( $requestContext, $dto )->toArray(); 
}
 
    public function createWorkspace(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // FromRequestCreateDTO $requestDTO = SaveWorkspaceRequestDTO::fromRequest($this->request); // call ApplyServiceprocess return $this->workspaceAppService->createWorkspace($requestContext, $requestDTO)->toArray(); 
}
 
    public function updateWorkspace(RequestContext $requestContext, string $id): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // FromRequestCreateDTO $requestDTO = SaveWorkspaceRequestDTO::fromRequest($this->request); $requestDTO->id = $id; // call ApplyServiceprocess return $this->workspaceAppService->updateWorkspace($requestContext, $requestDTO)->toArray(); 
}
 /** * Saveworkspace Createor Update. * Interfaceprocess HTTPRequestResponseNot contain. * @throws Throwable */ 
    public function saveWorkspace(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // FromRequestCreateDTO $requestDTO = SaveWorkspaceRequestDTO::fromRequest($this->request); // call ApplyServiceprocess return $this->workspaceAppService->saveWorkspace($requestContext, $requestDTO)->toArray(); 
}
 /** * delete workspace delete . * Interfaceprocess HTTPRequestResponseNot contain. * * @param RequestContext $requestContext RequestContext * @return array Result * @throws BusinessException IfParameterInvalidor FailedThrowException */ 
    public function deleteWorkspace(RequestContext $requestContext, string $id): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // call ApplyServiceprocess $this->workspaceAppService->deleteWorkspace($requestContext, (int) $id); // Return NormalizeResponseResult return ['id' => $id]; 
}
 /** * Set workspace Status. * * @param RequestContext $requestContext RequestContext * @return array Result * @throws BusinessException IfParameterInvalidor FailedThrowException */ 
    public function setArchived(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // GetRequestParameter $workspaceIds = $this->request->input('workspace_ids', []); $isArchived = (int) $this->request->input('is_archived', WorkspaceArchiveStatus::NotArchived->value); // call ApplyServiceSet Status $result = $this->workspaceAppService->setWorkspaceArchived($requestContext, $workspaceIds, $isArchived); // Return NormalizeResponseResult return [ 'success' => $result, ]; 
}
 
}
 

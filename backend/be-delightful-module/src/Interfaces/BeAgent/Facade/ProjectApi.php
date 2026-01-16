<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Infrastructure\Util\Context\RequestContext;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\Service\ProjectAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\ProjectMemberAppService;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\ForkProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetParticipatedProjectsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetProjectAttachmentsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetProjectlist RequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\MoveProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpdateProjectPinRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpdateProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\ProjectItemDTO;
use Hyperf\HttpServer\Contract\RequestInterface;
use Qbhy\HyperfAuth\AuthManager;
/** * Project API. */ #[ApiResponse('low_code')]

class ProjectApi extends AbstractApi 
{
 
    public function __construct( 
    protected RequestInterface $request, 
    private readonly ProjectAppService $projectAppService, 
    private readonly ProjectMemberAppService $projectMemberAppService, ) 
{
 parent::__construct($request); 
}
 /** * Create project. */ 
    public function store(RequestContext $requestContext): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = CreateProjectRequestDTO::fromRequest($this->request); return $this->projectAppService->createProject($requestContext, $requestDTO); 
}
 /** * Update project. */ 
    public function update(RequestContext $requestContext, string $id): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = UpdateProjectRequestDTO::fromRequest($this->request); $requestDTO->id = $id; return $this->projectAppService->updateProject($requestContext, $requestDTO); 
}
 /** * delete project. */ 
    public function destroy(RequestContext $requestContext, string $id): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); $this->projectAppService->deleteProject($requestContext, (int) $id); return ['id' => $id]; 
}
 /** * Pin project. */ 
    public function pin(RequestContext $requestContext, string $id): array 
{
 // Set user authorization and context data $userAuthorization = $this->getAuthorization(); $requestContext->setuser Authorization($userAuthorization); $requestContext->setuser Id($userAuthorization->getId()); $requestContext->setOrganizationCode($userAuthorization->getOrganizationCode()); // 1. Convert toRequestDTOautomatic Validate $requestDTO = UpdateProjectPinRequestDTO::fromRequest($this->request); // 2. give Applicationprocess $this->projectMemberAppService->updateProjectPin($requestContext, (int) $id, $requestDTO); return []; 
}
 /** * Get project detail. */ 
    public function show(RequestContext $requestContext, string $id): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); $userId = $this->getAuthorization()->getId(); $project = $this->projectAppService->getProjectinfo ($requestContext, (int) $id); $hasProjectMember = $this->projectAppService->hasProjectMember($project->getId()); $userRole = $this->projectAppService->getProjectRoleByuser Id($project->getId(), $userId); $projectDTO = ProjectItemDTO::fromEntity($project, null, null, $hasProjectMember); return array_merge($projectDTO->toArray(), ['user_role' => $userRole]); 
}
 /** * Get project list. */ 
    public function index(RequestContext $requestContext): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = GetProjectlist RequestDTO::fromRequest($this->request); return $this->projectAppService->getProjectlist ($requestContext, $requestDTO); 
}
 /** * Get project topics. */ 
    public function getTopics(RequestContext $requestContext, string $id): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); // GetPagingParameter $page = (int) $this->request->input('page', 1); $pageSize = (int) $this->request->input('page_size', 10); return $this->projectAppService->getProjectTopics($requestContext, (int) $id, $page, $pageSize); 
}
 
    public function checkFilelist Update(RequestContext $requestContext, string $id): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); $dataIsolation = DataIsolation::create( $requestContext->getuser Authorization()->getOrganizationCode(), $requestContext->getuser Authorization()->getId() ); return $this->projectAppService->checkFilelist Update($requestContext, (int) $id, $dataIsolation); 
}
 /** * Get project attachments. */ 
    public function getProjectAttachments(RequestContext $requestContext, string $id): array 
{
 // Using fromRequest MethodFromRequestin Create DTOCanFromroute Parameterin Get project_id $dto = GetProjectAttachmentsRequestDTO::fromRequest($this->request); // temporary user UsingUpper limit $dto->setPageSize(10000); if (! empty($dto->getToken())) 
{
 // Token return $this->projectAppService->getProjectAttachmentsByAccessToken($dto); 
}
 // Loginuser Using $requestContext->setuser Authorization(di(AuthManager::class)->guard(name: 'web')->user()); return $this->projectAppService->getProjectAttachments($requestContext, $dto); 
}
 
    public function getCloudFiles(RequestContext $requestContext, string $id) 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); return $this->projectAppService->getCloudFiles($requestContext, (int) $id); 
}
 /** * Fork project. */ 
    public function fork(RequestContext $requestContext): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = ForkProjectRequestDTO::fromRequest($this->request); return $this->projectAppService->forkProject($requestContext, $requestDTO); 
}
 /** * check fork project status. */ 
    public function forkStatus(RequestContext $requestContext, string $id): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); return $this->projectAppService->checkForkProjectStatus($requestContext, (int) $id); 
}
 /** * Move project to another workspace. */ 
    public function moveProject(RequestContext $requestContext): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = MoveProjectRequestDTO::fromRequest($this->request); return $this->projectAppService->moveProject($requestContext, $requestDTO); 
}
 /** * Get participated projects. */ 
    public function getParticipatedProjects(RequestContext $requestContext): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = GetParticipatedProjectsRequestDTO::fromRequest($this->request); return $this->projectMemberAppService->getParticipatedProjects($requestContext, $requestDTO); 
}
 
}
 

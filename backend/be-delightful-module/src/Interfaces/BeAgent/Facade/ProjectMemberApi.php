<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Infrastructure\Util\Context\RequestContext;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\Service\ProjectMemberAppService;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\BatchUpdateMembersRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateMembersRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetCollaborationProjectlist RequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpdateProjectMembersRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpdateProjectPinRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpdateProjectShortcutRequestDTO;
use Hyperf\HttpServer\Contract\RequestInterface;
/** * Project Member API. */ #[ApiResponse('low_code')]

class ProjectMemberApi extends AbstractApi 
{
 
    public function __construct( 
    protected RequestInterface $request, 
    private readonly ProjectMemberAppService $projectMemberAppService, ) 
{
 parent::__construct($request); 
}
 /** * Getcollaboration Itemlist . */ 
    public function getCollaborationProjects(RequestContext $requestContext): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = GetCollaborationProjectlist RequestDTO::fromRequest($this->request); return $this->projectMemberAppService->getCollaborationProjects($requestContext, $requestDTO); 
}
 /** * UpdateItemMember. */ 
    public function updateMembers(RequestContext $requestContext, int $projectId): array 
{
 // Set user authorization and context data $userAuthorization = $this->getAuthorization(); $requestContext->setuser Authorization($userAuthorization); $requestContext->setuser Id($userAuthorization->getId()); $requestContext->setOrganizationCode($userAuthorization->getOrganizationCode()); // 1. Convert toRequestDTOautomatic Validate including route Parameterproject_id $requestDTO = UpdateProjectMembersRequestDTO::fromRequest($this->request); $requestDTO->setProjectId((string) $projectId); // 2. give Applicationprocess $this->projectMemberAppService->updateProjectMembers($requestContext, $requestDTO); return []; 
}
 /** * GetItemMember. */ 
    public function getMembers(RequestContext $requestContext, int $projectId): array 
{
 // Set user authorization and context data $userAuthorization = $this->getAuthorization(); $requestContext->setuser Authorization($userAuthorization); $requestContext->setuser Id($userAuthorization->getId()); $requestContext->setOrganizationCode($userAuthorization->getOrganizationCode()); // Create and set DataIsolation $dataIsolation = DataIsolation::create( $userAuthorization->getOrganizationCode(), $userAuthorization->getId() ); $requestContext->setDataIsolation($dataIsolation); // give Applicationprocess $responseDTO = $this->projectMemberAppService->getProjectMembers($requestContext, $projectId); // Return DTOConvertArrayFormat return ['members' => $responseDTO->toArray()]; 
}
 /** * UpdateItempinned Status. */ 
    public function updateProjectPin(RequestContext $requestContext, string $project_id): array 
{
 // Set user authorization and context data $userAuthorization = $this->getAuthorization(); $requestContext->setuser Authorization($userAuthorization); $requestContext->setuser Id($userAuthorization->getId()); $requestContext->setOrganizationCode($userAuthorization->getOrganizationCode()); // 1. Convert toRequestDTOautomatic Validate $requestDTO = UpdateProjectPinRequestDTO::fromRequest($this->request); // 2. give Applicationprocess $this->projectMemberAppService->updateProjectPin($requestContext, (int) $project_id, $requestDTO); return []; 
}
 /** * Getcollaboration Itemcreator list . */ 
    public function getCollaborationProjectcreator s(RequestContext $requestContext): array 
{
 // Set user authorization and context data $userAuthorization = $this->getAuthorization(); $requestContext->setuser Authorization($userAuthorization); $requestContext->setuser Id($userAuthorization->getId()); $requestContext->setOrganizationCode($userAuthorization->getOrganizationCode()); // Create and set DataIsolation $dataIsolation = DataIsolation::create( $userAuthorization->getOrganizationCode(), $userAuthorization->getId() ); $requestContext->setDataIsolation($dataIsolation); // give Applicationprocess $responseDTO = $this->projectMemberAppService->getCollaborationProjectcreator s($requestContext); // Return DTOConvertArrayFormat return $responseDTO->toArray(); 
}
 /** * Update project shortcut. */ 
    public function updateProjectShortcut(RequestContext $requestContext, string $project_id): array 
{
 // Set user authorization and context data $userAuthorization = $this->getAuthorization(); $requestContext->setuser Authorization($userAuthorization); $requestContext->setuser Id($userAuthorization->getId()); $requestContext->setOrganizationCode($userAuthorization->getOrganizationCode()); // 1. Convert toRequestDTOautomatic Validate $requestDTO = UpdateProjectShortcutRequestDTO::fromRequest($this->request); // 2. give Applicationprocess $this->projectMemberAppService->updateProjectShortcut($requestContext, (int) $project_id, $requestDTO); return []; 
}
 /** * AddItemMemberonly SupportOrganizationInternalMember. */ 
    public function createProjectMembers(RequestContext $requestContext, int $projectId): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = CreateMembersRequestDTO::fromRequest($this->request); $memberinfo s = $this->projectMemberAppService->createMembers($requestContext, $projectId, $requestDTO); return ['members' => $memberinfo s]; 
}
 /** * BatchUpdateMemberpermission . */ 
    public function updateProjectMemberRoles(RequestContext $requestContext, int $projectId): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = BatchUpdateMembersRequestDTO::fromRequest($this->request); return $this->projectMemberAppService->updateProjectMemberRoles($requestContext, $projectId, $requestDTO); 
}
 /** * Batchdelete Member. */ 
    public function deleteProjectMembers(RequestContext $requestContext, int $projectId): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $members = (array) $this->request->input('members', []); $this->projectMemberAppService->deleteMembers($requestContext, $projectId, $members); return []; 
}
 
}
 

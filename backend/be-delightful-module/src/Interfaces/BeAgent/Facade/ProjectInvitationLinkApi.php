<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\Infrastructure\Util\Context\RequestContext;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\Service\ProjectInvitationLinkAppService;
use Hyperf\HttpServer\Contract\RequestInterface;
/** * ItemInviteLinkAPI. * * ItemInviteLink */ #[ApiResponse('low_code')]

class ProjectInvitationLinkApi extends AbstractApi 
{
 
    public function __construct( 
    protected ProjectInvitationLinkAppService $invitationLinkAppService, 
    protected RequestInterface $request, ) 
{
 parent::__construct($request); 
}
 /** * GetItemInviteLinkinfo . */ 
    public function getInvitationLink(RequestContext $requestContext, int $projectId): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); $result = $this->invitationLinkAppService->getInvitationLink($requestContext, $projectId); return $result ? $result->toArray() : []; 
}
 /** * On/CloseInviteLink. */ 
    public function toggleInvitationLink(RequestContext $requestContext, int $projectId): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); $enabled = (bool) $this->request->input('enabled', false); return $this->invitationLinkAppService->toggleInvitationLink($requestContext, $projectId, $enabled)->toArray(); 
}
 /** * ResetInviteLink. */ 
    public function resetInvitationLink(RequestContext $requestContext, int $projectId): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); return $this->invitationLinkAppService->resetInvitationLink($requestContext, $projectId)->toArray(); 
}
 /** * Set PasswordProtected. */ 
    public function setPassword(RequestContext $requestContext, int $projectId): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); $enabled = (bool) $this->request->input('enabled', false); $password = $this->invitationLinkAppService->setPassword($requestContext, $projectId, $enabled); return ['password' => $password]; 
}
 /** * NewSet Password */ 
    public function resetPassword(RequestContext $requestContext, int $projectId): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); $password = $this->invitationLinkAppService->resetPassword($requestContext, $projectId); return ['password' => $password]; 
}
 /** * ModifyInviteLinkPassword */ 
    public function changePassword(RequestContext $requestContext, int $projectId): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); $newPassword = $this->request->input('password', ''); $password = $this->invitationLinkAppService->changePassword($requestContext, $projectId, $newPassword); return ['password' => $password]; 
}
 /** * Modifypermission Level. */ 
    public function updateDefaultJoinpermission (RequestContext $requestContext, int $projectId): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); $permission = $this->request->input('default_join_permission', 'viewer'); $this->invitationLinkAppService->updateDefaultJoinpermission ($requestContext, $projectId, $permission); return ['default_join_permission' => $permission]; 
}
 /** * ThroughTokenInviteLink. */ 
    public function getInvitationByToken(RequestContext $requestContext, string $token): array 
{
 // Externaluser need Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); return $this->invitationLinkAppService->getInvitationByToken($requestContext, $token)->toArray(); 
}
 /** * JoinItemExternaluser . */ 
    public function joinProject(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); $token = $this->request->input('token', ''); $password = $this->request->input('password'); return $this->invitationLinkAppService->joinProject($requestContext, $token, $password)->toArray(); 
}
 
}
 

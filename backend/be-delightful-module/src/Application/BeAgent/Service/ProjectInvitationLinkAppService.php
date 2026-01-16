<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Domain\Contact\Service\Magicuser DomainService;
use App\Domain\File\Service\FileDomainService;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use Carbon\Carbon;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Constant\ShareAccessType;
use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectMemberDomainService;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\PasswordCrypt;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\InvitationDetailResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\InvitationLinkResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\JoinProjectResponseDTO;
/** * ItemInviteLinkApplyService * * ItemInviteLinkrelated */

class ProjectInvitationLinkAppService extends AbstractAppService 
{
 
    public function __construct( 
    private ResourceShareDomainService $resourceShareDomainService, 
    private ProjectMemberDomainService $projectMemberDomainService, 
    private Magicuser DomainService $magicuser DomainService, 
    private FileDomainService $fileDomainService ) 
{
 
}
 /** * GetItemInviteLinkinfo . */ 
    public function getInvitationLink(RequestContext $requestContext, int $projectId): ?InvitationLinkResponseDTO 
{
 $organizationCode = $requestContext->getuser Authorization()->getOrganizationCode(); $currentuser Id = $requestContext->getuser Authorization()->getId(); // 1. Validate Itempermission $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); $shareEntity = $this->resourceShareDomainService->getShareByResource( (string) $projectId, ResourceType::ProjectInvitation->value ); if (! $shareEntity) 
{
 return null; 
}
 return InvitationLinkResponseDTO::fromEntity($shareEntity, $this->resourceShareDomainService); 
}
 /** * On/CloseInviteLink. */ 
    public function toggleInvitationLink(RequestContext $requestContext, int $projectId, bool $enabled): InvitationLinkResponseDTO 
{
 $currentuser Id = $requestContext->getuser Authorization()->getId(); $organizationCode = $requestContext->getuser Authorization()->getOrganizationCode(); // 1. Validate if has project management permission $project = $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); if ($project->getuser Id() !== $currentuser Id) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED, 'project.invalid_permission_level'); 
}
 // 2. FindHaveInviteShare $existingShare = $this->resourceShareDomainService->getShareByResource( (string) $projectId, ResourceType::ProjectInvitation->value ); if ($existingShare) 
{
 // UpdateHaveShareEnabled/DisabledStatus $savedShare = $this->resourceShareDomainService->toggleShareStatus( $existingShare->getId(), $enabled, $currentuser Id ); return InvitationLinkResponseDTO::fromEntity($savedShare, $this->resourceShareDomainService); 
}
 if (! $enabled) 
{
 // Ifdoes not existand CloseThrowException ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_NOT_FOUND); 
}
 // 3. create new InviteShare (Through ResourceShareDomainService) $shareEntity = $this->resourceShareDomainService->saveShare( (string) $projectId, ResourceType::ProjectInvitation->value, $currentuser Id, $organizationCode, [ 'resource_name' => $project->getProjectName(), 'share_type' => ShareAccessType::Internet->value, 'extra' => [ 'default_join_permission' => MemberRole::VIEWER->value, ], ], ); return InvitationLinkResponseDTO::fromEntity($shareEntity, $this->resourceShareDomainService); 
}
 /** * ResetInviteLink. */ 
    public function resetInvitationLink(RequestContext $requestContext, int $projectId): InvitationLinkResponseDTO 
{
 $currentuser Id = $requestContext->getuser Authorization()->getId(); $organizationCode = $requestContext->getuser Authorization()->getOrganizationCode(); // 1. Validate Itempermission $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); // 2. GetHaveInviteShare $shareEntity = $this->resourceShareDomainService->getShareByResource( (string) $projectId, ResourceType::ProjectInvitation->value ); if (! $shareEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_NOT_FOUND); 
}
 // 3. SaveUpdate $savedShare = $this->resourceShareDomainService->regenerateShareCodeById($shareEntity->getId()); return InvitationLinkResponseDTO::fromEntity($savedShare, $this->resourceShareDomainService); 
}
 /** * Set PasswordProtected. */ 
    public function setPassword(RequestContext $requestContext, int $projectId, bool $enabled): string 
{
 $currentuser Id = $requestContext->getuser Authorization()->getId(); $organizationCode = $requestContext->getuser Authorization()->getOrganizationCode(); // 1. Validate Itempermission $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); // 2. GetHaveInviteShare $shareEntity = $this->resourceShareDomainService->getShareByResource( (string) $projectId, ResourceType::ProjectInvitation->value ); if (! $shareEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_NOT_FOUND); 
}
 // 3. Set PasswordProtectedSwitch if ($enabled) 
{
 // OnPasswordProtected $password = $shareEntity->getPassword(); // IfDon't haveHistoryPasswordGenerate NewPassword if (empty($password)) 
{
 $plainPassword = ResourceShareEntity::generateRandomPassword(); 
}
 else 
{
 $plainPassword = PasswordCrypt::decrypt($password); 
}
 $this->resourceShareDomainService->changePasswordById($shareEntity->getId(), $plainPassword); return $plainPassword; 
}
 // ClosePasswordProtectedPassword $shareEntity->setIsPasswordEnabled(false); $shareEntity->setUpdatedAt(date('Y-m-d H:i:s')); $shareEntity->setUpdatedUid($currentuser Id); $this->resourceShareDomainService->saveShareByEntity($shareEntity); return ''; 
}
 /** * NewSet Password */ 
    public function resetPassword(RequestContext $requestContext, int $projectId): string 
{
 $currentuser Id = $requestContext->getuser Authorization()->getId(); $organizationCode = $requestContext->getuser Authorization()->getOrganizationCode(); // 1. Validate Itempermission $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); // 2. GetHaveInviteShare $shareEntity = $this->resourceShareDomainService->getShareByResource( (string) $projectId, ResourceType::ProjectInvitation->value ); if (! $shareEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_NOT_FOUND); 
}
 // 3. Generate NewPassword $newPassword = ResourceShareEntity::generateRandomPassword(); $this->resourceShareDomainService->changePasswordById($shareEntity->getId(), $newPassword); return $newPassword; 
}
 /** * ModifyInviteLinkPassword */ 
    public function changePassword(RequestContext $requestContext, int $projectId, string $newPassword): string 
{
 $currentuser Id = $requestContext->getuser Authorization()->getId(); $organizationCode = $requestContext->getuser Authorization()->getOrganizationCode(); // 1. Validate Itempermission $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); // 2. Validate PasswordLengthMaximum18 if (strlen($newPassword) > 18 || strlen($newPassword) < 3) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_PASSWORD_INCORRECT); 
}
 // 3. GetInviteLink $shareEntity = $this->resourceShareDomainService->getShareByResource( (string) $projectId, ResourceType::ProjectInvitation->value ); if (! $shareEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_NOT_FOUND); 
}
 // 4. UpdatePasswordEnabledPasswordProtected $this->resourceShareDomainService->changePasswordById($shareEntity->getId(), $newPassword); return $newPassword; 
}
 /** * Modifypermission Level. */ 
    public function updateDefaultJoinpermission (RequestContext $requestContext, int $projectId, string $permission): string 
{
 $currentuser Id = $requestContext->getuser Authorization()->getId(); $organizationCode = $requestContext->getuser Authorization()->getOrganizationCode(); // 1. Validate Itempermission $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); // 2. GetHaveInviteShare $shareEntity = $this->resourceShareDomainService->getShareByResource( (string) $projectId, ResourceType::ProjectInvitation->value ); if (! $shareEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_NOT_FOUND); 
}
 // 3. Validate Updatepermission Level MemberRole::validatepermission Level($permission); // 4. Update extra in default_join_permission $extra = $shareEntity->getExtra() ?? []; $extra['default_join_permission'] = $permission; $shareEntity->setExtra($extra); $shareEntity->setUpdatedAt(date('Y-m-d H:i:s')); $shareEntity->setUpdatedUid($currentuser Id); // 5. SaveUpdate $this->resourceShareDomainService->saveShareByEntity($shareEntity); return $permission; 
}
 /** * ThroughTokenGetInviteinfo Externaluser Preview. */ 
    public function getInvitationByToken(RequestContext $requestContext, string $token): InvitationDetailResponseDTO 
{
 $currentuser Id = $requestContext->getuser Authorization()->getId(); // 1. GetShareinfo $shareEntity = $this->resourceShareDomainService->getShareByCode($token); if (! $shareEntity || ! ResourceType::isProjectInvitation($shareEntity->getResourceType())) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_NOT_FOUND); 
}
 // 2. check whether Enabled if (! $shareEntity->getIsEnabled()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_DISABLED); 
}
 // 3. check whether validdelete  if (! $shareEntity->isValid()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_INVALID); 
}
 $resourceId = $shareEntity->getResourceId(); $projectId = (int) $resourceId; // 4. Validate Linkcreator whether HaveItempermission Existuser FromItemdelete $project = $this->getAccessibleProjectWithManager($projectId, $shareEntity->getCreatedUid(), $shareEntity->getOrganizationCode()); // 5. creator ID $creatorId = $project->getuser Id(); $iscreator = $creatorId === $currentuser Id; // 6. check MemberRelationship $hasJoined = $iscreator || $this->projectMemberDomainService->isProjectMemberByuser ($projectId, $currentuser Id); // 7. Getcreator info $creatorEntity = $this->magicuser DomainService->getByuser Id($creatorId); $creatorAvatarUrl = $creatorNickName = ''; if ($creatorEntity) 
{
 $creatorNickName = $creatorEntity->getNickname(); $creatorAvatarUrl = $this->fileDomainService->getLink('', $creatorEntity->getAvatarUrl()) ?? $creatorEntity->getAvatarUrl(); 
}
 // 8. From extra in Get default_join_permission $defaultJoinpermission = $shareEntity->getExtraAttribute('default_join_permission', 'viewer'); return InvitationDetailResponseDTO::fromArray([ 'project_id' => $resourceId, 'project_name' => $project->getProjectName(), 'project_description' => $project->getProjectDescription() ?? '', 'organization_code' => $project->getuser OrganizationCode() ?? '', 'creator_id' => $creatorId, 'creator_name' => $creatorNickName, 'creator_avatar' => $creatorAvatarUrl, 'default_join_permission' => $defaultJoinpermission , 'requires_password' => $shareEntity->getIsPasswordEnabled(), 'token' => $shareEntity->getShareCode(), 'has_joined' => $hasJoined, ]); 
}
 /** * JoinItemExternaluser . */ 
    public function joinProject(RequestContext $requestContext, string $token, ?string $password = null): JoinProjectResponseDTO 
{
 $currentuser Id = $requestContext->getuser Authorization()->getId(); // 1. Validate ShareLink $shareEntity = $this->resourceShareDomainService->getShareByCode($token); if (! $shareEntity || ! ResourceType::isProjectInvitation($shareEntity->getResourceType())) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_NOT_FOUND); 
}
 // 2. check whether Enabled if (! $shareEntity->getIsEnabled()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_DISABLED); 
}
 // 3. check whether validdelete  if (! $shareEntity->isValid()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_INVALID); 
}
 // 4. Validate Password if ($shareEntity->getIsPasswordEnabled()) 
{
 // LinkEnableded PasswordProtected if (empty($password)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_PASSWORD_INCORRECT); 
}
 if (! $this->resourceShareDomainService->verifyPassword($shareEntity, $password)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_PASSWORD_INCORRECT); 
}
 
}
 // 5. check whether already yes ItemMemberThroughDomainService $isExistingMember = $this->projectMemberDomainService->isProjectMemberByuser ( (int) $shareEntity->getResourceId(), $currentuser Id ); if ($isExistingMember) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVITATION_LINK_ALREADY_JOINED); 
}
 $projectId = (int) $shareEntity->getResourceId(); // 6. Validate Linkcreator whether HaveItempermission Existuser FromItemdelete $this->getAccessibleProjectWithManager($projectId, $shareEntity->getCreatedUid(), $shareEntity->getOrganizationCode()); // 7. From extra in Get default_join_permissionConvert toMemberRole $permission = $shareEntity->getExtraAttribute('default_join_permission', MemberRole::VIEWER->value); $memberRole = MemberRole::validatepermission Level($permission); // UsingDomainServiceAddMemberComply withDDD $projectMemberEntity = $this->projectMemberDomainService->addMemberByInvitation( $shareEntity->getResourceId(), $currentuser Id, $memberRole, $shareEntity->getOrganizationCode(), $shareEntity->getCreatedUid() // Inviter (invitation link creator) ); return JoinProjectResponseDTO::fromArray([ 'project_id' => $shareEntity->getResourceId(), 'user_role' => $memberRole->value, 'join_method' => $projectMemberEntity->getJoinMethod()->value, 'joined_at' => Carbon::now()->toDateTimeString(), ]); 
}
 
}
 

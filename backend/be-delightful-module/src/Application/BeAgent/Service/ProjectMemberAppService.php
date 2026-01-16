<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Domain\Contact\Entity\MagicDepartmentEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\DepartmentOption;
use App\Domain\Contact\Service\MagicDepartmentDomainService;
use App\Domain\Contact\Service\MagicDepartmentuser DomainService;
use App\Domain\Contact\Service\Magicuser DomainService;
use App\Domain\Provider\Service\ModelFilter\PackageFilterInterface;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectMemberEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberType;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ProjectMembersUpdatedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ProjectShortcutcancel ledEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ProjectShortcutSetEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectMemberDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\WorkspaceDomainService;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\BatchUpdateMembersRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateMembersRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetCollaborationProjectlist RequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetParticipatedProjectsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpdateProjectMembersRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpdateProjectPinRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpdateProjectShortcutRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\Collaborationcreator list ResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\CollaborationProjectlist ResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\CollaboratorMemberDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\creator info DTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\ParticipatedProjectlist ResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\ProjectMembersResponseDTO;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
/** * ItemMemberApplyService * * ItemMemberrelated Business process Not containConcrete */

class ProjectMemberAppService extends AbstractAppService 
{
 
    public function __construct( 
    private readonly LoggerInterface $logger, 
    private readonly ProjectDomainService $projectDomainService, 
    private readonly ProjectMemberDomainService $projectMemberDomainService, 
    private readonly MagicDepartmentDomainService $departmentDomainService, 
    private readonly MagicDepartmentuser DomainService $departmentuser DomainService, 
    private readonly Magicuser DomainService $magicuser DomainService, 
    private readonly WorkspaceDomainService $workspaceDomainService, 
    private readonly EventDispatcherInterface $eventDispatcher, 
    private readonly PackageFilterInterface $packageFilterService, ) 
{
 
}
 /** * UpdateItemMember. * * @param RequestContext $requestContext RequestContext * @param UpdateProjectMembersRequestDTO $requestDTO RequestDTO */ 
    public function updateProjectMembers( RequestContext $requestContext, UpdateProjectMembersRequestDTO $requestDTO ): void 
{
 $userAuthorization = $requestContext->getuser Authorization(); $currentuser Id = $userAuthorization->getId(); $organizationCode = $userAuthorization->getOrganizationCode(); // 1. DTOConvert toEntity $projectId = (int) $requestDTO->getProjectId(); $memberEntities = []; foreach ($requestDTO->getMembers() as $memberData) 
{
 $entity = new ProjectMemberEntity(); $entity->setTargetTypeFromString($memberData['target_type']); $entity->setTargetId($memberData['target_id']); $entity->setOrganizationCode($organizationCode); $entity->setInvitedBy($currentuser Id); $entity->setRole(MemberRole::MANAGE); $memberEntities[] = $entity; 
}
 // 2. Validate and get accessible projects $projectEntity = $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); // 3. give Domainprocess $this->projectMemberDomainService->updateProjectMembers( $requestContext->getOrganizationCode(), $projectId, $memberEntities ); // 4. PublishedItemMemberUpdatedEvent $projectMembersUpdatedEvent = new ProjectMembersUpdatedEvent($projectEntity, $memberEntities, $userAuthorization); $this->eventDispatcher->dispatch($projectMembersUpdatedEvent); // 5. record SuccessLog $this->logger->info('Project members updated successfully', [ 'project_id' => $projectId, 'operator_id' => $requestContext->getuser Id(), 'member_count' => count($memberEntities), 'timestamp' => time(), ]); 
}
 /** * GetItemMemberlist . */ 
    public function getProjectMembers(RequestContext $requestContext, int $projectId): ProjectMembersResponseDTO 
{
 $userAuthorization = $requestContext->getuser Authorization(); $currentuser Id = $userAuthorization->getId(); $organizationCode = $userAuthorization->getOrganizationCode(); // 1. Validate if has manager or owner permission $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); // 2. GetItemMemberlist $memberEntities = $this->projectMemberDomainService->getProjectMembers($projectId, MemberRole::getAllRoleValues()); if (empty($memberEntities)) 
{
 return ProjectMembersResponseDTO::fromEmpty(); 
}
 // 3. GroupGetuser DepartmentID $userIds = $departmentIds = $targetMapEntities = []; foreach ($memberEntities as $entity) 
{
 if ($entity->getTargetType()->isuser ()) 
{
 $userIds[] = $entity->getTargetId(); 
}
 elseif ($entity->getTargetType()->isDepartment()) 
{
 $departmentIds[] = $entity->getTargetId(); 
}
 $targetMapEntities[$entity->getTargetId()] = $entity; 
}
 // 4. CreateDataObject $dataIsolation = $requestContext->getDataIsolation(); // Getuser Department $departmentuser s = $this->departmentuser DomainService->getDepartmentuser sByuser IdsInMagic($userIds); $userIdMapDepartmentIds = []; foreach ($departmentuser s as $departmentuser ) 
{
 if (! $departmentuser ->isTopLevel()) 
{
 $userIdMapDepartmentIds[$departmentuser ->getuser Id()] = $departmentuser ->getDepartmentId(); 
}
 
}
 $allDepartmentIds = array_merge($departmentIds, array_values($userIdMapDepartmentIds)); // GetDepartmentDetails $depIdMapDepartmentsinfo s = $this->departmentDomainService->getDepartmentFullPathByIds($dataIsolation, $allDepartmentIds); // 5. Getuser info $users = []; if (! empty($userIds)) 
{
 $userEntities = $this->magicuser DomainService->getuser ByIdsWithoutOrganization($userIds); $this->updateuser AvatarUrl($dataIsolation, $userEntities); foreach ($userEntities as $userEntity) 
{
 $pathNodes = []; if (isset($userIdMapDepartmentIds[$userEntity->getuser Id()])) 
{
 foreach ($depIdMapDepartmentsinfo s[$userIdMapDepartmentIds[$userEntity->getuser Id()]] ?? [] as $departmentinfo ) 
{
 $pathNodes[] = $this->assemblePathNodeByDepartmentinfo ($departmentinfo ); 
}
 
}
 $users[] = [ 'id' => (string) $userEntity->getId(), 'user_id' => $userEntity->getuser Id(), 'name' => $userEntity->getNickname(), 'i18n_name' => $userEntity->getI18nName() ?? '', 'organization_code' => $userEntity->getOrganizationCode(), 'avatar_url' => $userEntity->getAvatarUrl() ?? '', 'type' => 'user ', 'path_nodes' => $pathNodes, 'role' => $targetMapEntities[$userEntity->getuser Id()]->getRole()->value, 'join_method' => $targetMapEntities[$userEntity->getuser Id()]->getJoinMethod()->value, ]; 
}
 
}
 // 6. GetDepartmentinfo $departments = []; if (! empty($departmentIds)) 
{
 $departmentEntities = $this->departmentDomainService->getDepartmentByIds($dataIsolation, $departmentIds); foreach ($departmentEntities as $departmentEntity) 
{
 $pathNodes = []; foreach ($depIdMapDepartmentsinfo s[$departmentEntity->getDepartmentId()] ?? [] as $departmentinfo ) 
{
 $pathNodes[] = $this->assemblePathNodeByDepartmentinfo ($departmentinfo ); 
}
 $departments[] = [ 'id' => (string) $departmentEntity->getId(), 'department_id' => $departmentEntity->getDepartmentId(), 'name' => $departmentEntity->getName(), 'i18n_name' => $departmentEntity->getI18nName() ?? '', 'organization_code' => $requestContext->getOrganizationCode(), 'avatar_url' => '', 'type' => 'Department', 'path_nodes' => $pathNodes, 'role' => $targetMapEntities[$departmentEntity->getDepartmentId()]->getRole()->value, 'join_method' => $targetMapEntities[$departmentEntity->getDepartmentId()]->getJoinMethod()->value, ]; 
}
 
}
 // 7. UsingResponseDTOReturn Result return ProjectMembersResponseDTO::fromMemberData($users, $departments); 
}
 /** * Getcollaboration Itemlist * According totypeParameterGetDifferentTypecollaboration Item * - received: Sharegive collaboration Item * - shared: Sharegive collaboration Item. */ 
    public function getCollaborationProjects(RequestContext $requestContext, GetCollaborationProjectlist RequestDTO $requestDTO): array 
{
 $userAuthorization = $requestContext->getuser Authorization(); $dataIsolation = $this->createDataIsolation($userAuthorization); $userId = $dataIsolation->getcurrent user Id(); $currentOrganizationCode = $dataIsolation->getcurrent OrganizationCode(); $type = $requestDTO->getType() ?: 'received'; // 1. Get user collaboration projects in paid organization codes (non-paid plans do not support project collaboration) $collaborationPaidOrganizationCodes = $this->getuser CollaborationPaidOrganizationCodes($requestContext); // 2. current organization code also Joinlist for Filter $paidOrganizationCodes = array_unique(array_merge($collaborationPaidOrganizationCodes, [$currentOrganizationCode])); // 3. According toTypeGetProject IDlist $collaborationProjects = match ($type) 
{
 'shared' => $this->getSharedProjectIds($userId, $currentOrganizationCode, $requestDTO), default => $this->getReceivedProjectIds($userId, $dataIsolation, $requestDTO, $paidOrganizationCodes), 
}
; $projectIds = array_column($collaborationProjects['list'], 'project_id'); $totalCount = $collaborationProjects['total'] ?? 0; if (empty($projectIds)) 
{
 return CollaborationProjectlist ResponseDTO::fromProjectData([], [], [], [], [], $totalCount)->toArray(); 
}
 $result = $this->projectDomainService->getProjectsByConditions( ['project_ids' => $projectIds], $requestDTO->getPage(), $requestDTO->getPageSize() ); return $this->buildCollaborationProjectResponse($dataIsolation, $result['list'], $collaborationProjects['list'], $totalCount); 
}
 /** * UpdateItempinned Status. * * @param RequestContext $requestContext RequestContext * @param int $projectId Project ID * @param UpdateProjectPinRequestDTO $requestDTO RequestDTO */ 
    public function updateProjectPin( RequestContext $requestContext, int $projectId, UpdateProjectPinRequestDTO $requestDTO ): void 
{
 $userAuthorization = $requestContext->getuser Authorization(); // 1. Validate and get accessible projects $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); // 2. give Domainprocess $this->projectMemberDomainService->updateProjectPinStatus( $userAuthorization->getId(), $projectId, $userAuthorization->getOrganizationCode(), $requestDTO->isPinOperation() ); 
}
 /** * Update project shortcut. */ 
    public function updateProjectShortcut( RequestContext $requestContext, int $projectId, UpdateProjectShortcutRequestDTO $requestDTO ): void 
{
 $userAuthorization = $requestContext->getuser Authorization(); // 1. Validate and get accessible projects $projectEntity = $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); if ($projectEntity->getuser Id() === $userAuthorization->getId()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::CANNOT_SET_SHORTCUT_FOR_OWN_PROJECT); 
}
 // 2. Decide whether to set or cancel shortcut according to parameter if ($requestDTO->getIsBindWorkspace() === 1) 
{
 $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail((int) $requestDTO->getWorkspaceId()); if (! $workspaceEntity || $workspaceEntity->getuser Id() !== $userAuthorization->getId()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_NOT_FOUND); 
}
 // Set shortcut // 3. give Domainprocess Set shortcut $this->projectMemberDomainService->setProjectShortcut( $userAuthorization->getId(), $projectId, (int) $requestDTO->getWorkspaceId(), $userAuthorization->getOrganizationCode() ); // 4. PublishedItemshortcut Set Event $projectShortcutSetEvent = new ProjectShortcutSetEvent($projectEntity, (int) $requestDTO->getWorkspaceId(), $userAuthorization); $this->eventDispatcher->dispatch($projectShortcutSetEvent); 
}
 else 
{
 // cancel shortcut // 3. give Domainprocess cancel shortcut $this->projectMemberDomainService->cancelProjectShortcut( $userAuthorization->getId(), $projectId ); // 4. PublishedItemshortcut cancel Event $projectShortcutcancel ledEvent = new ProjectShortcutcancel ledEvent($projectEntity, $userAuthorization); $this->eventDispatcher->dispatch($projectShortcutcancel ledEvent); 
}
 
}
 /** * Getcollaboration Itemcreator list . */ 
    public function getCollaborationProjectcreator s(RequestContext $requestContext): Collaborationcreator list ResponseDTO 
{
 $userAuthorization = $requestContext->getuser Authorization(); $dataIsolation = $requestContext->getDataIsolation(); // 1. Getuser AtDepartmentIDlist $departmentIds = $this->departmentuser DomainService->getDepartmentIdsByuser Id($dataIsolation, $userAuthorization->getId(), true); // 2. Get list of creator user IDs for collaboration projects $creatoruser Ids = $this->projectMemberDomainService->getCollaborationProjectcreator Ids( $userAuthorization->getId(), $departmentIds, $userAuthorization->getOrganizationCode() ); $creatoruser Ids = array_filter($creatoruser Ids, function ($creatoruser Id) use ($dataIsolation) 
{
 return ((string) $creatoruser Id) !== $dataIsolation->getcurrent user Id();

}
); if (empty($creatoruser Ids)) 
{
 return Collaborationcreator list ResponseDTO::fromEmpty(); 
}
 // 3. BatchGetcreator user info $userEntities = $this->magicuser DomainService->getuser ByIdsWithoutOrganization($creatoruser Ids); // 4. Updateavatar URL $this->updateuser AvatarUrl($dataIsolation, $userEntities); // 5. CreateResponseDTOReturn return Collaborationcreator list ResponseDTO::fromuser Entities($userEntities); 
}
 /** * Get list of projects user participates inincluding collaboration Item. * * @param RequestContext $requestContext RequestContext * @param GetParticipatedProjectsRequestDTO $requestDTO RequestDTO */ 
    public function getParticipatedProjects( RequestContext $requestContext, GetParticipatedProjectsRequestDTO $requestDTO ): array 
{
 $userAuthorization = $requestContext->getuser Authorization(); $dataIsolation = $this->createDataIsolation($userAuthorization); // 1. Get user collaboration projects in paid organization codes (non-paid plans do not support project collaboration) $collaborationPaidOrganizationCodes = $this->getuser CollaborationPaidOrganizationCodes($requestContext); // 2. current organization code also Joinlist for Filter $paidOrganizationCodes = array_unique(array_merge($collaborationPaidOrganizationCodes, [$userAuthorization->getOrganizationCode()])); // 1. Get list of projects user participates in $result = $this->projectMemberDomainService->getParticipatedProjectsWithCollaboration( $dataIsolation->getcurrent user Id(), $requestDTO->getWorkspaceId(), $requestDTO->getShowCollaboration(), $requestDTO->getProjectName(), $requestDTO->getPage(), $requestDTO->getPageSize(), $paidOrganizationCodes ); // 2. workspace IDGetName $workspaceIds = array_unique(array_map(fn ($project) => $project['workspace_id'], $result['list'] ?? [])); $workspaceNameMap = $this->workspaceDomainService->getWorkspaceNamesBatch($workspaceIds); // NewMethodAccording to$projectIdsDeterminewhether ExistDataIfExistReturn ExistprojectIds $projectIds = []; foreach ($result['list'] as $projectData) 
{
 $projectIds[] = $projectData['id']; 
}
 $projectMemberCounts = $this->projectMemberDomainService->getProjectMembersCounts($projectIds); $projectIdsWithMember = array_keys(array_filter($projectMemberCounts, fn ($count) => $count > 0)); // 3. UsingResponseDTOprocess $listResponseDTO = ParticipatedProjectlist ResponseDTO::fromResult($result, $workspaceNameMap, $projectIdsWithMember); return $listResponseDTO->toArray(); 
}
 /** * AddItemMemberonly SupportOrganizationInternalMember. */ 
    public function createMembers(RequestContext $requestContext, int $projectId, CreateMembersRequestDTO $requestDTO): array 
{
 $userAuthorization = $requestContext->getuser Authorization(); $currentuser Id = $userAuthorization->getId(); $organizationCode = $userAuthorization->getOrganizationCode(); // 1. Get project and validate if user is project manager or owner $project = $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); // 2. check if project collaboration is on if (! $project->getIsCollaborationEnabled()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED, 'project.collaboration_disabled'); 
}
 // 3. RequestData $members = $requestDTO->getMembers(); // 4. BuildMemberlist $memberEntities = []; // 4.1 BatchValidate Targetuser /DepartmentAtcurrent Organization $this->validateTargetsInOrganization($members, $organizationCode); foreach ($members as $memberData) 
{
 $memberEntity = new ProjectMemberEntity(); $memberEntity->setProjectId($projectId); $memberEntity->setTargetType(MemberType::from($memberData['target_type'])); $memberEntity->setTargetId($memberData['target_id']); $memberEntity->setRole(MemberRole::validatepermission Level($memberData['role'])); $memberEntity->setOrganizationCode($organizationCode); $memberEntity->setInvitedBy($currentuser Id); $memberEntity->setStatus(MemberStatus::ACTIVE); $memberEntities[] = $memberEntity; 
}
 // 5. AddMember $this->projectMemberDomainService->addInternalMembers($memberEntities, $organizationCode); // 6. Getcomplete Memberinfo HaveGetMemberlist  $addedMemberIds = array_map(fn ($entity) => $entity->getTargetId(), $memberEntities); return $this->projectMemberDomainService->getMembersByIds($projectId, $addedMemberIds); 
}
 /** * BatchUpdateMemberpermission . */ 
    public function updateProjectMemberRoles(RequestContext $requestContext, int $projectId, BatchUpdateMembersRequestDTO $requestDTO): array 
{
 $userAuthorization = $requestContext->getuser Authorization(); $currentuser Id = $userAuthorization->getId(); $organizationCode = $userAuthorization->getOrganizationCode(); // 1. Validate permission $project = $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); // 2. RequestData $members = $requestDTO->getMembers(); // 3. Validate Batch - target_idand validate $targetIds = array_column($members, 'target_id'); // check whether try ModifyItemcreator permission Ifcreator yes Member if (in_array($project->getCreatedUid(), $targetIds, true)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED, 'project.cannot_modify_creator_permission'); 
}
 // 4. Validate Targetuser /DepartmentAtcurrent Organization $organizationCode = $requestContext->getuser Authorization()->getOrganizationCode(); $this->validateTargetsInOrganization($members, $organizationCode); // 5. ConvertDataFormatDomainServiceUsing $permissionUpdates = []; foreach ($members as $member) 
{
 $permissionUpdates[] = [ 'target_type' => $member['target_type'], 'target_id' => $member['target_id'], 'role' => $member['role'], ]; 
}
 // 6. execute Batchpermission Update $this->projectMemberDomainService->batchUpdateRole($projectId, $permissionUpdates); return []; 
}
 /** * Batchdelete Member. */ 
    public function deleteMembers(RequestContext $requestContext, int $projectId, array $members): void 
{
 $userAuthorization = $requestContext->getuser Authorization(); $currentuser Id = $userAuthorization->getId(); $organizationCode = $userAuthorization->getOrganizationCode(); // GetItem $project = $this->projectDomainService->getProjectNotuser Id($projectId); $targetIds = array_column($members, 'target_id'); // check whether delete if (in_array($currentuser Id, $targetIds)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED); 
}
 // Cannotwhether delete creator if (in_array($project->getuser Id(), $targetIds)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED); 
}
 // 1. Validate permission $this->getAccessibleProjectWithManager($projectId, $currentuser Id, $organizationCode); // 2. execute Batchdelete $this->projectMemberDomainService->deleteMembersByIds($projectId, $targetIds); 
}
 /** * Getuser collaboration Itemin paid organization code . * * @param RequestContext $requestContext RequestContext * @return array plan organization code Array */ 
    public function getuser CollaborationPaidOrganizationCodes(RequestContext $requestContext): array 
{
 $userAuthorization = $requestContext->getuser Authorization(); $userId = $userAuthorization->getId(); $dataIsolation = $this->createDataIsolation($userAuthorization); // 1. Get list of department IDs user belongs to (including parent departments) $departmentIds = $this->departmentuser DomainService->getDepartmentIdsByuser Id($dataIsolation, $userId, true); // 2. Mergeuser IDDepartmentIDas AuthorTargetID $targetIds = array_merge([$userId], $departmentIds); // 3. ThroughAuthorTargetIDGetAllcollaboration Itemorganization code ExcludeOWNERRole $projectIds = $this->projectMemberDomainService->getProjectIdsByCollaboratorTargets($targetIds); $organizationCodes = $this->projectDomainService->getOrganizationCodesByProjectIds($projectIds); if (empty($organizationCodes)) 
{
 return []; 
}
 // 4. ThroughPackageFilterInterfaceFilterplan organization code return $this->packageFilterService->filterPaidOrganizations($organizationCodes); 
}
 /** * GetSharegive Project IDlist . * * @param string $userId user ID * @param DataIsolation $dataIsolation DataObject * @param GetCollaborationProjectlist RequestDTO $requestDTO RequestDTO * @param array $organizationCodes organization code list for Filter */ 
    private function getReceivedProjectIds(string $userId, DataIsolation $dataIsolation, GetCollaborationProjectlist RequestDTO $requestDTO, array $organizationCodes = []): array 
{
 // Getuser AtAllDepartmentincluding parent Department $departmentIds = $this->departmentuser DomainService->getDepartmentIdsByuser Id( $dataIsolation, $userId, true // including parent Department ); // Getcollaboration Project IDlist Totalorganization code Filter return $this->projectMemberDomainService->getProjectIdsByuser AndDepartmentsWithTotal( $userId, $departmentIds, $requestDTO->getName(), $requestDTO->getSortField(), $requestDTO->getSortDirection(), $requestDTO->getcreator user Ids(), $requestDTO->getJoinMethod(), $organizationCodes ); 
}
 /** * GetSharegive Project IDlist . */ 
    private function getSharedProjectIds(string $userId, string $organizationCode, GetCollaborationProjectlist RequestDTO $requestDTO): array 
{
 // directly call optimize RepositoryMethodAtDatabasecomplete PagingFilter return $this->projectMemberDomainService->getSharedProjectIdsByuser WithTotal( $userId, $organizationCode, $requestDTO->getName(), $requestDTO->getPage(), $requestDTO->getPageSize(), $requestDTO->getSortField(), $requestDTO->getSortDirection(), $requestDTO->getcreator user Ids() ); 
}
 /** * Buildcollaboration ItemResponseData. */ 
    private function buildCollaborationProjectResponse(DataIsolation $dataIsolation, array $projects, array $collaborationProjects, int $totalCount): array 
{
 $userId = $dataIsolation->getcurrent user Id(); // 1. GetCreateinfo $creatoruser Ids = array_unique(array_map(fn ($project) => $project->getuser Id(), $projects)); $creatorinfo Map = []; if (! empty($creatoruser Ids)) 
{
 $creatoruser s = $this->magicuser DomainService->getuser ByIdsWithoutOrganization($creatoruser Ids); foreach ($creatoruser s as $user) 
{
 $creatorinfo Map[$user->getuser Id()] = creator info DTO::fromuser Entity($user); 
}
 
}
 // 2. GetAuthorinfo Interface $projectIdsFromResult = array_map(fn ($project) => $project->getId(), $projects); // 2.1 Get user's highest permission role in these projects $departmentIds = $this->departmentuser DomainService->getDepartmentIdsByuser Id($dataIsolation, $userId, true); $targetIds = array_merge([$userId], $departmentIds); $userRolesMap = $this->projectMemberDomainService->getuser HighestRolesInProjects($projectIdsFromResult, $targetIds); // 2.1 GetItemMemberTotal $memberCounts = $this->projectMemberDomainService->getProjectMembersCounts($projectIdsFromResult); // 2.2 Get project's first 4 members preview $membersPreview = $this->projectMemberDomainService->getProjectMembersPreview($projectIdsFromResult, 4); $collaboratorsinfo Map = []; foreach ($projectIdsFromResult as $projectId) 
{
 $memberinfo = $membersPreview[$projectId] ?? []; $memberCount = $memberCounts[$projectId] ?? 0; // user Department $userIds = []; $departmentIds = []; foreach ($memberinfo as $member) 
{
 if ($member->getTargetType()->isuser ()) 
{
 $userIds[] = $member->getTargetId(); 
}
 elseif ($member->getTargetType()->isDepartment()) 
{
 $departmentIds[] = $member->getTargetId(); 
}
 
}
 // Getuser Departmentinfo $userEntities = ! empty($userIds) ? $this->magicuser DomainService->getuser ByIdsWithoutOrganization($userIds) : []; $departmentEntities = ! empty($departmentIds) ? $this->departmentDomainService->getDepartmentByIds($dataIsolation, $departmentIds) : []; // directly CreateCollaboratorMemberDTOArray $members = []; $this->updateuser AvatarUrl($dataIsolation, $userEntities); foreach ($userEntities as $userEntity) 
{
 $members[] = CollaboratorMemberDTO::fromuser Entity($userEntity); 
}
 foreach ($departmentEntities as $departmentEntity) 
{
 $members[] = CollaboratorMemberDTO::fromDepartmentEntity($departmentEntity); 
}
 $collaboratorsinfo Map[$projectId] = [ 'members' => $members, 'member_count' => $memberCount, ]; 
}
 // 3. workspace IDGetName $workspaceIds = array_unique(array_map(fn ($project) => $project->getWorkspaceId(), $projects)); $workspaceNameMap = $this->workspaceDomainService->getWorkspaceNamesBatch($workspaceIds); // 4. Createcollaboration Itemlist ResponseDTOincluding user Role $collaborationlist ResponseDTO = CollaborationProjectlist ResponseDTO::fromProjectData( $projects, $collaborationProjects, $creatorinfo Map, $collaboratorsinfo Map, $workspaceNameMap, $totalCount, $userRolesMap ); return $collaborationlist ResponseDTO->toArray(); 
}
 
    private function updateuser AvatarUrl(DataIsolation $dataIsolation, array $userEntities): void 
{
 $urlMapRealUrl = $this->getuser AvatarUrls($dataIsolation, $userEntities); foreach ($userEntities as $userEntity) 
{
 $userEntity->setAvatarUrl($urlMapRealUrl[$userEntity->getAvatarUrl()] ?? ''); 
}
 
}
 
    private function getuser AvatarUrls(DataIsolation $dataIsolation, array $userEntities): array 
{
 $avatarUrlMapRealUrl = []; $urlPaths = []; foreach ($userEntities as $userEntity) 
{
 if (str_starts_with($userEntity->getAvatarUrl(), 'http')) 
{
 $avatarUrlMapRealUrl[$userEntity->getAvatarUrl()] = $userEntity->getAvatarUrl(); 
}
 else 
{
 $urlPaths[] = $userEntity->getAvatarUrl(); 
}
 
}
 $urlPaths = $this->getIcons($dataIsolation->getcurrent OrganizationCode(), $urlPaths); foreach ($urlPaths as $path => $urlPath) 
{
 $avatarUrlMapRealUrl[$path] = $urlPath->getUrl(); 
}
 return array_merge($urlPaths, $avatarUrlMapRealUrl); 
}
 
    private function assemblePathNodeByDepartmentinfo (MagicDepartmentEntity $departmentinfo ): array 
{
 return [ // DepartmentName 'department_name' => $departmentinfo ->getName(), // Departmentid 'department_id' => $departmentinfo ->getDepartmentId(), 'parent_department_id' => $departmentinfo ->getParentDepartmentId(), // DepartmentPath 'path' => $departmentinfo ->getPath(), // Visibility 'visible' => ! ($departmentinfo ->getOption() === DepartmentOption::Hidden), 'option' => $departmentinfo ->getOption(), ]; 
}
 /** * BatchValidate Targetuser /DepartmentAtcurrent Organization. */ 
    private function validateTargetsInOrganization(array $members, string $organizationCode): void 
{
 // Groupuser IDDepartmentID $userIds = []; $departmentIds = []; foreach ($members as $member) 
{
 if (MemberType::fromString($member['target_type'])->isuser ()) 
{
 $userIds[] = $member['target_id']; 
}
 elseif (MemberType::fromString($member['target_type'])->isDepartment()) 
{
 $departmentIds[] = $member['target_id']; 
}
 else 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::INVALID_MEMBER_TYPE); 
}
 
}
 // BatchValidate user if (! empty($userIds)) 
{
 $validuser s = $this->magicuser DomainService->getuser ByIdsWithoutOrganization($userIds); $validuser Ids = array_map(fn ($user) => $user->getuser Id(), $validuser s); $invaliduser Ids = array_diff($userIds, $validuser Ids); if (! empty($invaliduser Ids)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED, 'project.member_not_found'); 
}
 
}
 // BatchValidate Department if (! empty($departmentIds)) 
{
 $dataIsolation = DataIsolation::create($organizationCode, ''); $validDepartments = $this->departmentDomainService->getDepartmentByIds($dataIsolation, $departmentIds); $validDepartmentIds = array_map(fn ($dept) => $dept->getDepartmentId(), $validDepartments); $invalidDepartmentIds = array_diff($departmentIds, $validDepartmentIds); if (! empty($invalidDepartmentIds)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::DEPARTMENT_NOT_FOUND, 'project.department_not_found'); 
}
 
}
 
}
 
}
 

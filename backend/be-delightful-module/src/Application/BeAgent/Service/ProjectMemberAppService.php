<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Domain\Contact\Entity\DelightfulDepartmentEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\DepartmentOption;
use App\Domain\Contact\Service\DelightfulDepartmentDomainService;
use App\Domain\Contact\Service\DelightfulDepartmentUserDomainService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\Provider\Service\ModelFilter\PackageFilterInterface;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectMemberEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberStatus;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberType;
use Delightful\BeDelightful\Domain\BeAgent\Event\ProjectMembersUpdatedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\ProjectShortcutCancelledEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\ProjectShortcutSetEvent;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectMemberDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\WorkspaceDomainService;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\BatchUpdateMembersRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\CreateMembersRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\GetCollaborationProjectListRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\GetParticipatedProjectsRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\UpdateProjectMembersRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\UpdateProjectPinRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\UpdateProjectShortcutRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\CollaborationCreatorListResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\CollaborationProjectListResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\CollaboratorMemberDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\CreatorInfoDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\ParticipatedProjectListResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\ProjectMembersResponseDTO;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Project Member Application Service
 *
 * Responsible for coordinating project member-related business processes, does not contain specific business logic
 */
class ProjectMemberAppService extends AbstractAppService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ProjectDomainService $projectDomainService,
        private readonly ProjectMemberDomainService $projectMemberDomainService,
        private readonly DelightfulDepartmentDomainService $departmentDomainService,
        private readonly DelightfulDepartmentUserDomainService $departmentUserDomainService,
        private readonly DelightfulUserDomainService $delightfulUserDomainService,
        private readonly WorkspaceDomainService $workspaceDomainService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PackageFilterInterface $packageFilterService,
    ) {
    }

    /**
     * Update project members.
     *
     * @param RequestContext $requestContext Request context
     * @param UpdateProjectMembersRequestDTO $requestDTO Request DTO
     */
    public function updateProjectMembers(
        RequestContext $requestContext,
        UpdateProjectMembersRequestDTO $requestDTO
    ): void {
        $userAuthorization = $requestContext->getUserAuthorization();
        $currentUserId = $userAuthorization->getId();
        $organizationCode = $userAuthorization->getOrganizationCode();

        // 1. Convert DTO to Entity
        $projectId = (int) $requestDTO->getProjectId();
        $memberEntities = [];

        foreach ($requestDTO->getMembers() as $memberData) {
            $entity = new ProjectMemberEntity();
            $entity->setTargetTypeFromString($memberData['target_type']);
            $entity->setTargetId($memberData['target_id']);
            $entity->setOrganizationCode($organizationCode);
            $entity->setInvitedBy($currentUserId);
            $entity->setRole(MemberRole::MANAGE);

            $memberEntities[] = $entity;
        }

        // 2. Verify and get accessible project
        $projectEntity = $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        // 3. Delegate to Domain layer to handle business logic
        $this->projectMemberDomainService->updateProjectMembers(
            $requestContext->getOrganizationCode(),
            $projectId,
            $memberEntities
        );

        // 4. Publish project members updated event
        $projectMembersUpdatedEvent = new ProjectMembersUpdatedEvent($projectEntity, $memberEntities, $userAuthorization);
        $this->eventDispatcher->dispatch($projectMembersUpdatedEvent);

        // 5. Record success log
        $this->logger->info('Project members updated successfully', [
            'project_id' => $projectId,
            'operator_id' => $requestContext->getUserId(),
            'member_count' => count($memberEntities),
            'timestamp' => time(),
        ]);
    }

    /**
     * Get project member list.
     */
    public function getProjectMembers(RequestContext $requestContext, int $projectId): ProjectMembersResponseDTO
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $currentUserId = $userAuthorization->getId();
        $organizationCode = $userAuthorization->getOrganizationCode();

        // 1. Verify if manager or owner permission
        $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        // 2. Get project member list
        $memberEntities = $this->projectMemberDomainService->getProjectMembers($projectId, MemberRole::getAllRoleValues());

        if (empty($memberEntities)) {
            return ProjectMembersResponseDTO::fromEmpty();
        }

        // 3. Group and get user and department IDs
        $userIds = $departmentIds = $targetMapEntities = [];

        foreach ($memberEntities as $entity) {
            if ($entity->getTargetType()->isUser()) {
                $userIds[] = $entity->getTargetId();
            } elseif ($entity->getTargetType()->isDepartment()) {
                $departmentIds[] = $entity->getTargetId();
            }
            $targetMapEntities[$entity->getTargetId()] = $entity;
        }

        // 4. Create data isolation object
        $dataIsolation = $requestContext->getDataIsolation();

        // Get user belonging department
        $departmentUsers = $this->departmentUserDomainService->getDepartmentUsersByUserIdsInDelightful($userIds);
        $userIdMapDepartmentIds = [];
        foreach ($departmentUsers as $departmentUser) {
            if (! $departmentUser->isTopLevel()) {
                $userIdMapDepartmentIds[$departmentUser->getUserId()] = $departmentUser->getDepartmentId();
            }
        }
        $allDepartmentIds = array_merge($departmentIds, array_values($userIdMapDepartmentIds));

        // Get department details
        $depIdMapDepartmentsInfos = $this->departmentDomainService->getDepartmentFullPathByIds($dataIsolation, $allDepartmentIds);

        // 5. Get user details
        $users = [];
        if (! empty($userIds)) {
            $userEntities = $this->delightfulUserDomainService->getUserByIdsWithoutOrganization($userIds);
            $this->updateUserAvatarUrl($dataIsolation, $userEntities);

            foreach ($userEntities as $userEntity) {
                $pathNodes = [];
                if (isset($userIdMapDepartmentIds[$userEntity->getUserId()])) {
                    foreach ($depIdMapDepartmentsInfos[$userIdMapDepartmentIds[$userEntity->getUserId()]] ?? [] as $departmentInfo) {
                        $pathNodes[] = $this->assemblePathNodeByDepartmentInfo($departmentInfo);
                    }
                }

                $users[] = [
                    'id' => (string) $userEntity->getId(),
                    'user_id' => $userEntity->getUserId(),
                    'name' => $userEntity->getNickname(),
                    'i18n_name' => $userEntity->getI18nName() ?? '',
                    'organization_code' => $userEntity->getOrganizationCode(),
                    'avatar_url' => $userEntity->getAvatarUrl() ?? '',
                    'type' => 'User',
                    'path_nodes' => $pathNodes,
                    'role' => $targetMapEntities[$userEntity->getUserId()]->getRole()->value,
                    'join_method' => $targetMapEntities[$userEntity->getUserId()]->getJoinMethod()->value,
                ];
            }
        }

        // 6. Get department details
        $departments = [];
        if (! empty($departmentIds)) {
            $departmentEntities = $this->departmentDomainService->getDepartmentByIds($dataIsolation, $departmentIds);
            foreach ($departmentEntities as $departmentEntity) {
                $pathNodes = [];
                foreach ($depIdMapDepartmentsInfos[$departmentEntity->getDepartmentId()] ?? [] as $departmentInfo) {
                    $pathNodes[] = $this->assemblePathNodeByDepartmentInfo($departmentInfo);
                }
                $departments[] = [
                    'id' => (string) $departmentEntity->getId(),
                    'department_id' => $departmentEntity->getDepartmentId(),
                    'name' => $departmentEntity->getName(),
                    'i18n_name' => $departmentEntity->getI18nName() ?? '',
                    'organization_code' => $requestContext->getOrganizationCode(),
                    'avatar_url' => '',
                    'type' => 'Department',
                    'path_nodes' => $pathNodes,
                    'role' => $targetMapEntities[$departmentEntity->getDepartmentId()]->getRole()->value,
                    'join_method' => $targetMapEntities[$departmentEntity->getDepartmentId()]->getJoinMethod()->value,
                ];
            }
        }

        // 7. Use ResponseDTO to return result
        return ProjectMembersResponseDTO::fromMemberData($users, $departments);
    }

    /**
     * Get collaboration project list
     * Get different types of collaboration projects based on type parameter:
     * - received: Collaboration projects shared with me by others
     * - shared: Collaboration projects I shared with others.
     */
    public function getCollaborationProjects(RequestContext $requestContext, GetCollaborationProjectListRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $userId = $dataIsolation->getCurrentUserId();
        $currentOrganizationCode = $dataIsolation->getCurrentOrganizationCode();
        $type = $requestDTO->getType() ?: 'received';

        // 1. Get paid organization codes in user collaboration projects (non-paid plans do not support project collaboration)
        $collaborationPaidOrganizationCodes = $this->getUserCollaborationPaidOrganizationCodes($requestContext);

        // 2. Add current organization code to list (for filtering)
        $paidOrganizationCodes = array_unique(array_merge($collaborationPaidOrganizationCodes, [$currentOrganizationCode]));

        // 3. Get project ID list based on type
        $collaborationProjects = match ($type) {
            'shared' => $this->getSharedProjectIds($userId, $currentOrganizationCode, $requestDTO),
            default => $this->getReceivedProjectIds($userId, $dataIsolation, $requestDTO, $paidOrganizationCodes),
        };

        $projectIds = array_column($collaborationProjects['list'], 'project_id');
        $totalCount = $collaborationProjects['total'] ?? 0;

        if (empty($projectIds)) {
            return CollaborationProjectListResponseDTO::fromProjectData([], [], [], [], [], $totalCount)->toArray();
        }

        $result = $this->projectDomainService->getProjectsByConditions(
            ['project_ids' => $projectIds],
            $requestDTO->getPage(),
            $requestDTO->getPageSize()
        );

        return $this->buildCollaborationProjectResponse($dataIsolation, $result['list'], $collaborationProjects['list'], $totalCount);
    }

    /**
     * Update project pin status.
     *
     * @param RequestContext $requestContext Request context
     * @param int $projectId Project ID
     * @param UpdateProjectPinRequestDTO $requestDTO Request DTO
     */
    public function updateProjectPin(
        RequestContext $requestContext,
        int $projectId,
        UpdateProjectPinRequestDTO $requestDTO
    ): void {
        $userAuthorization = $requestContext->getUserAuthorization();

        // 1. Verify and get accessible project
        $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

        // 2. Delegate to Domain layer to handle business logic
        $this->projectMemberDomainService->updateProjectPinStatus(
            $userAuthorization->getId(),
            $projectId,
            $userAuthorization->getOrganizationCode(),
            $requestDTO->isPinOperation()
        );
    }

    /**
     * Update project shortcut.
     */
    public function updateProjectShortcut(
        RequestContext $requestContext,
        int $projectId,
        UpdateProjectShortcutRequestDTO $requestDTO
    ): void {
        $userAuthorization = $requestContext->getUserAuthorization();

        // 1. Verify and get accessible project
        $projectEntity = $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());
        if ($projectEntity->getUserId() === $userAuthorization->getId()) {
            ExceptionBuilder::throw(BeAgentErrorCode::CANNOT_SET_SHORTCUT_FOR_OWN_PROJECT);
        }

        // 2. Decide to set or cancel shortcut based on parameters
        if ($requestDTO->getIsBindWorkspace() === 1) {
            $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail((int) $requestDTO->getWorkspaceId());
            if (! $workspaceEntity || $workspaceEntity->getUserId() !== $userAuthorization->getId()) {
                ExceptionBuilder::throw(BeAgentErrorCode::WORKSPACE_NOT_FOUND);
            }
            // Set shortcut
            // 3. Delegate to Domain layer to handle Set shortcut
            $this->projectMemberDomainService->setProjectShortcut(
                $userAuthorization->getId(),
                $projectId,
                (int) $requestDTO->getWorkspaceId(),
                $userAuthorization->getOrganizationCode()
            );

            // 4. Publish project shortcut set event
            $projectShortcutSetEvent = new ProjectShortcutSetEvent($projectEntity, (int) $requestDTO->getWorkspaceId(), $userAuthorization);
            $this->eventDispatcher->dispatch($projectShortcutSetEvent);
        } else {
            // Cancel shortcut
            // 3. Delegate to Domain layer to handle Cancel shortcut
            $this->projectMemberDomainService->cancelProjectShortcut(
                $userAuthorization->getId(),
                $projectId
            );

            // 4. Publish project shortcut cancelled event
            $projectShortcutCancelledEvent = new ProjectShortcutCancelledEvent($projectEntity, $userAuthorization);
            $this->eventDispatcher->dispatch($projectShortcutCancelledEvent);
        }
    }

    /**
     * Get collaboration project creator list.
     */
    public function getCollaborationProjectCreators(RequestContext $requestContext): CollaborationCreatorListResponseDTO
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $requestContext->getDataIsolation();

        // 1. Get list of department IDs where user is located
        $departmentIds = $this->departmentUserDomainService->getDepartmentIdsByUserId($dataIsolation, $userAuthorization->getId(), true);

        // 2. Get list of creator user IDs for collaboration projects
        $creatorUserIds = $this->projectMemberDomainService->getCollaborationProjectCreatorIds(
            $userAuthorization->getId(),
            $departmentIds,
            $userAuthorization->getOrganizationCode()
        );

        $creatorUserIds = array_filter($creatorUserIds, function ($creatorUserId) use ($dataIsolation) {
            return ((string) $creatorUserId) !== $dataIsolation->getCurrentUserId();
        });

        if (empty($creatorUserIds)) {
            return CollaborationCreatorListResponseDTO::fromEmpty();
        }

        // 3. Batch get creator user details
        $userEntities = $this->delightfulUserDomainService->getUserByIdsWithoutOrganization($creatorUserIds);

        // 4. Update avatar URL
        $this->updateUserAvatarUrl($dataIsolation, $userEntities);

        // 5. Create response DTO and return
        return CollaborationCreatorListResponseDTO::fromUserEntities($userEntities);
    }

    /**
     * Get list of projects user participated in (including collaboration projects).
     *
     * @param RequestContext $requestContext Request context
     * @param GetParticipatedProjectsRequestDTO $requestDTO Request DTO
     */
    public function getParticipatedProjects(
        RequestContext $requestContext,
        GetParticipatedProjectsRequestDTO $requestDTO
    ): array {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // 1. Get paid organization codes in user collaboration projects (non-paid plans do not support project collaboration)
        $collaborationPaidOrganizationCodes = $this->getUserCollaborationPaidOrganizationCodes($requestContext);

        // 2. Add current organization code to list (for filtering)
        $paidOrganizationCodes = array_unique(array_merge($collaborationPaidOrganizationCodes, [$userAuthorization->getOrganizationCode()]));

        // 1. Get user participated projects list
        $result = $this->projectMemberDomainService->getParticipatedProjectsWithCollaboration(
            $dataIsolation->getCurrentUserId(),
            $requestDTO->getWorkspaceId(),
            $requestDTO->getShowCollaboration(),
            $requestDTO->getProjectName(),
            $requestDTO->getPage(),
            $requestDTO->getPageSize(),
            $paidOrganizationCodes
        );

        // 2. Extract workspace ID and get name
        $workspaceIds = array_unique(array_map(fn ($project) => $project['workspace_id'], $result['list'] ?? []));
        $workspaceNameMap = $this->workspaceDomainService->getWorkspaceNamesBatch($workspaceIds);

        // New method, based on $projectIds, determine if data exists, if exists return existing projectIds
        $projectIds = [];
        foreach ($result['list'] as $projectData) {
            $projectIds[] = $projectData['id'];
        }
        $projectMemberCounts = $this->projectMemberDomainService->getProjectMembersCounts($projectIds);
        $projectIdsWithMember = array_keys(array_filter($projectMemberCounts, fn ($count) => $count > 0));

        // 3. Use unified response DTO handling method
        $listResponseDTO = ParticipatedProjectListResponseDTO::fromResult($result, $workspaceNameMap, $projectIdsWithMember);

        return $listResponseDTO->toArray();
    }

    /**
     * Add project members (only support organization internal members).
     */
    public function createMembers(RequestContext $requestContext, int $projectId, CreateMembersRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $currentUserId = $userAuthorization->getId();
        $organizationCode = $userAuthorization->getOrganizationCode();

        // 1. Get project and verify if user is project manager or owner
        $project = $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        // 2. Check if project collaboration is enabled
        if (! $project->getIsCollaborationEnabled()) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED, 'project.collaboration_disabled');
        }

        // 3. Extract request data
        $members = $requestDTO->getMembers();

        // 4. Build member entity list
        $memberEntities = [];

        // 4.1 Batch verify target users/departments in current organization
        $this->validateTargetsInOrganization($members, $organizationCode);

        foreach ($members as $memberData) {
            $memberEntity = new ProjectMemberEntity();
            $memberEntity->setProjectId($projectId);
            $memberEntity->setTargetType(MemberType::from($memberData['target_type']));
            $memberEntity->setTargetId($memberData['target_id']);
            $memberEntity->setRole(MemberRole::validatePermissionLevel($memberData['role']));
            $memberEntity->setOrganizationCode($organizationCode);
            $memberEntity->setInvitedBy($currentUserId);
            $memberEntity->setStatus(MemberStatus::ACTIVE);

            $memberEntities[] = $memberEntity;
        }

        // 5. Add members
        $this->projectMemberDomainService->addInternalMembers($memberEntities, $organizationCode);

        // 6. Get complete member information (reuse existing get member list logic)
        $addedMemberIds = array_map(fn ($entity) => $entity->getTargetId(), $memberEntities);

        return $this->projectMemberDomainService->getMembersByIds($projectId, $addedMemberIds);
    }

    /**
     * Batch update member permissions.
     */
    public function updateProjectMemberRoles(RequestContext $requestContext, int $projectId, BatchUpdateMembersRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $currentUserId = $userAuthorization->getId();
        $organizationCode = $userAuthorization->getOrganizationCode();

        // 1. Verify permission
        $project = $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        // 2. Extract request data
        $members = $requestDTO->getMembers();

        // 3. Verify batch operation - extract target_id and verify
        $targetIds = array_column($members, 'target_id');

        // Check if trying to modify project creator permission (if creator is member)
        if (in_array($project->getCreatedUid(), $targetIds, true)) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED, 'project.cannot_modify_creator_permission');
        }

        // 4. Verify target users/departments in current organization
        $organizationCode = $requestContext->getUserAuthorization()->getOrganizationCode();
        $this->validateTargetsInOrganization($members, $organizationCode);

        // 5. Convert data format for DomainService use
        $permissionUpdates = [];
        foreach ($members as $member) {
            $permissionUpdates[] = [
                'target_type' => $member['target_type'],
                'target_id' => $member['target_id'],
                'role' => $member['role'],
            ];
        }

        // 6. Execute batch permission update
        $this->projectMemberDomainService->batchUpdateRole($projectId, $permissionUpdates);

        return [];
    }

    /**
     * Batch delete members.
     */
    public function deleteMembers(RequestContext $requestContext, int $projectId, array $members): void
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $currentUserId = $userAuthorization->getId();
        $organizationCode = $userAuthorization->getOrganizationCode();

        // Get project
        $project = $this->projectDomainService->getProjectNotUserId($projectId);

        $targetIds = array_column($members, 'target_id');

        // Check if deleting self
        if (in_array($currentUserId, $targetIds)) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED);
        }

        // Cannot delete creator
        if (in_array($project->getUserId(), $targetIds)) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED);
        }

        // 1. Verify permission
        $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        // 2. Execute batch delete
        $this->projectMemberDomainService->deleteMembersByIds($projectId, $targetIds);
    }

    /**
     * Get paid organization codes in user collaboration projects.
     *
     * @param RequestContext $requestContext Request context
     * @return array Array of paid organization codes
     */
    public function getUserCollaborationPaidOrganizationCodes(RequestContext $requestContext): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $userId = $userAuthorization->getId();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // 1. Get list of department IDs where user belongs (including parent departments)
        $departmentIds = $this->departmentUserDomainService->getDepartmentIdsByUserId($dataIsolation, $userId, true);

        // 2. Merge user IDs and department IDs as collaborator target IDs
        $targetIds = array_merge([$userId], $departmentIds);

        // 3. Get organization codes of all participating collaboration projects through collaborator target IDs (excluding OWNER role)
        $projectIds = $this->projectMemberDomainService->getProjectIdsByCollaboratorTargets($targetIds);

        $organizationCodes = $this->projectDomainService->getOrganizationCodesByProjectIds($projectIds);

        if (empty($organizationCodes)) {
            return [];
        }

        // 4. Filter paid organization codes through PackageFilterInterface
        return $this->packageFilterService->filterPaidOrganizations($organizationCodes);
    }

    /**
     * Get Project ID list shared with me by others.
     *
     * @param string $userId User ID
     * @param DataIsolation $dataIsolation Data isolation object
     * @param GetCollaborationProjectListRequestDTO $requestDTO Request DTO
     * @param array $organizationCodes List of organization codes (for filtering)
     */
    private function getReceivedProjectIds(string $userId, DataIsolation $dataIsolation, GetCollaborationProjectListRequestDTO $requestDTO, array $organizationCodes = []): array
    {
        // Get all departments where user belongs (including parent departments)
        $departmentIds = $this->departmentUserDomainService->getDepartmentIdsByUserId(
            $dataIsolation,
            $userId,
            true // Include parent departments
        );

        // Get collaboration Project ID list and total count (filtered by organization code)
        return $this->projectMemberDomainService->getProjectIdsByUserAndDepartmentsWithTotal(
            $userId,
            $departmentIds,
            $requestDTO->getName(),
            $requestDTO->getSortField(),
            $requestDTO->getSortDirection(),
            $requestDTO->getCreatorUserIds(),
            $requestDTO->getJoinMethod(),
            $organizationCodes
        );
    }

    /**
     * Get Project ID list I shared with others.
     */
    private function getSharedProjectIds(string $userId, string $organizationCode, GetCollaborationProjectListRequestDTO $requestDTO): array
    {
        // Call optimized Repository method directly, pagination and filtering completed at database level
        return $this->projectMemberDomainService->getSharedProjectIdsByUserWithTotal(
            $userId,
            $organizationCode,
            $requestDTO->getName(),
            $requestDTO->getPage(),
            $requestDTO->getPageSize(),
            $requestDTO->getSortField(),
            $requestDTO->getSortDirection(),
            $requestDTO->getCreatorUserIds()
        );
    }

    /**
     * Build collaboration project response data.
     */
    private function buildCollaborationProjectResponse(DataIsolation $dataIsolation, array $projects, array $collaborationProjects, int $totalCount): array
    {
        $userId = $dataIsolation->getCurrentUserId();

        // 1. Get creator information
        $creatorUserIds = array_unique(array_map(fn ($project) => $project->getUserId(), $projects));
        $creatorInfoMap = [];
        if (! empty($creatorUserIds)) {
            $creatorUsers = $this->delightfulUserDomainService->getUserByIdsWithoutOrganization($creatorUserIds);
            foreach ($creatorUsers as $user) {
                $creatorInfoMap[$user->getUserId()] = CreatorInfoDTO::fromUserEntity($user);
            }
        }

        // 2. Get collaborator information separately (split interface)
        $projectIdsFromResult = array_map(fn ($project) => $project->getId(), $projects);

        // 2.1 Get highest permission role of user in these projects
        $departmentIds = $this->departmentUserDomainService->getDepartmentIdsByUserId($dataIsolation, $userId, true);
        $targetIds = array_merge([$userId], $departmentIds);
        $userRolesMap = $this->projectMemberDomainService->getUserHighestRolesInProjects($projectIdsFromResult, $targetIds);

        // 2.1 Get total count of project members
        $memberCounts = $this->projectMemberDomainService->getProjectMembersCounts($projectIdsFromResult);

        // 2.2 Get first 4 members preview of project
        $membersPreview = $this->projectMemberDomainService->getProjectMembersPreview($projectIdsFromResult, 4);

        $collaboratorsInfoMap = [];

        foreach ($projectIdsFromResult as $projectId) {
            $memberInfo = $membersPreview[$projectId] ?? [];
            $memberCount = $memberCounts[$projectId] ?? 0;

            // Separate users and departments
            $userIds = [];
            $departmentIds = [];
            foreach ($memberInfo as $member) {
                if ($member->getTargetType()->isUser()) {
                    $userIds[] = $member->getTargetId();
                } elseif ($member->getTargetType()->isDepartment()) {
                    $departmentIds[] = $member->getTargetId();
                }
            }

            // Get users and departments information
            $userEntities = ! empty($userIds) ? $this->delightfulUserDomainService->getUserByIdsWithoutOrganization($userIds) : [];
            $departmentEntities = ! empty($departmentIds) ? $this->departmentDomainService->getDepartmentByIds($dataIsolation, $departmentIds) : [];

            // Directly create CollaboratorMemberDTO array
            $members = [];

            $this->updateUserAvatarUrl($dataIsolation, $userEntities);
            foreach ($userEntities as $userEntity) {
                $members[] = CollaboratorMemberDTO::fromUserEntity($userEntity);
            }
            foreach ($departmentEntities as $departmentEntity) {
                $members[] = CollaboratorMemberDTO::fromDepartmentEntity($departmentEntity);
            }

            $collaboratorsInfoMap[$projectId] = [
                'members' => $members,
                'member_count' => $memberCount,
            ];
        }

        // 3. Extract workspace ID and get name
        $workspaceIds = array_unique(array_map(fn ($project) => $project->getWorkspaceId(), $projects));
        $workspaceNameMap = $this->workspaceDomainService->getWorkspaceNamesBatch($workspaceIds);

        // 4. Create collaboration project list response DTO (including user role)
        $collaborationListResponseDTO = CollaborationProjectListResponseDTO::fromProjectData(
            $projects,
            $collaborationProjects,
            $creatorInfoMap,
            $collaboratorsInfoMap,
            $workspaceNameMap,
            $totalCount,
            $userRolesMap
        );

        return $collaborationListResponseDTO->toArray();
    }

    private function updateUserAvatarUrl(DataIsolation $dataIsolation, array $userEntities): void
    {
        $urlMapRealUrl = $this->getUserAvatarUrls($dataIsolation, $userEntities);

        foreach ($userEntities as $userEntity) {
            $userEntity->setAvatarUrl($urlMapRealUrl[$userEntity->getAvatarUrl()] ?? '');
        }
    }

    private function getUserAvatarUrls(DataIsolation $dataIsolation, array $userEntities): array
    {
        $avatarUrlMapRealUrl = [];
        $urlPaths = [];
        foreach ($userEntities as $userEntity) {
            if (str_starts_with($userEntity->getAvatarUrl(), 'http')) {
                $avatarUrlMapRealUrl[$userEntity->getAvatarUrl()] = $userEntity->getAvatarUrl();
            } else {
                $urlPaths[] = $userEntity->getAvatarUrl();
            }
        }
        $urlPaths = $this->getIcons($dataIsolation->getCurrentOrganizationCode(), $urlPaths);
        foreach ($urlPaths as $path => $urlPath) {
            $avatarUrlMapRealUrl[$path] = $urlPath->getUrl();
        }
        return array_merge($urlPaths, $avatarUrlMapRealUrl);
    }

    private function assemblePathNodeByDepartmentInfo(DelightfulDepartmentEntity $departmentInfo): array
    {
        return [
            // Department name
            'department_name' => $departmentInfo->getName(),
            // Department id
            'department_id' => $departmentInfo->getDepartmentId(),
            'parent_department_id' => $departmentInfo->getParentDepartmentId(),
            // Department path
            'path' => $departmentInfo->getPath(),
            // Visibility
            'visible' => ! ($departmentInfo->getOption() === DepartmentOption::Hidden),
            'option' => $departmentInfo->getOption(),
        ];
    }

    /**
     * Batch verify target users/departments in current organization.
     */
    private function validateTargetsInOrganization(array $members, string $organizationCode): void
    {
        // Group collect user IDs and department IDs
        $userIds = [];
        $departmentIds = [];

        foreach ($members as $member) {
            if (MemberType::fromString($member['target_type'])->isUser()) {
                $userIds[] = $member['target_id'];
            } elseif (MemberType::fromString($member['target_type'])->isDepartment()) {
                $departmentIds[] = $member['target_id'];
            } else {
                ExceptionBuilder::throw(BeAgentErrorCode::INVALID_MEMBER_TYPE);
            }
        }

        // Batch verify users
        if (! empty($userIds)) {
            $validUsers = $this->delightfulUserDomainService->getUserByIdsWithoutOrganization($userIds);
            $validUserIds = array_map(fn ($user) => $user->getUserId(), $validUsers);

            $invalidUserIds = array_diff($userIds, $validUserIds);
            if (! empty($invalidUserIds)) {
                ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED, 'project.member_not_found');
            }
        }

        // Batch verify departments
        if (! empty($departmentIds)) {
            $dataIsolation = DataIsolation::create($organizationCode, '');
            $validDepartments = $this->departmentDomainService->getDepartmentByIds($dataIsolation, $departmentIds);
            $validDepartmentIds = array_map(fn ($dept) => $dept->getDepartmentId(), $validDepartments);

            $invalidDepartmentIds = array_diff($departmentIds, $validDepartmentIds);
            if (! empty($invalidDepartmentIds)) {
                ExceptionBuilder::throw(BeAgentErrorCode::DEPARTMENT_NOT_FOUND, 'project.department_not_found');
            }
        }
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use Carbon\Carbon;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Constant\ShareAccessType;
use Delightful\BeDelightful\Domain\Share\Entity\ResourceShareEntity;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectMemberDomainService;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\PasswordCrypt;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\InvitationDetailResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\InvitationLinkResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\JoinProjectResponseDTO;

/**
 * Project Invitation Link Application Service
 *
 * Responsible for coordinating project invitation link-related business logic
 */
class ProjectInvitationLinkAppService extends AbstractAppService
{
    public function __construct(
        private ResourceShareDomainService $resourceShareDomainService,
        private ProjectMemberDomainService $projectMemberDomainService,
        private DelightfulUserDomainService $delightfulUserDomainService,
        private FileDomainService $fileDomainService
    ) {
    }

    /**
     * Get project invitation link information.
     */
    public function getInvitationLink(RequestContext $requestContext, int $projectId): ?InvitationLinkResponseDTO
    {
        $organizationCode = $requestContext->getUserAuthorization()->getOrganizationCode();
        $currentUserId = $requestContext->getUserAuthorization()->getId();

        // 1. Verify project access rights
        $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        $shareEntity = $this->resourceShareDomainService->getShareByResource(
            (string) $projectId,
            ResourceType::ProjectInvitation->value
        );

        if (! $shareEntity) {
            return null;
        }

        return InvitationLinkResponseDTO::fromEntity($shareEntity, $this->resourceShareDomainService);
    }

    /**
     * Enable/disable invitation link.
     */
    public function toggleInvitationLink(RequestContext $requestContext, int $projectId, bool $enabled): InvitationLinkResponseDTO
    {
        $currentUserId = $requestContext->getUserAuthorization()->getId();
        $organizationCode = $requestContext->getUserAuthorization()->getOrganizationCode();

        // 1. Verify if you have project management access rights
        $project = $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        if ($project->getUserId() !== $currentUserId) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED, 'project.invalid_permission_level');
        }

        // 2. Find existing invitation share
        $existingShare = $this->resourceShareDomainService->getShareByResource(
            (string) $projectId,
            ResourceType::ProjectInvitation->value
        );

        if ($existingShare) {
            // Update the enabled/disabled status of the existing share
            $savedShare = $this->resourceShareDomainService->toggleShareStatus(
                $existingShare->getId(),
                $enabled,
                $currentUserId
            );
            return InvitationLinkResponseDTO::fromEntity($savedShare, $this->resourceShareDomainService);
        }

        if (! $enabled) {
            // If it does not exist and close is required, throw an exception
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_NOT_FOUND);
        }

        // 3. Create new invitation share (via ResourceShareDomainService)
        $shareEntity = $this->resourceShareDomainService->saveShare(
            (string) $projectId,
            ResourceType::ProjectInvitation->value,
            $currentUserId,
            $organizationCode,
            [
                'resource_name' => $project->getProjectName(),
                'share_type' => ShareAccessType::Internet->value,
                'extra' => [
                    'default_join_permission' => MemberRole::VIEWER->value,
                ],
            ],
        );

        return InvitationLinkResponseDTO::fromEntity($shareEntity, $this->resourceShareDomainService);
    }

    /**
     * Reset invitation link.
     */
    public function resetInvitationLink(RequestContext $requestContext, int $projectId): InvitationLinkResponseDTO
    {
        $currentUserId = $requestContext->getUserAuthorization()->getId();
        $organizationCode = $requestContext->getUserAuthorization()->getOrganizationCode();

        // 1. Verify project access rights
        $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        // 2. Get existing invitation share
        $shareEntity = $this->resourceShareDomainService->getShareByResource(
            (string) $projectId,
            ResourceType::ProjectInvitation->value
        );

        if (! $shareEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_NOT_FOUND);
        }

        // 3. Save updates
        $savedShare = $this->resourceShareDomainService->regenerateShareCodeById($shareEntity->getId());

        return InvitationLinkResponseDTO::fromEntity($savedShare, $this->resourceShareDomainService);
    }

    /**
     * Set password protection.
     */
    public function setPassword(RequestContext $requestContext, int $projectId, bool $enabled): string
    {
        $currentUserId = $requestContext->getUserAuthorization()->getId();
        $organizationCode = $requestContext->getUserAuthorization()->getOrganizationCode();

        // 1. Verify project access rights
        $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        // 2. Get existing invitation share
        $shareEntity = $this->resourceShareDomainService->getShareByResource(
            (string) $projectId,
            ResourceType::ProjectInvitation->value
        );

        if (! $shareEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_NOT_FOUND);
        }

        // 3. Set password protection switch
        if ($enabled) {
            // Enable password protection
            $password = $shareEntity->getPassword();

            // If there is no history password, generate a new password
            if (empty($password)) {
                $plainPassword = ResourceShareEntity::generateRandomPassword();
            } else {
                $plainPassword = PasswordCrypt::decrypt($password);
            }
            $this->resourceShareDomainService->changePasswordById($shareEntity->getId(), $plainPassword);
            return $plainPassword;
        }

        // Close password protection (keep password)
        $shareEntity->setIsPasswordEnabled(false);
        $shareEntity->setUpdatedAt(date('Y-m-d H:i:s'));
        $shareEntity->setUpdatedUid($currentUserId);
        $this->resourceShareDomainService->saveShareByEntity($shareEntity);
        return '';
    }

    /**
     * Reset password.
     */
    public function resetPassword(RequestContext $requestContext, int $projectId): string
    {
        $currentUserId = $requestContext->getUserAuthorization()->getId();
        $organizationCode = $requestContext->getUserAuthorization()->getOrganizationCode();

        // 1. Verify project access rights
        $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        // 2. Get existing invitation share
        $shareEntity = $this->resourceShareDomainService->getShareByResource(
            (string) $projectId,
            ResourceType::ProjectInvitation->value
        );

        if (! $shareEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_NOT_FOUND);
        }

        // 3. Generate new password
        $newPassword = ResourceShareEntity::generateRandomPassword();
        $this->resourceShareDomainService->changePasswordById($shareEntity->getId(), $newPassword);

        return $newPassword;
    }

    /**
     * Change invitation link password
     */
    public function changePassword(RequestContext $requestContext, int $projectId, string $newPassword): string
    {
        $currentUserId = $requestContext->getUserAuthorization()->getId();
        $organizationCode = $requestContext->getUserAuthorization()->getOrganizationCode();

        // 1. Verify project access rights
        $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        // 2. Verify password length (maximum 18 characters)
        if (strlen($newPassword) > 18 || strlen($newPassword) < 3) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_PASSWORD_INCORRECT);
        }

        // 3. Get invitation link
        $shareEntity = $this->resourceShareDomainService->getShareByResource(
            (string) $projectId,
            ResourceType::ProjectInvitation->value
        );

        if (! $shareEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_NOT_FOUND);
        }

        // 4. Update password and enable password protection
        $this->resourceShareDomainService->changePasswordById($shareEntity->getId(), $newPassword);

        return $newPassword;
    }

    /**
     * Change permission level.
     */
    public function updateDefaultJoinPermission(RequestContext $requestContext, int $projectId, string $permission): string
    {
        $currentUserId = $requestContext->getUserAuthorization()->getId();
        $organizationCode = $requestContext->getUserAuthorization()->getOrganizationCode();

        // 1. Verify project access rights
        $this->getAccessibleProjectWithManager($projectId, $currentUserId, $organizationCode);

        // 2. Get existing invitation share
        $shareEntity = $this->resourceShareDomainService->getShareByResource(
            (string) $projectId,
            ResourceType::ProjectInvitation->value
        );

        if (! $shareEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_NOT_FOUND);
        }

        // 3. Verify and update permission level
        MemberRole::validatePermissionLevel($permission);

        // 4. Update default_join_permission in extra
        $extra = $shareEntity->getExtra() ?? [];
        $extra['default_join_permission'] = $permission;
        $shareEntity->setExtra($extra);
        $shareEntity->setUpdatedAt(date('Y-m-d H:i:s'));
        $shareEntity->setUpdatedUid($currentUserId);

        // 5. Save updates
        $this->resourceShareDomainService->saveShareByEntity($shareEntity);

        return $permission;
    }

    /**
     * Get invitation information through Token (external user preview).
     */
    public function getInvitationByToken(RequestContext $requestContext, string $token): InvitationDetailResponseDTO
    {
        $currentUserId = $requestContext->getUserAuthorization()->getId();

        // 1. Get share information
        $shareEntity = $this->resourceShareDomainService->getShareByCode($token);
        if (! $shareEntity || ! ResourceType::isProjectInvitation($shareEntity->getResourceType())) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_NOT_FOUND);
        }

        // 2. Check if enabled
        if (! $shareEntity->getIsEnabled()) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_DISABLED);
        }

        // 3. Check if valid (expired, deleted, etc)
        if (! $shareEntity->isValid()) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_INVALID);
        }

        $resourceId = $shareEntity->getResourceId();
        $projectId = (int) $resourceId;

        // 4. Verify if the link creator has project management access rights, there may be subsequent deletion of the user from the project
        $project = $this->getAccessibleProjectWithManager($projectId, $shareEntity->getCreatedUid(), $shareEntity->getOrganizationCode());

        // 5. Extract creator ID
        $creatorId = $project->getUserId();
        $isCreator = $creatorId === $currentUserId;

        // 6. Check membership relationship
        $hasJoined = $isCreator || $this->projectMemberDomainService->isProjectMemberByUser($projectId, $currentUserId);

        // 7. Get creator information
        $creatorEntity = $this->delightfulUserDomainService->getByUserId($creatorId);
        $creatorAvatarUrl = $creatorNickName = '';
        if ($creatorEntity) {
            $creatorNickName = $creatorEntity->getNickname();
            $creatorAvatarUrl = $this->fileDomainService->getLink('', $creatorEntity->getAvatarUrl()) ?? $creatorEntity->getAvatarUrl();
        }

        // 8. Get default_join_permission from extra
        $defaultJoinPermission = $shareEntity->getExtraAttribute('default_join_permission', 'viewer');

        return InvitationDetailResponseDTO::fromArray([
            'project_id' => $resourceId,
            'project_name' => $project->getProjectName(),
            'project_description' => $project->getProjectDescription() ?? '',
            'organization_code' => $project->getUserOrganizationCode() ?? '',
            'creator_id' => $creatorId,
            'creator_name' => $creatorNickName,
            'creator_avatar' => $creatorAvatarUrl,
            'default_join_permission' => $defaultJoinPermission,
            'requires_password' => $shareEntity->getIsPasswordEnabled(),
            'token' => $shareEntity->getShareCode(),
            'has_joined' => $hasJoined,
        ]);
    }

    /**
     * Join project (external user operation).
     */
    public function joinProject(RequestContext $requestContext, string $token, ?string $password = null): JoinProjectResponseDTO
    {
        $currentUserId = $requestContext->getUserAuthorization()->getId();

        // 1. Verify share link
        $shareEntity = $this->resourceShareDomainService->getShareByCode($token);
        if (! $shareEntity || ! ResourceType::isProjectInvitation($shareEntity->getResourceType())) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_NOT_FOUND);
        }

        // 2. Check if enabled
        if (! $shareEntity->getIsEnabled()) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_DISABLED);
        }

        // 3. Check if valid (expired, deleted, etc)
        if (! $shareEntity->isValid()) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_INVALID);
        }

        // 4. Verify password
        if ($shareEntity->getIsPasswordEnabled()) {
            // Link has password protection enabled
            if (empty($password)) {
                ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_PASSWORD_INCORRECT);
            }
            if (! $this->resourceShareDomainService->verifyPassword($shareEntity, $password)) {
                ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_PASSWORD_INCORRECT);
            }
        }

        // 5. Check if already a project member (via Domain service)
        $isExistingMember = $this->projectMemberDomainService->isProjectMemberByUser(
            (int) $shareEntity->getResourceId(),
            $currentUserId
        );
        if ($isExistingMember) {
            ExceptionBuilder::throw(BeAgentErrorCode::INVITATION_LINK_ALREADY_JOINED);
        }

        $projectId = (int) $shareEntity->getResourceId();

        // 6. Verify if the link creator has project management access rights, there may be subsequent deletion of the user from the project
        $this->getAccessibleProjectWithManager($projectId, $shareEntity->getCreatedUid(), $shareEntity->getOrganizationCode());

        // 7. Get default_join_permission from extra, and convert to member role
        $permission = $shareEntity->getExtraAttribute('default_join_permission', MemberRole::VIEWER->value);
        $memberRole = MemberRole::validatePermissionLevel($permission);

        // Add member using Domain service, consistent with DDD architecture
        $projectMemberEntity = $this->projectMemberDomainService->addMemberByInvitation(
            $shareEntity->getResourceId(),
            $currentUserId,
            $memberRole,
            $shareEntity->getOrganizationCode(),
            $shareEntity->getCreatedUid() // Inviter (invitation link creator)
        );

        return JoinProjectResponseDTO::fromArray([
            'project_id' => $shareEntity->getResourceId(),
            'user_role' => $memberRole->value,
            'join_method' => $projectMemberEntity->getJoinMethod()->value,
            'joined_at' => Carbon::now()->toDateTimeString(),
        ]);
    }
}

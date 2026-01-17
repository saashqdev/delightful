<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Application\Kernel\AbstractKernelAppService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulDepartmentUserDomainService;
use App\Domain\Provider\Service\ModelFilter\PackageFilterInterface;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\Traits\DataIsolationTrait;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectMemberDomainService;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;

class AbstractAppService extends AbstractKernelAppService
{
    use DataIsolationTrait;

    /**
     * Get user-accessible project entity (default requires role higher than or equal to viewer).
     *
     * @return ProjectEntity Project entity
     */
    public function getAccessibleProject(int $projectId, string $userId, string $organizationCode, MemberRole $requiredRole = MemberRole::VIEWER): ProjectEntity
    {
        $projectDomainService = di(ProjectDomainService::class);
        $packageFilterService = di(PackageFilterInterface::class);
        $projectEntity = $projectDomainService->getProjectNotUserId($projectId);

        /*if ($projectEntity->getUserOrganizationCode() !== $organizationCode) {
            $logger->error('Project access denied', [
                'projectId' => $projectId,
                'userId' => $userId,
                'organizationCode' => $organizationCode,
                'projectUserOrganizationCode' => $projectEntity->getUserOrganizationCode(),
            ]);
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED);
        }*/

        // If creator, return directly
        if ($projectEntity->getUserId() === $userId) {
            return $projectEntity;
        }

        // Check if project collaboration is enabled
        if (! $projectEntity->getIsCollaborationEnabled()) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED);
        }

        // Validate identity
        $delightfulUserAuthorization = new DelightfulUserAuthorization();
        $delightfulUserAuthorization->setOrganizationCode($organizationCode);
        $delightfulUserAuthorization->setId($userId);
        $this->validateRoleHigherOrEqual($delightfulUserAuthorization, $projectId, $requiredRole);

        // Check if it's a paid subscription
        if (! $packageFilterService->isPaidSubscription($projectEntity->getUserOrganizationCode())) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED);
        }
        return $projectEntity;
    }

    /**
     * Get user-accessible project entity (requires role higher than or equal to editor).
     */
    public function getAccessibleProjectWithEditor(int $projectId, string $userId, string $organizationCode): ProjectEntity
    {
        return $this->getAccessibleProject($projectId, $userId, $organizationCode, MemberRole::EDITOR);
    }

    /**
     * Get user-accessible project entity (requires role higher than or equal to manager).
     */
    public function getAccessibleProjectWithManager(int $projectId, string $userId, string $organizationCode): ProjectEntity
    {
        return $this->getAccessibleProject($projectId, $userId, $organizationCode, MemberRole::MANAGE);
    }

    /**
     * Validate manager or owner permission.
     */
    protected function validateManageOrOwnerPermission(DelightfulUserAuthorization $delightfulUserAuthorization, int $projectId): void
    {
        $projectDomainService = di(ProjectDomainService::class);
        $projectEntity = $projectDomainService->getProjectNotUserId($projectId);
        // Check if project collaboration is enabled
        if (! $projectEntity->getIsCollaborationEnabled()) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED);
        }

        $this->validateRoleHigherOrEqual($delightfulUserAuthorization, $projectId, MemberRole::MANAGE);
    }

    /**
     * Validate editor permission.
     */
    protected function validateEditorPermission(DelightfulUserAuthorization $delightfulUserAuthorization, int $projectId): void
    {
        $projectDomainService = di(ProjectDomainService::class);
        $projectEntity = $projectDomainService->getProjectNotUserId($projectId);
        // Check if project collaboration is enabled
        if (! $projectEntity->getIsCollaborationEnabled()) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED);
        }

        $this->validateRoleHigherOrEqual($delightfulUserAuthorization, $projectId, MemberRole::EDITOR);
    }

    /**
     * Validate viewer permission.
     */
    protected function validateViewerPermission(DelightfulUserAuthorization $delightfulUserAuthorization, int $projectId): void
    {
        $projectDomainService = di(ProjectDomainService::class);
        $projectEntity = $projectDomainService->getProjectNotUserId($projectId);
        // Check if project collaboration is enabled
        if (! $projectEntity->getIsCollaborationEnabled()) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED);
        }

        $this->validateRoleHigherOrEqual($delightfulUserAuthorization, $projectId, MemberRole::VIEWER);
    }

    /**
     * Validate if current user role is higher than or equal to specified role.
     */
    protected function validateRoleHigherOrEqual(DelightfulUserAuthorization $delightfulUserAuthorization, int $projectId, MemberRole $requiredRole): void
    {
        $projectMemberService = di(ProjectMemberDomainService::class);
        $delightfulDepartmentUserDomainService = di(DelightfulDepartmentUserDomainService::class);
        $userId = $delightfulUserAuthorization->getId();

        $projectMemberEntity = $projectMemberService->getMemberByProjectAndUser($projectId, $userId);

        if ($projectMemberEntity && $projectMemberEntity->getRole()->isHigherOrEqualThan($requiredRole)) {
            return;
        }

        $dataIsolation = DataIsolation::create($delightfulUserAuthorization->getOrganizationCode(), $userId);
        $departmentIds = $delightfulDepartmentUserDomainService->getDepartmentIdsByUserId($dataIsolation, $userId, true);
        $projectMemberEntities = $projectMemberService->getMembersByProjectAndDepartmentIds($projectId, $departmentIds);

        foreach ($projectMemberEntities as $projectMemberEntity) {
            if ($projectMemberEntity->getRole()->isHigherOrEqualThan($requiredRole)) {
                return;
            }
        }
        ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_ACCESS_DENIED);
    }
}

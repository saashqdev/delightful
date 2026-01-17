<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Infrastructure\Util\Context\RequestContext;
use Delightful\BeDelightful\Domain\BeAgent\Service\FileEditingDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;

/**
 * File editing status application service.
 */
class FileEditingAppService extends AbstractAppService
{
    public function __construct(
        private readonly FileEditingDomainService $fileEditingDomainService,
        private readonly TaskFileDomainService $taskFileDomainService,
    ) {
    }

    /**
     * Join editing.
     */
    public function joinEditing(RequestContext $requestContext, int $fileId): void
    {
        $userAuthorization = $requestContext->getUserAuthorization();

        // Permission check
        $fileEntity = $this->taskFileDomainService->getUserFileEntityNoUser($fileId);
        $projectEntity = $this->getAccessibleProjectWithEditor($fileEntity->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

        // Delegate to Domain layer to handle business logic
        $this->fileEditingDomainService->joinEditing($fileId, $userAuthorization->getId(), $projectEntity->getUserOrganizationCode());
    }

    /**
     * Leave editing.
     */
    public function leaveEditing(RequestContext $requestContext, int $fileId): void
    {
        $userAuthorization = $requestContext->getUserAuthorization();

        // Permission check
        $fileEntity = $this->taskFileDomainService->getUserFileEntityNoUser($fileId);
        $projectEntity = $this->getAccessibleProjectWithEditor($fileEntity->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

        // Delegate to Domain layer to handle business logic
        $this->fileEditingDomainService->leaveEditing($fileId, $userAuthorization->getId(), $projectEntity->getUserOrganizationCode());
    }

    /**
     * Get number of editing users.
     */
    public function getEditingUsers(RequestContext $requestContext, int $fileId): int
    {
        $userAuthorization = $requestContext->getUserAuthorization();

        // Permission check
        $fileEntity = $this->taskFileDomainService->getUserFileEntityNoUser($fileId);
        $projectEntity = $this->getAccessibleProject($fileEntity->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

        // Delegate to Domain layer to query editing users count
        return $this->fileEditingDomainService->getEditingUsersCount($fileId, $projectEntity->getUserOrganizationCode());
    }
}

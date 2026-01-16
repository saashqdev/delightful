<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Infrastructure\Util\Context\RequestContext;
use Delightful\BeDelightful\Domain\SuperAgent\Service\FileEditingDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileDomainService;
/** * FileEditStatusApplyService */

class FileEditingAppService extends AbstractAppService 
{
 
    public function __construct( 
    private readonly FileEditingDomainService $fileEditingDomainService, 
    private readonly TaskFileDomainService $taskFileDomainService, ) 
{
 
}
 /** * JoinEdit. */ 
    public function joinEditing(RequestContext $requestContext, int $fileId): void 
{
 $userAuthorization = $requestContext->getuser Authorization(); // permission check $fileEntity = $this->taskFileDomainService->getuser FileEntityNouser ($fileId); $projectEntity = $this->getAccessibleProjectWithEditor($fileEntity->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); // Domainprocess $this->fileEditingDomainService->joinEditing($fileId, $userAuthorization->getId(), $projectEntity->getuser OrganizationCode()); 
}
 /** * Edit. */ 
    public function leaveEditing(RequestContext $requestContext, int $fileId): void 
{
 $userAuthorization = $requestContext->getuser Authorization(); // permission check $fileEntity = $this->taskFileDomainService->getuser FileEntityNouser ($fileId); $projectEntity = $this->getAccessibleProjectWithEditor($fileEntity->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); // Domainprocess $this->fileEditingDomainService->leaveEditing($fileId, $userAuthorization->getId(), $projectEntity->getuser OrganizationCode()); 
}
 /** * GetEdituser Quantity. */ 
    public function getEditinguser s(RequestContext $requestContext, int $fileId): int 
{
 $userAuthorization = $requestContext->getuser Authorization(); // permission check $fileEntity = $this->taskFileDomainService->getuser FileEntityNouser ($fileId); $projectEntity = $this->getAccessibleProject($fileEntity->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); // Domainquery Edituser Quantity return $this->fileEditingDomainService->getEditinguser sCount($fileId, $projectEntity->getuser OrganizationCode()); 
}
 
}
 

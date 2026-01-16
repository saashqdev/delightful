<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Application\Kernel\AbstractKernelAppService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\MagicDepartmentuser DomainService;
use App\Domain\Provider\Service\ModelFilter\PackageFilterInterface;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\Traits\DataIsolationTrait;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectMemberDomainService;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;

class AbstractAppService extends AbstractKernelAppService 
{
 use DataIsolationTrait;
/** * Getuser accessible ItemDefaultGreater thanReadableRole. * * @return ProjectEntity Item */ 
    public function getAccessibleProject(int $projectId, string $userId, string $organizationCode, MemberRole $requiredRole = MemberRole::VIEWER): ProjectEntity 
{
 $projectDomainService = di(ProjectDomainService::class); $packageFilterService = di(PackageFilterInterface::class); $projectEntity = $projectDomainService->getProjectNotuser Id($projectId); /*if ($projectEntity->getuser OrganizationCode() !== $organizationCode) 
{
 $logger->error('Project access denied', [ 'projectId' => $projectId, 'userId' => $userId, 'organizationCode' => $organizationCode, 'projectuser OrganizationCode' => $projectEntity->getuser OrganizationCode(), ]); ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED); 
}
*/ // Ifyes creator directly Return if ($projectEntity->getuser Id() === $userId) 
{
 return $projectEntity; 
}
 // Determinewhether OnItem if (! $projectEntity->getIsCollaborationEnabled()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED); 
}
 // Validate $magicuser Authorization = new Magicuser Authorization(); $magicuser Authorization->setOrganizationCode($organizationCode); $magicuser Authorization->setId($userId); $this->validateRoleHigherOrEqual($magicuser Authorization, $projectId, $requiredRole); // Determinewhether plan if (! $packageFilterService->isPaidSubscription($projectEntity->getuser OrganizationCode())) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED); 
}
 return $projectEntity; 
}
 /** * Getuser accessible ItemGreater thanEditRole. */ 
    public function getAccessibleProjectWithEditor(int $projectId, string $userId, string $organizationCode): ProjectEntity 
{
 return $this->getAccessibleProject($projectId, $userId, $organizationCode, MemberRole::EDITOR); 
}
 /** * Getuser accessible ItemGreater thanRole. */ 
    public function getAccessibleProjectWithManager(int $projectId, string $userId, string $organizationCode): ProjectEntity 
{
 return $this->getAccessibleProject($projectId, $userId, $organizationCode, MemberRole::MANAGE); 
}
 /** * Validate manager or owner permission . */ 
    protected function validateManageOrowner permission (Magicuser Authorization $magicuser Authorization, int $projectId): void 
{
 $projectDomainService = di(ProjectDomainService::class); $projectEntity = $projectDomainService->getProjectNotuser Id($projectId); // Determinewhether OnItem if (! $projectEntity->getIsCollaborationEnabled()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED); 
}
 $this->validateRoleHigherOrEqual($magicuser Authorization, $projectId, MemberRole::MANAGE); 
}
 /** * Validate Editorpermission . */ 
    protected function validateEditorpermission (Magicuser Authorization $magicuser Authorization, int $projectId): void 
{
 $projectDomainService = di(ProjectDomainService::class); $projectEntity = $projectDomainService->getProjectNotuser Id($projectId); // Determinewhether OnItem if (! $projectEntity->getIsCollaborationEnabled()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED); 
}
 $this->validateRoleHigherOrEqual($magicuser Authorization, $projectId, MemberRole::EDITOR); 
}
 /** * Validate Readablepermission . */ 
    protected function validateViewerpermission (Magicuser Authorization $magicuser Authorization, int $projectId): void 
{
 $projectDomainService = di(ProjectDomainService::class); $projectEntity = $projectDomainService->getProjectNotuser Id($projectId); // Determinewhether OnItem if (! $projectEntity->getIsCollaborationEnabled()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED); 
}
 $this->validateRoleHigherOrEqual($magicuser Authorization, $projectId, MemberRole::VIEWER); 
}
 /** * Validate current user Rolewhether Greater thanor Equalspecified Role. */ 
    protected function validateRoleHigherOrEqual(Magicuser Authorization $magicuser Authorization, int $projectId, MemberRole $requiredRole): void 
{
 $projectMemberService = di(ProjectMemberDomainService::class); $magicDepartmentuser DomainService = di(MagicDepartmentuser DomainService::class); $userId = $magicuser Authorization->getId(); $projectMemberEntity = $projectMemberService->getMemberByProjectAnduser ($projectId, $userId); if ($projectMemberEntity && $projectMemberEntity->getRole()->isHigherOrEqualThan($requiredRole)) 
{
 return; 
}
 $dataIsolation = DataIsolation::create($magicuser Authorization->getOrganizationCode(), $userId); $departmentIds = $magicDepartmentuser DomainService->getDepartmentIdsByuser Id($dataIsolation, $userId, true); $projectMemberEntities = $projectMemberService->getMembersByProjectAndDepartmentIds($projectId, $departmentIds); foreach ($projectMemberEntities as $projectMemberEntity) 
{
 if ($projectMemberEntity->getRole()->isHigherOrEqualThan($requiredRole)) 
{
 return; 
}
 
}
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED); 
}
 
}
 

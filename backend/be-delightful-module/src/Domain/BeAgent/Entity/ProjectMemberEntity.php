<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity;

use App\Infrastructure\Core\AbstractEntity;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberJoinMethod;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberType;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
/** * ItemMember. * * table Itemin MemberSupportuser DepartmentType */

class ProjectMemberEntity extends AbstractEntity 
{
 /** @var int primary key ID */ 
    protected int $id = 0; 
    protected int $projectId = 0; 
    protected MemberType $targetType; 
    protected string $targetId = ''; 
    protected MemberRole $role; 
    protected string $organizationCode = ''; 
    protected MemberStatus $status; 
    protected MemberJoinMethod $joinMethod; 
    protected string $invitedBy = ''; protected ?string $createdAt = null; protected ?string $updatedAt = null; protected ?string $deletedAt = null; 
    public function __construct(array $data = []) 
{
 // Set DefaultStatusas Active $this->status = MemberStatus::ACTIVE; // Set DefaultMemberTypeas user $this->targetType = MemberType::USER; // Set DefaultRoleas Editor $this->role = MemberRole::EDITOR; // Set DefaultJoinas TeamInvite $this->joinMethod = MemberJoinMethod::INTERNAL; $this->initProperty($data); $this->validateEntity(); 
}
 
    public function getId(): int 
{
 return $this->id; 
}
 
    public function setId(int $id): void 
{
 $this->id = $id; 
}
 
    public function getProjectId(): int 
{
 return $this->projectId; 
}
 
    public function setProjectId(int $projectId): void 
{
 $this->projectId = $projectId; 
}
 
    public function getTargetType(): MemberType 
{
 return $this->targetType; 
}
 
    public function setTargetType(MemberType $targetType): void 
{
 $this->targetType = $targetType; 
}
 /** * FromStringSet MemberType. */ 
    public function setTargetTypeFromString(string $targetType): void 
{
 $this->targetType = MemberType::fromString($targetType); 
}
 
    public function getTargetId(): string 
{
 return $this->targetId; 
}
 
    public function setTargetId(string $targetId): void 
{
 $this->targetId = $targetId; 
}
 
    public function getRole(): MemberRole 
{
 return $this->role; 
}
 
    public function setRole(MemberRole $role): void 
{
 $this->role = $role; 
}
 /** * FromStringSet MemberRole. */ 
    public function setRoleFromString(string $role): void 
{
 if (empty($role)) 
{
 // IfRoleEmptySet as DefaultEditorRoleIs not null $this->role = MemberRole::EDITOR; 
}
 else 
{
 $this->role = MemberRole::fromString($role); 
}
 
}
 /** * GetRoleStringValue. */ 
    public function getRoleValue(): string 
{
 return $this->role->value; 
}
 
    public function getOrganizationCode(): string 
{
 return $this->organizationCode; 
}
 
    public function setOrganizationCode(string $organizationCode): void 
{
 $this->organizationCode = $organizationCode; 
}
 
    public function getStatus(): MemberStatus 
{
 return $this->status; 
}
 
    public function setStatus(MemberStatus $status): void 
{
 $this->status = $status; 
}
 
    public function getInvitedBy(): string 
{
 return $this->invitedBy; 
}
 
    public function setInvitedBy(string $invitedBy): void 
{
 $this->invitedBy = $invitedBy; 
}
 
    public function getCreatedAt(): ?string 
{
 return $this->createdAt; 
}
 
    public function setCreatedAt(?string $createdAt): void 
{
 $this->createdAt = $createdAt; 
}
 
    public function getUpdatedAt(): ?string 
{
 return $this->updatedAt; 
}
 
    public function setUpdatedAt(?string $updatedAt): void 
{
 $this->updatedAt = $updatedAt; 
}
 
    public function getdelete dAt(): ?string 
{
 return $this->deletedAt; 
}
 
    public function setdelete dAt(?string $deletedAt): void 
{
 $this->deletedAt = $deletedAt; 
}
 
    public function getJoinMethod(): MemberJoinMethod 
{
 return $this->joinMethod; 
}
 
    public function setJoinMethod(MemberJoinMethod $joinMethod): void 
{
 $this->joinMethod = $joinMethod; 
}
 
    public 
    static function modelToEntity(array $data): ProjectMemberEntity 
{
 $entity = new ProjectMemberEntity(); $entity->setId($data['id']); $entity->setProjectId($data['project_id']); $entity->setTargetTypeFromString($data['target_type']); $entity->setTargetId($data['target_id']); $entity->setRoleFromString($data['role']); $entity->setOrganizationCode($data['organization_code']); $entity->setStatus(MemberStatus::from((int) $data['status'])); // Convert toMemberStatusEnum $entity->setJoinMethod(MemberJoinMethod::from($data['join_method'] ?? MemberJoinMethod::INTERNAL->value)); $entity->setInvitedBy($data['invited_by']); $entity->setCreatedAt($data['created_at']); $entity->setUpdatedAt($data['updated_at']); return $entity; 
}
 /** * Convert toArrayFormat. */ 
    public function toArray(): array 
{
 return [ 'id' => $this->id, 'project_id' => $this->projectId, 'target_type' => $this->targetType->value, 'target_id' => $this->targetId, 'role' => $this->getRoleValue(), 'organization_code' => $this->organizationCode, 'status' => $this->status->value, 'invited_by' => $this->invitedBy, 'created_at' => $this->createdAt, 'updated_at' => $this->updatedAt, 'deleted_at' => $this->deletedAt, ]; 
}
 /** * Validate Datacomplete . */ 
    private function validateEntity(): void 
{
 if (empty($this->targetId)) 
{
 return; // AllowInitializeEmpty 
}
 // basic Validate EnsuretargetIdHaveValue if (empty(trim($this->targetId))) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::MEMBER_VALIDATION_FAILED); 
}
 
}
 
}
 

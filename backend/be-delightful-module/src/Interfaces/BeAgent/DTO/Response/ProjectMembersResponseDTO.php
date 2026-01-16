<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

class ProjectMembersResponseDTO extends AbstractDTO 
{
 /** * @var ProjectMemberItemDTO[] ItemMemberlist */ 
    protected array $members = []; /** * FromMemberDataCreateResponseDTO. * * @param array $users user DataArray * @param array $departments DepartmentDataArray */ 
    public 
    static function fromMemberData(array $users, array $departments): self 
{
 $dto = new self(); $members = []; // process user Data foreach ($users as $userData) 
{
 $members[] = ProjectMemberItemDTO::fromuser Data($userData); 
}
 // process DepartmentData foreach ($departments as $departmentData) 
{
 $members[] = ProjectMemberItemDTO::fromDepartmentData($departmentData); 
}
 $dto->setMembers($members); return $dto; 
}
 /** * FromEmptyResultCreateResponseDTO. */ 
    public 
    static function fromEmpty(): self 
{
 $dto = new self(); $dto->setMembers([]); return $dto; 
}
 /** * Convert toArray * According toAPIDocumentationReturn Formatas [[...members]]ArrayEmptyMemberReturn EmptyArray. */ 
    public function toArray(): array 
{
 $memberArrays = []; foreach ($this->members as $member) 
{
 $memberArrays[] = $member->toArray(); 
}
 // IfDon't haveMemberReturn EmptyArrayOtherwiseReturn ArrayFormat if (empty($memberArrays)) 
{
 return []; 
}
 return $memberArrays; 
}
 /** * GetMemberlist . * * @return ProjectMemberItemDTO[] */ 
    public function getMembers(): array 
{
 return $this->members; 
}
 /** * Set Memberlist . * * @param ProjectMemberItemDTO[] $members */ 
    public function setMembers(array $members): self 
{
 $this->members = $members; return $this; 
}
 /** * AddMember. */ 
    public function addMember(ProjectMemberItemDTO $member): self 
{
 $this->members[] = $member; return $this; 
}
 /** * GetMemberTotal. */ 
    public function getMemberCount(): int 
{
 return count($this->members); 
}
 /** * check whether Empty. */ 
    public function isEmpty(): bool 
{
 return empty($this->members); 
}
 
}
 

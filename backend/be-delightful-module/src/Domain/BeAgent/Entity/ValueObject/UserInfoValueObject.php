<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * user info ValueObject. */

class user info ValueObject 
{
 /** * Function. * * @param string $id user ID * @param string $nickname user * @param string $realName Real * @param string $workNumber * @param string $position * @param array $departments Departmentinfo Array */ 
    public function __construct( 
    private string $id = '', 
    private string $nickname = '', 
    private string $realName = '', 
    private string $workNumber = '', 
    private string $position = '', 
    private array $departments = [] ) 
{
 // Ensure departments are Departmentinfo ValueObject instances $this->departments = array_map(function ($dept) 
{
 return $dept instanceof Departmentinfo ValueObject ? $dept : Departmentinfo ValueObject::fromArray($dept); 
}
, $this->departments); 
}
 /** * FromArrayCreateuser info Object. * * @param array $data user info Array */ 
    public 
    static function fromArray(array $data): self 
{
 $departments = []; if (isset($data['departments']) && is_array($data['departments'])) 
{
 $departments = array_map(function ($dept) 
{
 return is_array($dept) ? Departmentinfo ValueObject::fromArray($dept) : $dept; 
}
, $data['departments']); 
}
 return new self( $data['id'] ?? '', $data['nickname'] ?? '', $data['real_name'] ?? '', $data['work_number'] ?? '', $data['position'] ?? '', $departments ); 
}
 /** * Convert toArray. * * @return array user info Array */ 
    public function toArray(): array 
{
 return [ 'id' => $this->id, 'nickname' => $this->nickname, 'real_name' => $this->realName, 'work_number' => $this->workNumber, 'position' => $this->position, 'departments' => array_map(fn ($dept) => $dept->toArray(), $this->departments), ]; 
}
 // Getters 
    public function getId(): string 
{
 return $this->id; 
}
 
    public function getNickname(): string 
{
 return $this->nickname; 
}
 
    public function getRealName(): string 
{
 return $this->realName; 
}
 
    public function getWorkNumber(): string 
{
 return $this->workNumber; 
}
 
    public function getPosition(): string 
{
 return $this->position; 
}
 /** * @return Departmentinfo ValueObject[] */ 
    public function getDepartments(): array 
{
 return $this->departments; 
}
 // Withers for immutability 
    public function withId(string $id): self 
{
 $clone = clone $this; $clone->id = $id; return $clone; 
}
 
    public function withNickname(string $nickname): self 
{
 $clone = clone $this; $clone->nickname = $nickname; return $clone; 
}
 
    public function withRealName(string $realName): self 
{
 $clone = clone $this; $clone->realName = $realName; return $clone; 
}
 
    public function withWorkNumber(string $workNumber): self 
{
 $clone = clone $this; $clone->workNumber = $workNumber; return $clone; 
}
 
    public function withPosition(string $position): self 
{
 $clone = clone $this; $clone->position = $position; return $clone; 
}
 
    public function withDepartments(array $departments): self 
{
 $clone = clone $this; $clone->departments = array_map(function ($dept) 
{
 return $dept instanceof Departmentinfo ValueObject ? $dept : Departmentinfo ValueObject::fromArray($dept); 
}
, $departments); return $clone; 
}
 /** * check user info whether Empty. */ 
    public function isEmpty(): bool 
{
 return empty($this->id) && empty($this->nickname) && empty($this->realName); 
}
 /** * check user info whether valid. */ 
    public function isValid(): bool 
{
 return ! empty($this->id); 
}
 /** * GetPrimaryDepartmentFirstDepartment. */ 
    public function getPrimaryDepartment(): ?Departmentinfo ValueObject 
{
 return $this->departments[0] ?? null; 
}
 /** * check user whether belongs to specified Department. */ 
    public function belongsToDepartment(string $departmentId): bool 
{
 foreach ($this->departments as $department) 
{
 if ($department->getId() === $departmentId) 
{
 return true; 
}
 
}
 return false; 
}
 
}
 

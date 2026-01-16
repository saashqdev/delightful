<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * Departmentinfo ValueObject. */

class Departmentinfo ValueObject 
{
 /** * Function. * * @param string $id DepartmentID * @param string $name DepartmentName * @param string $path DepartmentPath */ 
    public function __construct( 
    private string $id = '', 
    private string $name = '', 
    private string $path = '' ) 
{
 
}
 /** * FromArrayCreateDepartmentinfo Object. * * @param array $data Departmentinfo Array */ 
    public 
    static function fromArray(array $data): self 
{
 return new self( $data['id'] ?? '', $data['name'] ?? '', $data['path'] ?? '' ); 
}
 /** * Convert toArray. * * @return array Departmentinfo Array */ 
    public function toArray(): array 
{
 return [ 'id' => $this->id, 'name' => $this->name, 'path' => $this->path, ]; 
}
 // Getters 
    public function getId(): string 
{
 return $this->id; 
}
 
    public function getName(): string 
{
 return $this->name; 
}
 
    public function getPath(): string 
{
 return $this->path; 
}
 // Withers for immutability 
    public function withId(string $id): self 
{
 $clone = clone $this; $clone->id = $id; return $clone; 
}
 
    public function withName(string $name): self 
{
 $clone = clone $this; $clone->name = $name; return $clone; 
}
 
    public function withPath(string $path): self 
{
 $clone = clone $this; $clone->path = $path; return $clone; 
}
 /** * check Departmentinfo whether Empty. */ 
    public function isEmpty(): bool 
{
 return empty($this->id) && empty($this->name) && empty($this->path); 
}
 /** * check Departmentinfo whether valid. */ 
    public function isValid(): bool 
{
 return ! empty($this->id) && ! empty($this->name); 
}
 
}
 

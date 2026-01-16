<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Agent\DTO;

class Builtintool CategoryDTO 
{
 
    public string $name; 
    public string $icon; 
    public string $description; /** @var array<Builtintool DTO> */ 
    public array $tools; 
    public function __construct(array $data = []) 
{
 $this->name = $data['name'] ?? ''; $this->icon = $data['icon'] ?? ''; $this->description = $data['description'] ?? ''; $this->tools = $data['tools'] ?? []; 
}
 
    public function getName(): string 
{
 return $this->name; 
}
 
    public function setName(string $name): void 
{
 $this->name = $name; 
}
 
    public function getIcon(): string 
{
 return $this->icon; 
}
 
    public function setIcon(string $icon): void 
{
 $this->icon = $icon; 
}
 
    public function getDescription(): string 
{
 return $this->description; 
}
 
    public function setDescription(string $description): void 
{
 $this->description = $description; 
}
 /** * @return array<Builtintool DTO> */ 
    public function gettool s(): array 
{
 return $this->tools; 
}
 /** * @param array<Builtintool DTO> $tools */ 
    public function settool s(array $tools): void 
{
 $this->tools = $tools; 
}
 
    public function addtool (Builtintool DTO $tool): void 
{
 $this->tools[] = $tool; 
}
 
}
 

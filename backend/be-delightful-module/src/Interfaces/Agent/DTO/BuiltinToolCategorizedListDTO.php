<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Agent\DTO;

class Builtintool Categorizedlist DTO 
{
 /** * CategoryOrganizationtool list . * @var array<string, array<Builtintool DTO>> */ 
    public array $categories = []; /** * Alltool list . * @var array<Builtintool DTO> */ 
    public array $tools = []; /** * Quantity. */ 
    public int $total = 0; 
    public function __construct(array $data = []) 
{
 if (isset($data['categories'])) 
{
 $this->setCategories($data['categories']); 
}
 if (isset($data['tools'])) 
{
 $this->settool s($data['tools']); 
}
 if (isset($data['total'])) 
{
 $this->setTotal($data['total']); 
}
 
}
 /** * @param array<string, array<Builtintool DTO>> $categories */ 
    public function setCategories(array $categories): self 
{
 $this->categories = $categories; return $this; 
}
 /** * @return array<string, array<Builtintool DTO>> */ 
    public function getCategories(): array 
{
 return $this->categories; 
}
 /** * @param array<Builtintool DTO> $tools */ 
    public function settool s(array $tools): self 
{
 $this->tools = $tools; return $this; 
}
 /** * @return array<Builtintool DTO> */ 
    public function gettool s(): array 
{
 return $this->tools; 
}
 
    public function setTotal(int $total): self 
{
 $this->total = $total; return $this; 
}
 
    public function getTotal(): int 
{
 return $this->total; 
}
 
}
 

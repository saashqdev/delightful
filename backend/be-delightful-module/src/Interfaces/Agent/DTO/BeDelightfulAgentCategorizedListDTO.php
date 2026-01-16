<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Agent\DTO;

class BeDelightfulAgentCategorizedlist DTO 
{
 /** * constantuseAgentlist. * @var array<BeDelightfulAgentlist DTO>*/ 
    public array $frequent = []; /** * All agentslist ( not containingconstantuse in ). * @var array<BeDelightfulAgentlist DTO>*/ 
    public array $all = [];
/** * Total amount .*/ 
    public int $total = 0; 
    public function __construct(array $data = []) 
{
 if (isset($data['frequent'])) 
{
 $this->setFrequent($data['frequent']); 
}
 if (isset($data['all'])) 
{
 $this->setAll($data['all']); 
}
 if (isset($data['total'])) 
{
 $this->setTotal($data['total']); 
}
 
}
 /** * @param array<BeDelightfulAgentlist DTO> $frequent */ 
    public function setFrequent(array $frequent): self 
{
 $this->frequent = $frequent; return $this; 
}
 /** * @return array<BeDelightfulAgentlist DTO> */ 
    public function getFrequent(): array 
{
 return $this->frequent; 
}
 /** * @param array<BeDelightfulAgentlist DTO> $all */ 
    public function setAll(array $all): self 
{
 $this->all = $all; return $this; 
}
 /** * @return array<BeDelightfulAgentlist DTO> */ 
    public function getAll(): array 
{
 return $this->all; 
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
 

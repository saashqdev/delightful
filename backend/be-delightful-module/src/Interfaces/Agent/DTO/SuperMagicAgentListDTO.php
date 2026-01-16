<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Agent\DTO;

use App\Infrastructure\Core\AbstractDTO;

class BeDelightfulAgentlist DTO extends AbstractDTO 
{
 /** * AgentCode. */ 
    public string $id = ''; /** * AgentName. */ 
    public string $name = ''; /** * AgentDescription. */ 
    public string $description = ''; /** * AgentIcon. * Format: 
{
 url : ... , type : ... , color : ... 
}
. */ 
    public array $icon = []; /** * IconType 1:Icon 2:Image. */ 
    public int $iconType = 1; /** * Type1-Built-in2-Custom. */ 
    public int $type = 2; 
    public function getId(): string 
{
 return $this->id; 
}
 
    public function setId(string $id): void 
{
 $this->id = $id; 
}
 
    public function getName(): string 
{
 return $this->name; 
}
 
    public function setName(?string $name): void 
{
 $this->name = $name ?? ''; 
}
 
    public function getDescription(): string 
{
 return $this->description; 
}
 
    public function setDescription(?string $description): void 
{
 $this->description = $description ?? ''; 
}
 
    public function getIcon(): array 
{
 return $this->icon; 
}
 
    public function setIcon(?array $icon): void 
{
 $this->icon = $icon ?? []; 
}
 
    public function getIconType(): int 
{
 return $this->iconType; 
}
 
    public function setIconType(?int $iconType): void 
{
 $this->iconType = $iconType ?? 1; 
}
 
    public function getType(): int 
{
 return $this->type; 
}
 
    public function setType(int $type): void 
{
 $this->type = $type; 
}
 
}
 

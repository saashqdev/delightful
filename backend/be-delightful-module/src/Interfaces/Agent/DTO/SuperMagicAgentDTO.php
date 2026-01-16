<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Agent\DTO;

use App\Infrastructure\Core\AbstractDTO;
use App\Interfaces\Kernel\DTO\Traits\OperatorDTOTrait;
use App\Interfaces\Kernel\DTO\Traits\StringIdDTOTrait;

class BeDelightfulAgentDTO extends AbstractDTO 
{
 use OperatorDTOTrait;
use StringIdDTOTrait;
/** * AgentName. */ 
    public string $name = ''; /** * AgentDescription. */ 
    public string $description = ''; /** * AgentIcon. * Format: 
{
 url : ... , type : ... , color : ... 
}
. */ 
    public array $icon = []; /** * IconType 1:Icon 2:Image. */ 
    public int $iconType = 1; /** * SystemNotice. */ 
    public array $prompt = []; /** * Type1-Built-in2-Custom. */ 
    public int $type = 2; /** * whether Enabled. */ public ?bool $enabled = null; /** * tool list . */ 
    public array $tools = []; /** * SystemNoticeTextFormat. */ public ?string $promptString = null; 
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
 
    public function getPrompt(): array 
{
 return $this->prompt; 
}
 
    public function setPrompt(?array $prompt): void 
{
 $this->prompt = $prompt ?? []; 
}
 
    public function getEnabled(): ?bool 
{
 return $this->enabled; 
}
 
    public function setEnabled(?bool $enabled): void 
{
 $this->enabled = $enabled; 
}
 
    public function getType(): int 
{
 return $this->type; 
}
 
    public function setType(int $type): void 
{
 $this->type = $type; 
}
 
    public function gettool s(): array 
{
 return $this->tools; 
}
 
    public function settool s(?array $tools): void 
{
 $this->tools = $tools ?? []; 
}
 
    public function getPromptString(): ?string 
{
 return $this->promptString; 
}
 
    public function setPromptString(?string $promptString): void 
{
 $this->promptString = $promptString; 
}
 
}
 

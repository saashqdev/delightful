<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\query ;

use App\Infrastructure\Core\Abstractquery ;

class BeDelightfulAgentquery extends Abstractquery 
{
 protected ?string $name = null; protected ?bool $enabled = null; protected ?array $codes = null; protected ?string $creatorId = null; 
    public function getName(): ?string 
{
 return $this->name; 
}
 
    public function setName(?string $name): void 
{
 $this->name = $name; 
}
 
    public function getEnabled(): ?bool 
{
 return $this->enabled; 
}
 
    public function setEnabled(?bool $enabled): void 
{
 $this->enabled = $enabled; 
}
 
    public function getCodes(): ?array 
{
 return $this->codes; 
}
 
    public function setCodes(?array $codes): void 
{
 $this->codes = $codes; 
}
 
    public function getcreator Id(): ?string 
{
 return $this->creatorId; 
}
 
    public function setcreator Id(?string $creatorId): void 
{
 $this->creatorId = $creatorId; 
}
 
}
 

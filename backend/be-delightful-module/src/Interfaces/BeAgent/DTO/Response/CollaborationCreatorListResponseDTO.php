<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;
/** * collaboration Itemcreator list Response DTO. */

class Collaborationcreator list ResponseDTO extends AbstractDTO 
{
 /** * @var Collaborationcreator ItemDTO[] creator list */ 
    protected array $creators = []; /** * Fromuser ArrayCreateResponseDTO. * * @param array $userEntities user Array */ 
    public 
    static function fromuser Entities(array $userEntities): self 
{
 $dto = new self(); $creatorItems = []; foreach ($userEntities as $userEntity) 
{
 $creatorItems[] = Collaborationcreator ItemDTO::fromuser Entity($userEntity); 
}
 $dto->setcreator s($creatorItems); return $dto; 
}
 /** * CreateEmptyResponseDTO. */ 
    public 
    static function fromEmpty(): self 
{
 return new self(); 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return array_map(fn (Collaborationcreator ItemDTO $creator) => $creator->toArray(), $this->creators); 
}
 /** * Getcreator list . * * @return Collaborationcreator ItemDTO[] */ 
    public function getcreator s(): array 
{
 return $this->creators; 
}
 /** * Set creator list . * * @param Collaborationcreator ItemDTO[] $creators */ 
    public function setcreator s(array $creators): self 
{
 $this->creators = $creators; return $this; 
}
 
}
 

<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Agent\Assembler;

use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Kernel\Assembler\OperatorAssembler;
use App\Interfaces\Kernel\DTO\PageDTO;
use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Interfaces\Agent\DTO\BeDelightfulAgentCategorizedlist DTO;
use Delightful\BeDelightful\Interfaces\Agent\DTO\BeDelightfulAgentDTO;
use Delightful\BeDelightful\Interfaces\Agent\DTO\BeDelightfulAgentlist DTO;

class BeDelightfulAgentAssembler 
{
 
    public 
    static function createDTO(BeDelightfulAgentEntity $BeDelightfulAgentEntity, array $users = [], bool $withPromptString = false): BeDelightfulAgentDTO 
{
 $DTO = new BeDelightfulAgentDTO(); $DTO->setId($BeDelightfulAgentEntity->getCode()); $DTO->setName($BeDelightfulAgentEntity->getName()); $DTO->setDescription($BeDelightfulAgentEntity->getDescription()); $DTO->setIcon($BeDelightfulAgentEntity->getIcon()); $DTO->setIconType($BeDelightfulAgentEntity->getIconType()); $DTO->setPrompt($BeDelightfulAgentEntity->getPrompt()); $DTO->setType($BeDelightfulAgentEntity->getType()->value); $DTO->setEnabled($BeDelightfulAgentEntity->isEnabled()); $DTO->settool s($BeDelightfulAgentEntity->gettool s()); // Set promptString if requested if ($withPromptString) 
{
 $DTO->setPromptString($BeDelightfulAgentEntity->getPromptString()); 
}
 $DTO->setcreator ($BeDelightfulAgentEntity->getcreator ()); $DTO->setCreatedAt($BeDelightfulAgentEntity->getCreatedAt()); $DTO->setModifier($BeDelightfulAgentEntity->getModifier()); $DTO->setUpdatedAt($BeDelightfulAgentEntity->getUpdatedAt()); $DTO->setcreator info (OperatorAssembler::createOperatorDTOByuser Entity($users[$BeDelightfulAgentEntity->getcreator ()] ?? null, $BeDelightfulAgentEntity->getCreatedAt())); $DTO->setModifierinfo (OperatorAssembler::createOperatorDTOByuser Entity($users[$BeDelightfulAgentEntity->getModifier()] ?? null, $BeDelightfulAgentEntity->getUpdatedAt())); return $DTO; 
}
 
    public 
    static function createDO(BeDelightfulAgentDTO $BeDelightfulAgentDTO): BeDelightfulAgentEntity 
{
 $BeDelightfulAgentEntity = new BeDelightfulAgentEntity(); $BeDelightfulAgentEntity->setCode((string) $BeDelightfulAgentDTO->getId()); $BeDelightfulAgentEntity->setName($BeDelightfulAgentDTO->getName()); $BeDelightfulAgentEntity->setDescription($BeDelightfulAgentDTO->getDescription()); $BeDelightfulAgentEntity->setIcon($BeDelightfulAgentDTO->getIcon()); $BeDelightfulAgentEntity->setIconType($BeDelightfulAgentDTO->getIconType()); $BeDelightfulAgentEntity->setPrompt($BeDelightfulAgentDTO->getPrompt()); $BeDelightfulAgentEntity->settool s($BeDelightfulAgentDTO->gettool s()); if ($BeDelightfulAgentDTO->getEnabled() !== null) 
{
 $BeDelightfulAgentEntity->setEnabled($BeDelightfulAgentDTO->getEnabled()); 
}
 return $BeDelightfulAgentEntity; 
}
 
    public 
    static function createlist DTO(BeDelightfulAgentEntity $BeDelightfulAgentEntity): BeDelightfulAgentlist DTO 
{
 $DTO = new BeDelightfulAgentlist DTO(); $DTO->setId($BeDelightfulAgentEntity->getCode()); $DTO->setName($BeDelightfulAgentEntity->getName()); $DTO->setDescription($BeDelightfulAgentEntity->getDescription()); $DTO->setIcon($BeDelightfulAgentEntity->getIcon()); $DTO->setIconType($BeDelightfulAgentEntity->getIconType()); $DTO->setType($BeDelightfulAgentEntity->getType()->value); return $DTO; 
}
 /** * @param array<BeDelightfulAgentEntity> $list */ 
    public 
    static function createPagelist DTO(array $list, int $total, Page $page): PageDTO 
{
 $dtolist = []; foreach ($list as $entity) 
{
 $dtolist [] = self::createlist DTO($entity); 
}
 return new PageDTO($page->getPage(), $total, $dtolist ); 
}
 /** * CreateCategorylist DTO. */ 
    public 
    static function createCategorizedlist DTO(array $frequent, array $all, int $total): BeDelightfulAgentCategorizedlist DTO 
{
 $frequentDTOs = []; foreach ($frequent as $entity) 
{
 $frequentDTOs[] = self::createlist DTO($entity); 
}
 $allDTOs = []; foreach ($all as $entity) 
{
 $allDTOs[] = self::createlist DTO($entity); 
}
 return new BeDelightfulAgentCategorizedlist DTO([ 'frequent' => $frequentDTOs, 'all' => $allDTOs, 'total' => $total, ]); 
}
 
}
 

<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\Agent\Assembler;

use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\Builtintool ;
use Delightful\BeDelightful\Interfaces\Agent\DTO\Builtintool CategoryDTO;
use Delightful\BeDelightful\Interfaces\Agent\DTO\Builtintool DTO;

class Builtintool Assembler 
{
 /** * Createtool Categorylist DTOZ-indexFormat. * @return array<Builtintool CategoryDTO> */ 
    public 
    static function createtool Categorylist DTO(): array 
{
 $categoryDTOs = []; // CategoryGrouptool directly CreateCategoryDTO foreach (Builtintool ::cases() as $toolEnum) 
{
 $toolCode = $toolEnum->value; $category = $toolEnum->gettool Category(); $categoryCode = $category->value; // IfCategoryDTOdoes not existCreate if (! isset($categoryDTOs[$categoryCode])) 
{
 $categoryDTOs[$categoryCode] = new Builtintool CategoryDTO([ 'name' => $category->getName(), 'icon' => $category->getIcon(), 'description' => $category->getDescription(), 'tools' => [], ]); 
}
 // Addtool PairCategory $categoryDTOs[$categoryCode]->addtool (new Builtintool DTO([ 'code' => $toolCode, 'name' => $toolEnum->gettool Name(), 'description' => $toolEnum->gettool Description(), 'icon' => $toolEnum->gettool Icon(), 'required' => $toolEnum->isRequired(), ])); 
}
 return array_values($categoryDTOs); 
}
 /** * Createtool list DTOFormat. * @return array<Builtintool DTO> */ 
    public 
    static function createtool list DTO(): array 
{
 $tools = []; foreach (Builtintool ::cases() as $toolEnum) 
{
 $tools[] = new Builtintool DTO([ 'code' => $toolEnum->value, 'name' => $toolEnum->gettool Name(), 'description' => $toolEnum->gettool Description(), 'icon' => $toolEnum->gettool Icon(), 'required' => $toolEnum->isRequired(), ]); 
}
 return $tools; 
}
 
}
 

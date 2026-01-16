<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

use function Hyperf\Translation\trans;

enum Builtintool Category: string 
{
 case FileOperations = 'file_operations'; case SearchExtraction = 'search_extraction'; case Contentprocess ing = 'content_processing'; case SystemExecution = 'system_execution'; case AIAssistance = 'ai_assistance'; /** * GetCategoryDisplayName. */ 
    public function getName(): string 
{
 return trans( builtin_tool_categories.names.
{
$this->value
}
 ); 
}
 /** * GetCategoryIcon. */ 
    public function getIcon(): string 
{
 // TemporarilyReturn EmptyStringWaitingFrontendIconContent return ''; 
}
 /** * GetCategoryDescription. */ 
    public function getDescription(): string 
{
 return trans( builtin_tool_categories.descriptions.
{
$this->value
}
 ); 
}
 
}
 

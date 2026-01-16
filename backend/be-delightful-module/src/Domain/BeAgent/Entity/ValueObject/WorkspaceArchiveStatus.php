<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * workspace StatusValueObject */

enum WorkspaceArchiveStatus: int 
{
 /** * not archived . */ case NotArchived = 0; /** * Archived. */ case Archived = 1; /** * GetStatusDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::NotArchived => 'not archived ', self::Archived => 'Archived', 
}
; 
}
 /** * GetAllStatuslist . * * @return array<int, string> StatusValueDescriptionMap */ 
    public 
    static function getlist (): array 
{
 return [ self::NotArchived->value => self::NotArchived->getDescription(), self::Archived->value => self::Archived->getDescription(), ]; 
}
 
}
 

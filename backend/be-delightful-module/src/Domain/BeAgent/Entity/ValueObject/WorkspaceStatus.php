<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * workspace StatusValueObject */

enum WorkspaceStatus: int 
{
 /** * NormalStatus */ case Normal = 0; /** * DisabledStatus */ case Disabled = 1; /** * delete Status */ case delete d = 2; /** * GetStatusDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::Normal => 'Normal', self::Disabled => 'Disabled', self::delete d => 'delete d', 
}
; 
}
 /** * GetAllStatuslist . * * @return array<int, string> StatusValueDescriptionMap */ 
    public 
    static function getlist (): array 
{
 return [ self::Normal->value => self::Normal->getDescription(), self::Disabled->value => self::Disabled->getDescription(), self::delete d->value => self::delete d->getDescription(), ]; 
}
 
}
 

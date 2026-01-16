<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * delete DataTypeEnum * for Identifierdelete DataTypequery related RunningTask */

enum delete DataType: string 
{
 /** * workspace . */ case WORKSPACE = 'workspace'; /** * Item. */ case PROJECT = 'project'; /** * topic . */ case TOPIC = 'topic'; /** * GetTypeDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::WORKSPACE => 'workspace ', self::PROJECT => 'Item', self::TOPIC => 'topic ', 
}
; 
}
 /** * GetAllTypelist . * * @return array<string, string> TypeValueDescriptionMap */ 
    public 
    static function getlist (): array 
{
 return [ self::WORKSPACE->value => self::WORKSPACE->getDescription(), self::PROJECT->value => self::PROJECT->getDescription(), self::TOPIC->value => self::TOPIC->getDescription(), ]; 
}
 
}
 

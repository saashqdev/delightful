<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject;

/** * TypeEnum. */

enum StorageType: string 
{
 /** * Empty. */ case WORKSPACE = 'workspace'; /** * Message. */ case TOPIC = 'topic'; /** * . */ case SNAPSHOT = 'snapshot'; case OBJECT_STORAGE = 'object_storage'; case OTHERS = ''; /** * GetTypeName. */ 
    public function getName(): string 
{
 return match ($this) 
{
 self::WORKSPACE => 'Empty', self::TOPIC => 'topic ', self::SNAPSHOT => '', self::OBJECT_STORAGE => 'Object', self::OTHERS => '', 
}
; 
}
 /** * GetTypeDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::WORKSPACE => 'AtEmptyin File', self::TOPIC => 'AtMessagein File', self::SNAPSHOT => 'Atin File', self::OBJECT_STORAGE => 'AtObjectin File', self::OTHERS => '', 
}
; 
}
 /** * FromStringCreateEnumInstance. */ 
    public 
    static function fromValue(string $value): self 
{
 return match ($value) 
{
 'workspace' => self::WORKSPACE, 'topic' => self::TOPIC, 'snapshot' => self::SNAPSHOT, // UnknownValueas WORKSPACEprocess Data default => self::OTHERS, 
}
; 
}
 
}
 

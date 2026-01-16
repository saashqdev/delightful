<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Constants;

/** * ResourceTypeConstant. */ 
    final class ResourceType 
{
 
    public 
    const PROJECT = 'project'; 
    public 
    const TOPIC = 'topic'; 
    public 
    const FILE = 'file'; 
    public 
    const DIRECTORY = 'directory'; 
    public 
    const MEMBER = 'member'; /** * GetAllResourceType. */ 
    public 
    static function getAllTypes(): array 
{
 return [ self::PROJECT, self::TOPIC, self::FILE, self::DIRECTORY, self::MEMBER, ]; 
}
 /** * Validate ResourceTypewhether valid. */ 
    public 
    static function isValidType(string $type): bool 
{
 return in_array($type, self::getAllTypes(), true); 
}
 
}
 

<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Share\Constant;

use RuntimeException;
/** * ResourceTypeEnum. */

enum ResourceType: int 
{
 // HaveType case BotCode = 1; // AI case SubFlowCode = 2; // case tool Set = 3; // tool case Knowledge = 4; // Library // NewType case Topic = 5; // topic case Document = 6; // Documentation case Schedule = 7; // case Multitable = 8; // table case Form = 9; // table case MindMap = 10; // Graph case Website = 11; // Website case Project = 12; // Item case File = 13; // File case ProjectInvitation = 14; // ItemInviteLink /** * GetResourceTypeName. */ 
    public function getBusinessName(): string 
{
 return match ($this) 
{
 self::BotCode => 'bot', self::SubFlowCode => 'subflow', self::tool Set => 'toolset', self::Knowledge => 'knowledge', self::Topic => 'topic', self::Document => 'document', self::Schedule => 'schedule', self::Multitable => 'multitable', self::Form => 'form', self::MindMap => 'mindmap', self::Website => 'website', self::Project => 'project', self::File => 'file', self::ProjectInvitation => 'project_invitation', 
}
; 
}
 /** * According toNameGetResourceTypeEnum. * * @param string $businessName Name * @return ResourceType ResourceTypeEnum * @throws RuntimeException Whencorresponding ResourceTypeThrowException */ 
    public 
    static function fromBusinessName(string $businessName): self 
{
 foreach (self::cases() as $type) 
{
 if ($type->getBusinessName() === $businessName) 
{
 return $type; 
}
 
}
 throw new RuntimeException( Nameas '
{
$businessName
}
' ResourceType ); 
}
 
    public 
    static function isProjectInvitation(int $type): bool 
{
 return self::ProjectInvitation->value === $type; 
}
 
}
 

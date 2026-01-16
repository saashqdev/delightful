<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject;

/** * memory Enum. */

enum MemoryOperationAction: string 
{
 case ACCEPT = 'accept'; // Acceptmemory Suggested case REJECT = 'reject'; // Declinememory Suggested /** * GetDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::ACCEPT => 'Accept', self::REJECT => 'Decline', 
}
; 
}
 /** * GetAllValue. */ 
    public 
    static function getAllValues(): array 
{
 return array_column(self::cases(), 'value'); 
}
 /** * check whether valid. */ 
    public 
    static function isValid(string $action): bool 
{
 return in_array($action, self::getAllValues(), true); 
}
 
}
 

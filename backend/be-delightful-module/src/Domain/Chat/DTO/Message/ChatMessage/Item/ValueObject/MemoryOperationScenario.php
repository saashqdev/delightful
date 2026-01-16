<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject;

/** * memory Enum. */

enum MemoryOperationScenario: string 
{
 case ADMIN_PANEL = 'admin_panel'; // case MEMORY_CARD_QUICK = 'memory_card_quick'; // memory /** * GetDescription. */ 
    public function getDescription(): string 
{
 return match ($this) 
{
 self::ADMIN_PANEL => '', self::MEMORY_CARD_QUICK => 'memory ', 
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
    static function isValid(string $scenario): bool 
{
 return in_array($scenario, self::getAllValues(), true); 
}
 /** * GetDefault. */ 
    public 
    static function getDefault(): self 
{
 return self::ADMIN_PANEL; 
}
 
}
 

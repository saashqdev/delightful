<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item;

use App\Infrastructure\Core\AbstractDTO;
use Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject\MemoryOperationAction;
use Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject\MemoryOperationScenario;
/** * Super MaggieCreate/Updatememory tool call CanAtMessageButtonRowQuick */

class MemoryOperation extends AbstractDTO 
{
 // memory 
    protected MemoryOperationAction $action; 
    protected string $memoryId; 
    protected MemoryOperationScenario $scenario; 
    public function __construct(?array $data) 
{
 parent::__construct($data); 
}
 
    public function getAction(): ?MemoryOperationAction 
{
 return $this->action ?? null; 
}
 
    public function getMemoryId(): ?string 
{
 return $this->memoryId ?? null; 
}
 
    public function setAction(MemoryOperationAction|string $action): void 
{
 if (is_string($action)) 
{
 $this->action = MemoryOperationAction::from($action); 
}
 else 
{
 $this->action = $action; 
}
 
}
 
    public function setMemoryId(string $memoryId): void 
{
 $this->memoryId = $memoryId; 
}
 
    public function getScenario(): ?MemoryOperationScenario 
{
 return $this->scenario ?? null; 
}
 
    public function setScenario(MemoryOperationScenario|string $scenario): void 
{
 if (is_string($scenario)) 
{
 $this->scenario = MemoryOperationScenario::from($scenario); 
}
 else 
{
 $this->scenario = $scenario; 
}
 
}
 
}
 

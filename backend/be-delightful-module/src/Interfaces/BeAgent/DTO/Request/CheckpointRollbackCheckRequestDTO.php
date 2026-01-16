<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

class check pointRollbackcheck RequestDTO extends AbstractRequestDTO 
{
 
    protected string $targetMessageId = ''; /** * Validate Rule. */ 
    public 
    static function getHyperfValidate Rules(): array 
{
 return [ 'target_message_id' => 'required|string', ]; 
}
 
    public 
    static function getHyperfValidate Message(): array 
{
 return [ 'target_message_id.required' => 'TargetMessageIDCannot be empty', ]; 
}
 /** * PropertyName. */ 
    public function attributes(): array 
{
 return [ 'target_message_id' => 'TargetMessageID', ]; 
}
 
    public function getTargetMessageId(): string 
{
 return $this->targetMessageId; 
}
 
    public function setTargetMessageId(string $targetMessageId): void 
{
 $this->targetMessageId = $targetMessageId; 
}
 
}
 

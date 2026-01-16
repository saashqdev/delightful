<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

/** * check pointRollbackStartRequestClass * Followsandbox DocumentationcheckpointRollbackStartRequestFormat. */

class check pointRollbackStartRequest 
{
 
    public function __construct( 
    private string $targetMessageId = '', ) 
{
 
}
 /** * CreatecheckpointRollbackStartRequestObject */ 
    public 
    static function create( string $targetMessageId, ): self 
{
 return new self($targetMessageId); 
}
 /** * GetTargetMessageID. */ 
    public function getTargetMessageId(): string 
{
 return $this->targetMessageId; 
}
 /** * Set TargetMessageID. */ 
    public function setTargetMessageId(string $targetMessageId): self 
{
 $this->targetMessageId = $targetMessageId; return $this; 
}
 /** * Convert toAPIRequestArray * According tosandbox DocumentationcheckpointRollbackStartRequestFormat. */ 
    public function toArray(): array 
{
 return [ 'target_message_id' => $this->targetMessageId, ]; 
}
 
}
 

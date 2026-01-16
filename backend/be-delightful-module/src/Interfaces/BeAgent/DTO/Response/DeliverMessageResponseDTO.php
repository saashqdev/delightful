<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

class DeliverMessageResponseDTO 
{
 /** * @param bool $success whether Success * @param string $messageId MessageID */ 
    public function __construct( 
    private bool $success, 
    private string $messageId ) 
{
 
}
 /** * FromResultCreateResponseDTO. */ 
    public 
    static function fromResult(bool $success, string $messageId = ''): self 
{
 return new self($success, $messageId); 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'success' => $this->success, 'message_id' => $this->messageId, ]; 
}
 /** * Determinewhether operation succeeded. */ 
    public function isSuccess(): bool 
{
 return $this->success; 
}
 /** * GetMessageID. */ 
    public function getMessageId(): string 
{
 return $this->messageId; 
}
 
}
 

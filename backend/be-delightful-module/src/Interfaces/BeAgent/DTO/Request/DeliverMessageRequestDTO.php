<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Hyperf\HttpServer\Contract\RequestInterface;

class DeliverMessageRequestDTO 
{
 /** * Function. * * @param array $metadata Data * @param array $payload Message */ 
    public function __construct( 
    private array $metadata, 
    private array $payload ) 
{
 
}
 /** * FromHTTPRequestCreateDTO. * * @param RequestInterface $request HTTPRequest */ 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 $requestData = $request->all(); if (empty($requestData)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'message_required'); 
}
 // Validate Requestincluding metadatapayloadField if (! isset($requestData['metadata']) || ! isset($requestData['payload'])) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'metadata_and_payload_required'); 
}
 return new self($requestData['metadata'], $requestData['payload']); 
}
 /** * GetData. */ 
    public function getMetadata(): array 
{
 return $this->metadata; 
}
 /** * Set Data. * * @param array $metadata Data */ 
    public function setMetadata(array $metadata): self 
{
 $this->metadata = $metadata; return $this; 
}
 /** * GetMessage. */ 
    public function getPayload(): array 
{
 return $this->payload; 
}
 /** * Set Message. * * @param array $payload Message */ 
    public function setPayload(array $payload): self 
{
 $this->payload = $payload; return $this; 
}
 
}
 

<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Hyperf\HttpServer\Contract\RequestInterface;
/** * check batch operation status request DTO. */

class check BatchOperationStatusRequestDTO 
{
 /** * Batch key for operation tracking. */ 
    private string $batchKey; /** * Constructor. */ 
    public function __construct(array $params) 
{
 $this->batchKey = $params['batch_key'] ?? ''; $this->validate(); 
}
 /** * Create DTO from HTTP request. */ 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 return new self($request->all()); 
}
 /** * Get batch key. */ 
    public function getBatchKey(): string 
{
 return $this->batchKey; 
}
 
    private function validate(): void 
{
 if (empty($this->batchKey)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'batch_key.required'); 
}
 
}
 
}
 

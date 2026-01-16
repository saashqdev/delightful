<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\SandboxStatus;
/** * Sandbox statusResultClass * process SingleSandbox statusquery Result. */

class SandboxStatusResult extends GatewayResult 
{
 private ?string $sandboxId = null; private ?string $status = null; /** * FromAPIResponseCreateSandbox statusResult. */ 
    public 
    static function fromApiResponse(array $response): self 
{
 $result = new self( $response['code'] ?? 2000, $response['message'] ?? 'Unknown error', $response['data'] ?? [] ); // Parse Sandbox statusData $data = $response['data'] ?? []; if (isset($data['sandbox_id'])) 
{
 $result->sandboxId = $data['sandbox_id']; 
}
 if (isset($data['status'])) 
{
 $result->status = $data['status']; 
}
 return $result; 
}
 /** * Get sandbox ID. */ 
    public function getSandboxId(): ?string 
{
 return $this->sandboxId ?? $this->getDataValue('sandbox_id'); 
}
 /** * Get sandbox status */ 
    public function getStatus(): ?string 
{
 return $this->status ?? $this->getDataValue('status'); 
}
 /** * Set Sandbox ID. */ 
    public function setSandboxId(?string $sandboxId): self 
{
 $this->sandboxId = $sandboxId; return $this; 
}
 /** * Set Sandbox status */ 
    public function setStatus(?string $status): self 
{
 $this->status = $status; return $this; 
}
 /** * check sandbox whether Running. */ 
    public function isRunning(): bool 
{
 $status = $this->getStatus(); return $status !== null && SandboxStatus::isAvailable($status); 
}
 /** * check sandbox whether . */ 
    public function isPending(): bool 
{
 return $this->getStatus() === SandboxStatus::PENDING; 
}
 /** * check sandbox whether Exit. */ 
    public function isExited(): bool 
{
 return $this->getStatus() === SandboxStatus::EXITED; 
}
 /** * check Statuswhether valid. */ 
    public function hasValidStatus(): bool 
{
 $status = $this->getStatus(); return $status !== null && SandboxStatus::isValidStatus($status); 
}
 
}
 

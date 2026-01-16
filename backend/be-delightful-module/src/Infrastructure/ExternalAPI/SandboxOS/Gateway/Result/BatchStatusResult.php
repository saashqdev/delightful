<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\SandboxStatus;
/** * BatchSandbox statusResultClass * process BatchSandbox statusquery Result. */

class BatchStatusResult extends GatewayResult 
{
 
    private array $sandboxStatuses = []; /** * FromAPIResponseCreateBatchStatusResult. */ 
    public 
    static function fromApiResponse(array $response): self 
{
 $result = new self( $response['code'] ?? 2000, $response['message'] ?? 'Unknown error', $response['data'] ?? [] ); // Parse BatchStatusData // According toDocumentationdata Fielddirectly yes Sandbox statusArray $data = $response['data'] ?? []; if (is_array($data)) 
{
 $result->sandboxStatuses = $data; 
}
 return $result; 
}
 /** * GetAllSandbox status */ 
    public function getSandboxStatuses(): array 
{
 return $this->sandboxStatuses ?: $this->getData(); 
}
 /** * Getspecified sandbox Status */ 
    public function getSandboxStatus(string $sandboxId): ?string 
{
 $statuses = $this->getSandboxStatuses(); foreach ($statuses as $sandbox) 
{
 if (isset($sandbox['sandbox_id']) && $sandbox['sandbox_id'] === $sandboxId) 
{
 return $sandbox['status'] ?? null; 
}
 
}
 return null; 
}
 /** * check specified sandbox whether Running. */ 
    public function isSandboxRunning(string $sandboxId): bool 
{
 $status = $this->getSandboxStatus($sandboxId); return $status !== null && SandboxStatus::isAvailable($status); 
}
 /** * GetRunningsandbox list . */ 
    public function getRunningSandboxes(): array 
{
 $running = []; $statuses = $this->getSandboxStatuses(); foreach ($statuses as $sandbox) 
{
 if (isset($sandbox['status']) && SandboxStatus::isAvailable($sandbox['status'])) 
{
 $running[] = $sandbox; 
}
 
}
 return $running; 
}
 /** * GetRunningSandbox IDlist . */ 
    public function getRunningSandboxIds(): array 
{
 $ids = []; $running = $this->getRunningSandboxes(); foreach ($running as $sandbox) 
{
 if (isset($sandbox['sandbox_id'])) 
{
 $ids[] = $sandbox['sandbox_id']; 
}
 
}
 return $ids; 
}
 /** * Getsandbox Total. */ 
    public function getTotalCount(): int 
{
 return count($this->getSandboxStatuses()); 
}
 /** * GetRunningsandbox Quantity. */ 
    public function getRunningCount(): int 
{
 return count($this->getRunningSandboxes()); 
}
 
}
 

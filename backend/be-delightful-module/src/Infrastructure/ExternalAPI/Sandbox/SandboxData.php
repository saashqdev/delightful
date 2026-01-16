<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox;

/** * Sandbox data structure. * * Corresponding to

interface response data format * 
{
 * sandbox_id : be9ae617 , * status : running/exited , * created_at : 1744293391.8599138, * ip_address : 192.168.148.10 * 
}
 */

class SandboxData 
{
 /** * @param string $sandboxId Sandbox ID * @param string $status Sandbox statusPossible values: running, exited, unknown * @param float $createdAt Creation timestamp * @param string $ipAddress IPAddress * @param array $extraData ExtraData */ 
    public function __construct( 
    private string $sandboxId = '', 
    private string $status = 'unknown', 
    private float $createdAt = 0, 
    private string $ipAddress = '', 
    private array $extraData = [] ) 
{
 if ($this->createdAt == 0) 
{
 $this->createdAt = (float) time(); 
}
 
}
 /** * Create struct instance from array. */ 
    public 
    static function fromArray(array $data): self 
{
 $sandboxId = $data['sandbox_id'] ?? ''; $status = $data['status'] ?? 'unknown'; $createdAt = (float) ($data['created_at'] ?? time()); $ipAddress = $data['ip_address'] ?? ''; // Remove processed keys, put remaining into extraData $extraData = $data; unset($extraData['sandbox_id'], $extraData['status'], $extraData['created_at'], $extraData['ip_address']); return new self($sandboxId, $status, $createdAt, $ipAddress, $extraData); 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 $result = [ 'sandbox_id' => $this->sandboxId, 'status' => $this->status, 'created_at' => $this->createdAt, 'ip_address' => $this->ipAddress, ]; // AddExtraData return array_merge($result, $this->extraData); 
}
 /** * Get sandbox ID. */ 
    public function getSandboxId(): string 
{
 return $this->sandboxId; 
}
 /** * Set Sandbox ID. */ 
    public function setSandboxId(string $sandboxId): self 
{
 $this->sandboxId = $sandboxId; return $this; 
}
 /** * Get sandbox status */ 
    public function getStatus(): string 
{
 return $this->status; 
}
 /** * Set Sandbox status */ 
    public function setStatus(string $status): self 
{
 $this->status = $status; return $this; 
}
 /** * Get creation timestamp. */ 
    public function getCreatedAt(): float 
{
 return $this->createdAt; 
}
 /** * Set Creation timestamp. */ 
    public function setCreatedAt(float $createdAt): self 
{
 $this->createdAt = $createdAt; return $this; 
}
 /** * GetIPAddress */ 
    public function getIpAddress(): string 
{
 return $this->ipAddress; 
}
 /** * Set IPAddress */ 
    public function setIpAddress(string $ipAddress): self 
{
 $this->ipAddress = $ipAddress; return $this; 
}
 /** * GetExtraData. */ 
    public function getExtraData(): array 
{
 return $this->extraData; 
}
 /** * Set ExtraData. */ 
    public function setExtraData(array $extraData): self 
{
 $this->extraData = $extraData; return $this; 
}
 /** * Get specified field from extra data. * @param null|mixed $default */ 
    public function getExtraValue(string $key, $default = null) 
{
 return $this->extraData[$key] ?? $default; 
}
 /** * Set specified field in extra data. * @param mixed $value */ 
    public function setExtraValue(string $key, $value): self 
{
 $this->extraData[$key] = $value; return $this; 
}
 
}
 

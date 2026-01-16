<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\BatchStatusResult;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\GatewayResult;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\SandboxStatusResult;
/** * Sandbox Gateway Interface * Defines sandbox lifecycle management and agent forwarding functionality. */

interface SandboxGatewayInterface 
{
 /** * Set user context for the current request. * This method should be called before making any requests that require user information. * * @param null|string $userId user ID * @param null|string $organizationCode Organization code * @return self Returns self for method chaining */ 
    public function setuser Context(?string $userId, ?string $organizationCode): self; /** * Clear user context. * * @return self Returns self for method chaining */ 
    public function clearuser Context(): self; /** * Createsandbox . * * @param string $projectId Project ID * @param string $sandboxId Sandbox ID * @param string $workDir Sandbox working directory * @return GatewayResult CreateResultSuccessdataincluding sandbox_id */ 
    public function createSandbox(string $projectId, string $sandboxId, string $workDir): GatewayResult; /** * Get single sandbox status. * * @param string $sandboxId Sandbox ID * @return SandboxStatusResult Sandbox status result */ 
    public function getSandboxStatus(string $sandboxId): SandboxStatusResult; /** * Get batch sandbox status. * * @param array $sandboxIds Sandbox ID list * @return BatchStatusResult Batch status result */ 
    public function getBatchSandboxStatus(array $sandboxIds): BatchStatusResult; /** * Proxy request to sandbox. * * @param string $sandboxId Sandbox ID * @param string $method HTTP method * @param string $path Target path * @param array $data Request data * @param array $headers Additional headers * @return GatewayResult Proxy result */ 
    public function proxySandboxRequest( string $sandboxId, string $method, string $path, array $data = [], array $headers = [] ): GatewayResult; /** * Ensuresandbox Existand Available. * * @param string $sandboxId Sandbox ID * @param string $projectId Project ID * @param string $workDir Working directory * @return string ActualUsingSandbox ID */ 
    public function ensureSandboxAvailable(string $sandboxId, string $projectId, string $workDir): string; 
    public function uploadFile(string $sandboxId, array $filePaths, string $projectId, string $organizationCode, string $taskId): GatewayResult; /** * CopyFileSync. * * @param array $files FileCopyItemArrayFormat[['source_oss_path' => 'xxx', 'target_oss_path' => 'xxx'], ...] * @return GatewayResult CopyResult */ 
    public function copyFiles(array $files): GatewayResult; /** * sandbox . * * @param string $messageId MessageID * @param string $contextType ContextTypeusually as continue * @return GatewayResult Result */ 
    public function upgradeSandbox(string $messageId, string $contextType = 'continue'): GatewayResult; 
}
 

<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AbstractSandboxOS;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter\Request\FileConverterRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter\Response\FileConverterResponse;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayInterface;
use Exception;
use Hyperf\Logger\LoggerFactory;

class FileConverterService extends AbstractSandboxOS implements FileConverterInterface 
{
 
    public function __construct( LoggerFactory $loggerFactory, 
    private SandboxGatewayInterface $gateway ) 
{
 parent::__construct($loggerFactory); 
}
 
    public function convert(string $userId, string $organizationCode, string $sandboxId, string $projectId, FileConverterRequest $request, string $workDir): FileConverterResponse 
{
 $requestData = $request->toArray(); try 
{
 // Using ensureSandbox MethodEnsuresandbox Exist $this->gateway->setuser Context($userId, $organizationCode); $actualSandboxId = $this->gateway->ensureSandboxAvailable($sandboxId, $projectId, $workDir); // Thendirectly ProxyRequestsandbox $result = $this->gateway->proxySandboxRequest( $actualSandboxId, 'POST', 'api/file/converts', $requestData ); $response = FileConverterResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('FileConverter Conversion successful', [ 'original_sandbox_id' => $sandboxId, 'actual_sandbox_id' => $actualSandboxId, 'project_id' => $projectId, 'batch_id' => $response->getBatchId(), 'converted_files_count' => count($response->getConvertedFiles()), ]); 
}
 else 
{
 $this->logger->error('FileConverter Conversion failed', [ 'sandbox_id' => $sandboxId, 'project_id' => $projectId, 'code' => $response->getCode(), 'message' => $response->getMessage(), ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('FileConverter Unexpected error during conversion', [ 'sandbox_id' => $sandboxId, 'project_id' => $projectId, 'error' => $e->getMessage(), ]); return FileConverterResponse::fromApiResponse([ 'code' => -1, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 
    public function queryConvertResult(string $sandboxId, string $projectId, string $taskKey): FileConverterResponse 
{
 $this->logger->info('FileConverter Starting query conversion result', [ 'sandbox_id' => $sandboxId, 'project_id' => $projectId, 'task_key' => $taskKey, ]); try 
{
 // directly query ConvertResultcheck Sandbox statusas create Methodsandbox $result = $this->gateway->proxySandboxRequest( $sandboxId, 'GET', 'api/file/converts/' . $taskKey, ); $response = FileConverterResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('FileConverter query conversion result successful', [ 'sandbox_id' => $sandboxId, 'project_id' => $projectId, 'task_key' => $taskKey, 'batch_id' => $response->getBatchId(), ]); 
}
 else 
{
 // Ifyes Sandbox does not existor Connection failedClearError message $errorMessage = $response->getMessage(); if (strpos($errorMessage, 'sandbox') !== false || strpos($errorMessage, 'timeout') !== false) 
{
 $errorMessage = 'Sandbox does not existor already Exitcannot query ConvertResultPlease check sandbox status or resubmit convert task'; 
}
 $this->logger->error('FileConverter query ConvertResultsandbox Return ed Exception', [ 'sandbox_id' => $sandboxId, 'project_id' => $projectId, 'task_key' => $taskKey, 'code' => $response->getCode(), 'message' => $response->getMessage(), 'user_friendly_message' => $errorMessage, ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('FileConverter Unexpected error during query conversion result', [ 'sandbox_id' => $sandboxId, 'project_id' => $projectId, 'task_key' => $taskKey, 'error' => $e->getMessage(), ]); return FileConverterResponse::fromApiResponse([ 'code' => -1, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 
}
 

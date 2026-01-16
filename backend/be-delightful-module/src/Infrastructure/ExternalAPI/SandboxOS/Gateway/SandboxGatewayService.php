<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway;

use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AbstractSandboxOS;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Exception\SandboxOperationException;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\ResponseCode;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\SandboxStatus;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\BatchStatusResult;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\GatewayResult;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\SandboxStatusResult;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Hyperf\Logger\LoggerFactory;
use RuntimeException;
use Throwable;
use function Hyperf\Support\retry;
/** * sandbox ServiceImplementation * sandbox LifecycleProxyForward. */

class SandboxGatewayService extends AbstractSandboxOS implements SandboxGatewayInterface 
{
 private ?string $userId = null; private ?string $organizationCode = null; 
    public function __construct(LoggerFactory $loggerFactory) 
{
 parent::__construct($loggerFactory); 
}
 /** * Set user context for the current request. * This method should be called before making any requests that require user information. */ 
    public function setuser Context(?string $userId, ?string $organizationCode): self 
{
 $this->userId = $userId; $this->organizationCode = $organizationCode; return $this; 
}
 /** * Clear user context. */ 
    public function clearuser Context(): self 
{
 $this->userId = null; $this->organizationCode = null; return $this; 
}
 /** * Createsandbox . */ 
    public function createSandbox(string $projectId, string $sandboxId, string $workDir): GatewayResult 
{
 // In local debugging mode, return mock success result if (! $this->isEnabledSandbox()) 
{
 $this->logger->info('[Sandbox][Gateway] Local debugging mode: skipping sandbox creation', [ 'sandbox_id' => $sandboxId, 'project_id' => $projectId, 'work_dir' => $workDir, ]); return GatewayResult::success([ 'sandbox_id' => $sandboxId, ], 'Sandbox creation skipped (local debugging mode)'); 
}
 $config = ['project_id' => $projectId, 'sandbox_id' => $sandboxId, 'project_oss_path' => $workDir]; $this->logger->info('[Sandbox][Gateway] Creating sandbox', [ 'config' => $config, 'max_retries' => 5, 'retry_delay' => 30000, ]); try 
{
 return retry(5, function () use ($config, $sandboxId) 
{
 try 
{
 $response = $this->getClient()->post($this->buildApiPath('api/v1/sandboxes'), [ 'headers' => $this->getAuthHeaders(), 'json' => $config, 'timeout' => 30, ]);
$responseData = json_decode($response->getBody()->getContents(), true); // AddDebugLogPrintoriginal APIResponseData $this->logger->info('[Sandbox][Gateway] Raw API response data', [ 'response_data' => $responseData, 'json_last_error' => json_last_error(), 'json_last_error_msg' => json_last_error_msg(), ]); $result = GatewayResult::fromApiResponse($responseData ?? []); // AddDebugLogcheck GatewayResult Object $this->logger->info('[Sandbox][Gateway] GatewayResult object analysis', [ 'result_class' => get_class($result), 'result_is_success' => $result->isSuccess(), 'result_code' => $result->getCode(), 'result_message' => $result->getMessage(), 'result_data_raw' => $result->getData(), 'result_data_type' => gettype($result->getData()), 'result_data_json' => json_encode($result->getData()), 'sandbox_id_via_getDataValue' => $result->getDataValue('sandbox_id'), 'sandbox_id_via_getData_direct' => $result->getData()['sandbox_id'] ?? 'KEY_NOT_FOUND', ]); if ($result->isSuccess()) 
{
 $sandboxId = $result->getDataValue('sandbox_id'); $this->logger->info('[Sandbox][Gateway] Sandbox created successfully', [ 'sandbox_id' => $sandboxId, ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Gateway] Failed to create sandbox', [ 'code' => $result->getCode(), 'message' => $result->getMessage(), ]); 
}
 return $result; 
}
 catch (GuzzleException $e) 
{
 $isRetryableError = $this->isRetryableError($e); $this->logger->error('[Sandbox][Gateway] HTTP error when creating sandbox', [ 'error' => $e->getMessage(), 'code' => $e->getCode(), 'is_retryable' => $isRetryableError, ]); // Only retry for retryable errors if (! $isRetryableError) 
{
 return GatewayResult::error('HTTP request failed: ' . $e->getMessage()); 
}
 // Re-throw for retry mechanism to handle throw $e; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Gateway] Unexpected error when creating sandbox', [ 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); return GatewayResult::error('Unexpected error: ' . $e->getMessage()); 
}
 
}
, 30000); // 30 seconds delay between retries 
}
 catch (Throwable $e) 
{
 $this->logger->error('[Sandbox][Gateway] All retry attempts failed for creating sandbox', [ 'config' => $config, 'error' => $e->getMessage(), ]); return GatewayResult::error('HTTP request failed after retries: ' . $e->getMessage()); 
}
 
}
 /** * GetSingleSandbox status */ 
    public function getSandboxStatus(string $sandboxId): SandboxStatusResult 
{
 // In local debugging mode, return mock running status if (! $this->isEnabledSandbox()) 
{
 $this->logger->debug('[Sandbox][Gateway] Local debugging mode: returning mock running status', [ 'sandbox_id' => $sandboxId, ]); return SandboxStatusResult::fromApiResponse([ 'code' => ResponseCode::SUCCESS, 'message' => 'Success (local debugging mode)', 'data' => [ 'sandbox_id' => $sandboxId, 'status' => SandboxStatus::RUNNING, ], ]); 
}
 $this->logger->info('[Sandbox][Gateway] Getting sandbox status', [ 'sandbox_id' => $sandboxId, 'max_retries' => 3, ]); try 
{
 return retry(3, function () use ($sandboxId) 
{
 try 
{
 $response = $this->getClient()->get($this->buildApiPath( api/v1/sandboxes/
{
$sandboxId
}
 ), [ 'headers' => $this->getAuthHeaders(), 'timeout' => 10, ]);
$responseData = json_decode($response->getBody()->getContents(), true); if (empty($responseData)) 
{
 throw new Exception('Sandbox status response is empty'); 
}
 $result = SandboxStatusResult::fromApiResponse($responseData); $this->logger->info('[Sandbox][Gateway] Sandbox status retrieved', [ 'sandbox_id' => $sandboxId, 'status' => $result->getStatus(), 'success' => $result->isSuccess(), ]); if ($result->getCode() === ResponseCode::NOT_FOUND) 
{
 $result->setStatus(SandboxStatus::NOT_FOUND); $result->setSandboxId($sandboxId); 
}
 return $result; 
}
 catch (GuzzleException $e) 
{
 $isRetryableError = $this->isRetryableError($e); $this->logger->error('[Sandbox][Gateway] HTTP error when getting sandbox status', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), 'code' => $e->getCode(), 'is_retryable' => $isRetryableError, ]); // Only retry for retryable errors if (! $isRetryableError) 
{
 return SandboxStatusResult::fromApiResponse([ 'code' => 2000, 'message' => 'HTTP request failed: ' . $e->getMessage(), 'data' => ['sandbox_id' => $sandboxId], ]); 
}
 // Re-throw for retry mechanism to handle throw $e; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Gateway] Unexpected error when getting sandbox status', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), ]); return SandboxStatusResult::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => ['sandbox_id' => $sandboxId], ]); 
}
 
}
, 1000); // Start with 1 second delay 
}
 catch (Throwable $e) 
{
 $this->logger->error('[Sandbox][Gateway] All retry attempts failed for getting sandbox status', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), ]); return SandboxStatusResult::fromApiResponse([ 'code' => 2000, 'message' => 'HTTP request failed after retries: ' . $e->getMessage(), 'data' => ['sandbox_id' => $sandboxId], ]); 
}
 
}
 /** * BatchGet sandbox status */ 
    public function getBatchSandboxStatus(array $sandboxIds): BatchStatusResult 
{
 // In local debugging mode, return mock running status for all sandboxes if (! $this->isEnabledSandbox()) 
{
 // Filter out empty or null sandbox IDs $filteredSandboxIds = array_filter($sandboxIds, function ($id) 
{
 return ! empty(trim($id)); 
}
); $mockStatuses = []; foreach ($filteredSandboxIds as $sandboxId) 
{
 $mockStatuses[] = [ 'sandbox_id' => $sandboxId, 'status' => SandboxStatus::RUNNING, ]; 
}
 $this->logger->debug('[Sandbox][Gateway] Local debugging mode: returning mock batch sandbox status', [ 'sandbox_ids' => $filteredSandboxIds, 'count' => count($mockStatuses), ]); return BatchStatusResult::fromApiResponse([ 'code' => ResponseCode::SUCCESS, 'message' => 'Success (local debugging mode)', 'data' => $mockStatuses, ]); 
}
 $this->logger->debug('[Sandbox][Gateway] Getting batch sandbox status', [ 'sandbox_ids' => $sandboxIds, 'count' => count($sandboxIds), 'max_retries' => 3, ]); if (empty($sandboxIds)) 
{
 return BatchStatusResult::fromApiResponse([ 'code' => 1000, 'message' => 'Success', 'data' => [], ]); 
}
 // Filter out empty or null sandbox IDs $filteredSandboxIds = array_filter($sandboxIds, function ($id) 
{
 return ! empty(trim($id)); 
}
); if (empty($filteredSandboxIds)) 
{
 $this->logger->warning('[Sandbox][Gateway] All sandbox IDs are empty after filtering', [ 'original_ids' => $sandboxIds, ]); return BatchStatusResult::fromApiResponse([ 'code' => 2000, 'message' => 'All sandbox IDs are empty', 'data' => [], ]); 
}
 try 
{
 return retry(3, function () use ($filteredSandboxIds) 
{
 try 
{
 $response = $this->getClient()->post($this->buildApiPath('api/v1/sandboxes/queries'), [ 'headers' => $this->getAuthHeaders(), 'json' => ['sandbox_ids' => array_values($filteredSandboxIds)], // Ensure indexed array 'timeout' => 15, ]);
$responseData = json_decode($response->getBody()->getContents(), true); if (empty($responseData)) 
{
 throw new Exception('Batch sandbox status response is empty'); 
}
 $result = BatchStatusResult::fromApiResponse($responseData); $this->logger->debug('[Sandbox][Gateway] Batch sandbox status retrieved', [ 'requested_count' => count($filteredSandboxIds), 'returned_count' => $result->getTotalCount(), 'running_count' => $result->getRunningCount(), 'success' => $result->isSuccess(), ]); return $result; 
}
 catch (GuzzleException $e) 
{
 $isRetryableError = $this->isRetryableError($e); $this->logger->error('[Sandbox][Gateway] HTTP error when getting batch sandbox status', [ 'sandbox_ids' => $filteredSandboxIds, 'error' => $e->getMessage(), 'code' => $e->getCode(), 'is_retryable' => $isRetryableError, ]); // Only retry for retryable errors if (! $isRetryableError) 
{
 return BatchStatusResult::fromApiResponse([ 'code' => 2000, 'message' => 'HTTP request failed: ' . $e->getMessage(), 'data' => [], ]); 
}
 // Re-throw for retry mechanism to handle throw $e; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Gateway] Unexpected error when getting batch sandbox status', [ 'sandbox_ids' => $filteredSandboxIds, 'error' => $e->getMessage(), ]); return BatchStatusResult::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
, 1000); // Start with 1 second delay 
}
 catch (Throwable $e) 
{
 $this->logger->error('[Sandbox][Gateway] All retry attempts failed for batch sandbox status', [ 'sandbox_ids' => $filteredSandboxIds, 'error' => $e->getMessage(), ]); return BatchStatusResult::fromApiResponse([ 'code' => 2000, 'message' => 'HTTP request failed after retries: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 /** * ProxyForwardRequestsandbox . */ 
    public function proxySandboxRequest( string $sandboxId, string $method, string $path, array $data = [], array $headers = [] ): GatewayResult 
{
 $isLocalMode = ! $this->isEnabledSandbox(); $mode = $isLocalMode ? 'local-direct' : 'sandbox-proxy'; $this->logger->debug('[Sandbox][Gateway] Proxying request', [ 'mode' => $mode, 'sandbox_id' => $sandboxId, 'method' => $method, 'path' => $path, 'has_data' => ! empty($data), 'max_retries' => 3, ]); try 
{
 return retry(3, function () use ($sandboxId, $method, $path, $data, $headers, $mode) 
{
 try 
{
 $requestOptions = [ 'headers' => array_merge($this->getAuthHeaders(), $headers), 'timeout' => 30, ];
// Add request body based on method if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH']) && ! empty($data)) 
{
 $requestOptions['json'] = $data; 
}
 $proxyPath = $this->buildProxyPath($sandboxId, $path); // $proxyPath = $path; $response = $this->getClient()->request($method, $this->buildApiPath($proxyPath), $requestOptions); $responseData = json_decode($response->getBody()->getContents(), true); if (empty($responseData)) 
{
 throw new Exception('Proxy sandbox status response is empty'); 
}
 $result = GatewayResult::fromApiResponse($responseData); $this->logger->debug('[Sandbox][Gateway] Proxy request completed', [ 'mode' => $mode, 'sandbox_id' => $sandboxId, 'method' => $method, 'path' => $path, 'success' => $result->isSuccess(), 'response_code' => $result->getCode(), ]); return $result; 
}
 catch (GuzzleException $e) 
{
 $isRetryableError = $this->isRetryableError($e); $this->logger->error('[Sandbox][Gateway] HTTP error when proxying request', [ 'sandbox_id' => $sandboxId, 'method' => $method, 'path' => $path, 'error' => $e->getMessage(), 'code' => $e->getCode(), 'is_retryable' => $isRetryableError, ]); // Only retry for retryable errors if (! $isRetryableError) 
{
 return GatewayResult::error('HTTP request failed: ' . $e->getMessage()); 
}
 // Re-throw for retry mechanism to handle throw $e; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Gateway] Unexpected error when proxying request', [ 'sandbox_id' => $sandboxId, 'method' => $method, 'path' => $path, 'error' => $e->getMessage(), ]); return GatewayResult::error('Unexpected error: ' . $e->getMessage()); 
}
 
}
, 1000); // Start with 1 second delay 
}
 catch (Throwable $e) 
{
 $this->logger->error('[Sandbox][Gateway] All retry attempts failed', [ 'sandbox_id' => $sandboxId, 'method' => $method, 'path' => $path, 'error' => $e->getMessage(), ]); return GatewayResult::error('HTTP request failed after retries: ' . $e->getMessage()); 
}
 
}
 
    public function uploadFile(string $sandboxId, array $filePaths, string $projectId, string $organizationCode, string $taskId): GatewayResult 
{
 $this->logger->info('[Sandbox][Gateway] uploadFile', ['sandbox_id' => $sandboxId, 'file_paths' => $filePaths, 'project_id' => $projectId, 'organization_code' => $organizationCode, 'task_id' => $taskId]); return $this->proxySandboxRequest($sandboxId, 'POST', 'api/file/upload', ['sandbox_id' => $sandboxId, 'file_paths' => $filePaths, 'project_id' => $projectId, 'organization_code' => $organizationCode, 'task_id' => $taskId]); 
}
 /** * Ensuresandbox Existand Available. */ 
    public function ensureSandboxAvailable(string $sandboxId, string $projectId, string $workDir = ''): string 
{
 // In local debugging mode, skip sandbox creation and status check if (! $this->isEnabledSandbox()) 
{
 $this->logger->info('[Sandbox][Gateway] Local debugging mode: skipping sandbox availability check', [ 'sandbox_id' => $sandboxId, 'project_id' => $projectId, ]); return $sandboxId; 
}
 try 
{
 // check sandbox whether Available if (! empty($sandboxId)) 
{
 $statusResult = $this->getSandboxStatus($sandboxId); // Ifsandbox Existand Statusas Runningdirectly Return if ($statusResult->isSuccess() && $statusResult->getCode() === ResponseCode::SUCCESS && SandboxStatus::isAvailable($statusResult->getStatus())) 
{
 $this->logger->info('ensureSandboxAvailable Sandbox is available, using existing sandbox', [ 'sandbox_id' => $sandboxId, ]); return $sandboxId; 
}
 // IfSandbox statusas PendingWaitingas Running if ($statusResult->isSuccess() && $statusResult->getCode() === ResponseCode::SUCCESS && $statusResult->getStatus() === SandboxStatus::PENDING) 
{
 $this->logger->info('ensureSandboxAvailable Sandbox is pending, waiting for it to become running', [ 'sandbox_id' => $sandboxId, ]); try 
{
 // WaitingHavesandbox as Running return $this->waitForSandboxRunning($sandboxId, 'existing'); 
}
 catch (SandboxOperationException $e) 
{
 // IfWaitingFailedContinueCreateNewsandbox $this->logger->warning('ensureSandboxAvailable Failed to wait for existing sandbox, creating new sandbox', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), ]); 
}
 
}
 // record need CreateNewsandbox if ($statusResult->getCode() === ResponseCode::NOT_FOUND) 
{
 $this->logger->info('ensureSandboxAvailable Sandbox not found, creating new sandbox', [ 'sandbox_id' => $sandboxId, ]); 
}
 else 
{
 $this->logger->info('ensureSandboxAvailable Sandbox status is not available, creating new sandbox', [ 'sandbox_id' => $sandboxId, 'current_status' => $statusResult->getStatus(), ]); 
}
 
}
 else 
{
 $this->logger->info('ensureSandboxAvailable Sandbox ID is empty, creating new sandbox'); 
}
 // CreateNewsandbox $createResult = $this->createSandbox($projectId, $sandboxId, $workDir); if (! $createResult->isSuccess()) 
{
 $this->logger->error('ensureSandboxAvailable Failed to create sandbox', [ 'requested_sandbox_id' => $sandboxId, 'project_id' => $projectId, 'code' => $createResult->getCode(), 'message' => $createResult->getMessage(), ]); throw new SandboxOperationException('Create sandbox', $createResult->getMessage(), $createResult->getCode()); 
}
 $newSandboxId = $createResult->getDataValue('sandbox_id'); // AddDebugLogcheck whether CorrectGeted sandbox_id $this->logger->info('ensureSandboxAvailable Created sandbox, starting to wait for it to become running', [ 'requested_sandbox_id' => $sandboxId, 'new_sandbox_id' => $newSandboxId, 'project_id' => $projectId, 'create_result_data' => $createResult->getData(), ]); // IfDon't haveGet sandbox_iddirectly Return Error if (empty($newSandboxId) || $newSandboxId !== $sandboxId) 
{
 $this->logger->error('ensureSandboxAvailable Failed to get sandbox_id from create result', [ 'requested_sandbox_id' => $sandboxId, 'project_id' => $projectId, 'new_sandbox_id' => $newSandboxId, 'create_result_data' => $createResult->getData(), ]); throw new SandboxOperationException('Get sandbox_id from create result', 'Failed to get sandbox_id from create result', 2001); 
}
 // WaitingNewsandbox as Running return $this->waitForSandboxRunning($newSandboxId, 'new'); 
}
 catch (SandboxOperationException $e) 
{
 // NewThrowsandbox Exception $this->logger->error('ensureSandboxAvailable Error ensuring sandbox availability', [ 'sandbox_id' => $sandboxId, 'project_id' => $projectId, 'error' => $e->getMessage(), ]); throw $e; 
}
 catch (Exception $e) 
{
 $this->logger->error('ensureSandboxAvailable Error ensuring sandbox availability', [ 'sandbox_id' => $sandboxId, 'project_id' => $projectId, 'error' => $e->getMessage(), ]); throw new SandboxOperationException('Ensure sandbox availability', $e->getMessage(), 2000); 
}
 
}
 /** * CopyFileSync. */ 
    public function copyFiles(array $files): GatewayResult 
{
 $requestData = [ 'files' => $files, ]; $this->logger->info('[Sandbox][Gateway] Copying files', [ 'files_count' => count($requestData['files']), 'max_retries' => 3, ]); try 
{
 return retry(3, function () use ($requestData) 
{
 try 
{
 $response = $this->getClient()->post($this->buildApiPath('api/v1/files/copy'), [ 'headers' => $this->getAuthHeaders(), 'json' => $requestData, 'timeout' => 60, // Increased timeout for file copy operations ]);
$responseData = json_decode($response->getBody()->getContents(), true); $this->logger->info('[Sandbox][Gateway] Raw API response for file copy', [ 'response_data' => $responseData, 'json_last_error' => json_last_error(), 'json_last_error_msg' => json_last_error_msg(), ]); $result = GatewayResult::fromApiResponse($responseData ?? []); if ($result->isSuccess()) 
{
 $this->logger->info('[Sandbox][Gateway] File copy completed successfully', [ 'files_count' => count($requestData['files']), ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Gateway] Failed to copy files', [ 'files_count' => count($requestData['files']), 'code' => $result->getCode(), 'message' => $result->getMessage(), ]); 
}
 return $result; 
}
 catch (GuzzleException $e) 
{
 $isRetryableError = $this->isRetryableError($e); $this->logger->error('[Sandbox][Gateway] HTTP error when copying files', [ 'files_count' => count($requestData['files']), 'error' => $e->getMessage(), 'code' => $e->getCode(), 'is_retryable' => $isRetryableError, ]); // Only retry for retryable errors if (! $isRetryableError) 
{
 return GatewayResult::error('HTTP request failed: ' . $e->getMessage()); 
}
 // Re-throw for retry mechanism to handle throw $e; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Gateway] Unexpected error when copying files', [ 'files_count' => count($requestData['files']), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); return GatewayResult::error('Unexpected error: ' . $e->getMessage()); 
}
 
}
, 1000); // Start with 1 second delay between retries 
}
 catch (Throwable $e) 
{
 $this->logger->error('[Sandbox][Gateway] All retry attempts failed for copying files', [ 'files_count' => count($requestData['files']), 'error' => $e->getMessage(), ]); return GatewayResult::error('HTTP request failed after retries: ' . $e->getMessage()); 
}
 
}
 /** * sandbox . */ 
    public function upgradeSandbox(string $messageId, string $contextType = 'continue'): GatewayResult 
{
 $config = [ 'message_id' => $messageId, 'context_type' => $contextType, ]; $this->logger->info('[Sandbox][Gateway] Upgrading sandbox', [ 'message_id' => $messageId, 'context_type' => $contextType, 'max_retries' => 3, ]); try 
{
 return retry(3, function () use ($config, $messageId) 
{
 try 
{
 $response = $this->getClient()->put($this->buildApiPath('api/v1/sandboxes/upgrade'), [ 'headers' => $this->getAuthHeaders(), 'json' => $config, 'timeout' => 60, // need Time ]);
$responseData = json_decode($response->getBody()->getContents(), true); $this->logger->info('[Sandbox][Gateway] Raw upgrade API response', [ 'response_data' => $responseData, 'message_id' => $messageId, ]); $result = GatewayResult::fromApiResponse($responseData ?? []); if ($result->isSuccess()) 
{
 $this->logger->info('[Sandbox][Gateway] Sandbox upgraded successfully', [ 'message_id' => $messageId, ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Gateway] Failed to upgrade sandbox', [ 'message_id' => $messageId, 'code' => $result->getCode(), 'message' => $result->getMessage(), ]); 
}
 return $result; 
}
 catch (GuzzleException $e) 
{
 $isRetryableError = $this->isRetryableError($e); $this->logger->error('[Sandbox][Gateway] HTTP error when upgrading sandbox', [ 'message_id' => $messageId, 'error' => $e->getMessage(), 'code' => $e->getCode(), 'is_retryable' => $isRetryableError, ]); if (! $isRetryableError) 
{
 return GatewayResult::error('HTTP request failed: ' . $e->getMessage()); 
}
 throw $e; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Gateway] Unexpected error when upgrading sandbox', [ 'message_id' => $messageId, 'error' => $e->getMessage(), ]); return GatewayResult::error('Unexpected error: ' . $e->getMessage()); 
}
 
}
, 1000); 
}
 catch (Throwable $e) 
{
 $this->logger->error('[Sandbox][Gateway] All retry attempts failed for upgrading sandbox', [ 'message_id' => $messageId, 'error' => $e->getMessage(), ]); return GatewayResult::error('HTTP request failed after retries: ' . $e->getMessage()); 
}
 
}
 /** * Override parent getBaseUrl to support local debugging mode. * When SANDBOX_ENABLE=false, SANDBOX_GATEWAY is used as local service URL. */ 
    protected function getBaseUrl(): string 
{
 // In local debugging mode, use SANDBOX_GATEWAY as local service URL if (! $this->isEnabledSandbox()) 
{
 $localServiceUrl = config('super-magic.sandbox.gateway', '');
if (empty($localServiceUrl)) 
{
 throw new RuntimeException('SANDBOX_GATEWAY environment variable is not set for local debugging mode'); 
}
 $this->logger->debug('[Sandbox][Gateway] Using local service URL (sandbox disabled)', [ 'local_service_url' => $localServiceUrl, ]); return $localServiceUrl; 
}
 // Normal mode: use parent implementation return parent::getBaseUrl();

}
 /** * Override parent buildProxyPath to support local debugging mode. * When SANDBOX_ENABLE=false, return original path without sandbox proxy prefix. */ 
    protected function buildProxyPath(string $sandboxId, string $agentPath): string 
{
 // In local debugging mode, return original path directly if (! $this->isEnabledSandbox()) 
{
 $this->logger->debug('[Sandbox][Gateway] Building direct proxy path (sandbox disabled)', [ 'sandbox_id' => $sandboxId, 'original_path' => $agentPath, ]); return ltrim($agentPath, '/'); 
}
 // Normal mode: use parent implementation return parent::buildProxyPath($sandboxId, $agentPath);

}
 /** * Override parent getAuthHeaders to include user-specific headers. */ 
    protected function getAuthHeaders(): array 
{
 $headers = parent::getAuthHeaders(); if ($this->userId !== null) 
{
 $headers['magic-user-id'] = $this->userId; 
}
 if ($this->organizationCode !== null) 
{
 $headers['magic-organization-code'] = $this->organizationCode; 
}
 # Determineheaderin whether including request_idIfDon't haveFromContextin Get if (empty($headers['request-id'])) 
{
 $requestId = CoContext::getRequestId() ?: (string) IdGenerator::getSnowId(); $headers['request-id'] = $requestId; 
}
 $traceId = CoContext::getTraceId() ?: (string) IdGenerator::getSnowId(); $headers['x-b3-trace-id'] = $traceId; $headers['tracer.trace_id'] = $traceId; return $headers; 
}
 /** * check if the error is retryable. * Retryable errors include timeout, connection errors, and 5xx server errors. */ 
    private function isRetryableError(GuzzleException $e): bool 
{
 // First, check for specific Guzzle exception types // ConnectException includes all network connection issues (timeouts, DNS errors, etc.) if ($e instanceof ConnectException) 
{
 return true; 
}
 // ServerException for 5xx HTTP errors - these are often temporary if ($e instanceof ServerException) 
{
 return true; 
}
 // For RequestException (parent of many exceptions), check if it has a response if ($e instanceof RequestException && $e->hasResponse()) 
{
 $statusCode = $e->getResponse()->getStatusCode(); // Retry on 5xx server errors if ($statusCode >= 500 && $statusCode < 600) 
{
 return true; 
}
 // Retry on specific 4xx errors that might be temporary $retryable4xxCodes = [ 408, // Request Timeout 429, // Too Many Requests (rate limiting) ]; if (in_array($statusCode, $retryable4xxCodes)) 
{
 return true; 
}
 
}
 // Fall back to string matching for specific cURL errors (as backup) $errorMessage = $e->getMessage(); // Timeout errors if (strpos($errorMessage, 'cURL error 28') !== false) 
{
 // Operation timed out return true; 
}
 // Connection errors if (strpos($errorMessage, 'cURL error 7') !== false) 
{
 // Couldn't connect to host return true; 
}
 // Other retryable cURL errors $retryableCurlErrors = [ 'cURL error 6', // Couldn't resolve host 'cURL error 52', // Empty reply from server 'cURL error 56', // Failure with receiving network data 'cURL error 35', // SSL connect error ]; foreach ($retryableCurlErrors as $curlError) 
{
 if (strpos($errorMessage, $curlError) !== false) 
{
 return true; 
}
 
}
 // Don't retry on: // - ClientException (4xx errors except 408, 429) // - Authentication errors // - Bad request format errors // - Other non-network related errors return false; 
}
 /** * Waitingsandbox as Running Status * * @param string $sandboxId Sandbox ID * @param string $type sandbox Typeexisting|newfor Log * @return string Return Sandbox IDSuccess * @throws SandboxOperationException WhenWaitingFailedThrowException */ 
    private function waitForSandboxRunning(string $sandboxId, string $type): string 
{
 $maxRetries = 15; // Wait at most about 30 seconds $retryDelay = 2; // Interval 2 seconds each time $this->logger->info( ensureSandboxAvailable Starting to wait for 
{
$type
}
 sandbox to become running , [ 'sandbox_id' => $sandboxId, 'type' => $type, 'max_retries' => $maxRetries, 'retry_delay' => $retryDelay, ]); for ($i = 0; $i < $maxRetries; ++$i) 
{
 $statusResult = $this->getSandboxStatus($sandboxId); if ($statusResult->isSuccess() && SandboxStatus::isAvailable($statusResult->getStatus())) 
{
 $this->logger->info( ensureSandboxAvailable 
{
$type
}
 sandbox is now running , [ 'sandbox_id' => $sandboxId, 'type' => $type, 'attempts' => $i + 1, ]); return $sandboxId; 
}
 // Ifyes Havesandbox and Statusas ExitedExit if ($type === 'existing' && $statusResult->getStatus() === SandboxStatus::EXITED) 
{
 $this->logger->info('ensureSandboxAvailable Existing sandbox exited while waiting', [ 'sandbox_id' => $sandboxId, 'current_status' => $statusResult->getStatus(), ]); throw new SandboxOperationException('Wait for existing sandbox', 'Existing sandbox exited while waiting', 2002); 
}
 $this->logger->info( ensureSandboxAvailable Waiting for 
{
$type
}
 sandbox to become ready... , [ 'sandbox_id' => $sandboxId, 'type' => $type, 'current_status' => $statusResult->getStatus(), 'attempt' => $i + 1, ]); sleep($retryDelay); 
}
 $this->logger->error( ensureSandboxAvailable Timeout waiting for 
{
$type
}
 sandbox to become running , [ 'sandbox_id' => $sandboxId, 'type' => $type, ]); throw new SandboxOperationException('Wait for sandbox ready', Timeout waiting for 
{
$type
}
 sandbox to become running , 2003); 
}
 
}
 

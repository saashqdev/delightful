<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AbstractSandboxOS;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Config\AsrAudioConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Config\AsrNoteFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Config\AsrTranscriptFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Asrrecord er\Response\Asrrecord erResponse;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Constants\SandboxEndpoints;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayInterface;
use Exception;
use Hyperf\Logger\LoggerFactory;
/** * ASR ServiceImplementation. */

class Asrrecord erService extends AbstractSandboxOS implements Asrrecord erInterface 
{
 
    public function __construct( LoggerFactory $loggerFactory, 
    private readonly SandboxGatewayInterface $gateway ) 
{
 parent::__construct($loggerFactory); 
}
 
    public function startTask( string $sandboxId, string $taskKey, string $sourceDir, string $workspaceDir = '.workspace', ?AsrNoteFileConfig $noteFileConfig = null, ?AsrTranscriptFileConfig $transcriptFileConfig = null ): Asrrecord erResponse 
{
 $requestData = [ 'task_key' => $taskKey, 'source_dir' => $sourceDir, 'workspace_dir' => $workspaceDir, ]; // AddFileConfigurationstart source_path if ($noteFileConfig !== null) 
{
 $requestData['note_file'] = [ 'source_path' => $noteFileConfig->getSourcePath(), ]; 
}
 // AddFluidIdentifyFileConfigurationstart source_path if ($transcriptFileConfig !== null) 
{
 $requestData['transcript_file'] = [ 'source_path' => $transcriptFileConfig->getSourcePath(), ]; 
}
 try 
{
 $this->logger->info('ASR record er: Starting task', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'source_dir' => $sourceDir, 'workspace_dir' => $workspaceDir, 'note_file_source_path' => $noteFileConfig?->getSourcePath(), 'transcript_file_source_path' => $transcriptFileConfig?->getSourcePath(), ]); // call sandbox API $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', SandboxEndpoints::ASR_TASK_START, $requestData ); $response = Asrrecord erResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('ASR record er: Task started successfully', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'status' => $response->getStatus(), ]); 
}
 else 
{
 $this->logger->error('ASR record er: Failed to start task', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'code' => $response->code, 'message' => $response->message, ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('ASR record er: Unexpected error during start task', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'error' => $e->getMessage(), ]); return Asrrecord erResponse::fromApiResponse([ 'code' => -1, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 
    public function finishTask( string $sandboxId, string $taskKey, string $workspaceDir, AsrAudioConfig $audioConfig, ?AsrNoteFileConfig $noteFileConfig = null, ?AsrTranscriptFileConfig $transcriptFileConfig = null ): Asrrecord erResponse 
{
 // BuildRequestDataV2 StructureVersion $requestData = [ 'task_key' => $taskKey, 'workspace_dir' => $workspaceDir, 'audio' => $audioConfig->toArray(), ]; // AddFileConfiguration if ($noteFileConfig !== null) 
{
 $requestData['note_file'] = $noteFileConfig->toArray(); 
}
 // AddFluidIdentifyFileConfiguration if ($transcriptFileConfig !== null) 
{
 $requestData['transcript_file'] = $transcriptFileConfig->toArray(); 
}
 try 
{
 $this->logger->info('ASR record er: Finishing task (V2)', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'workspace_dir' => $workspaceDir, 'audio_config' => $audioConfig->toArray(), 'note_file_config' => $noteFileConfig?->toArray(), 'transcript_file_config' => $transcriptFileConfig?->toArray(), ]); // call sandbox API $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', SandboxEndpoints::ASR_TASK_FINISH, $requestData ); $response = Asrrecord erResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('ASR record er: Task finish request successful', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'status' => $response->getStatus(), 'file_path' => $response->getFilePath(), ]); 
}
 else 
{
 $this->logger->error('ASR record er: Failed to finish task', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'code' => $response->code, 'message' => $response->message, ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('ASR record er: Unexpected error during finish task', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'error' => $e->getMessage(), ]); return Asrrecord erResponse::fromApiResponse([ 'code' => -1, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 
    public function cancelTask( string $sandboxId, string $taskKey, string $workspaceDir = '.workspace' ): Asrrecord erResponse 
{
 $requestData = [ 'task_key' => $taskKey, 'workspace_dir' => $workspaceDir, ]; try 
{
 $this->logger->info('ASR record er: cancel ing task', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'workspace_dir' => $workspaceDir, ]); // call sandbox API $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', SandboxEndpoints::ASR_TASK_CANCEL, $requestData ); $response = Asrrecord erResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('ASR record er: Task canceled successfully', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'status' => $response->getStatus(), ]); 
}
 else 
{
 $this->logger->error('ASR record er: Failed to cancel task', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'code' => $response->code, 'message' => $response->message, ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('ASR record er: Unexpected error during cancel task', [ 'sandbox_id' => $sandboxId, 'task_key' => $taskKey, 'error' => $e->getMessage(), ]); return Asrrecord erResponse::fromApiResponse([ 'code' => -1, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 
}
 

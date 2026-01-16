<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AbstractSandboxOS;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\ChatMessageRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\check pointRollbackcheck Request;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\check pointRollbackCommitRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\check pointRollbackRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\check pointRollbackStartRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\check pointRollbackUndoRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\InitAgentRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\InterruptRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\SaveFilesRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\ScriptTaskRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Response\AgentResponse;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Constants\SandboxEndpoints;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayInterface;
use Exception;
use Hyperf\Logger\LoggerFactory;
/** * sandbox AgentServiceImplementation * ThroughGatewayForwardAgent */

class SandboxAgentService extends AbstractSandboxOS implements SandboxAgentInterface 
{
 
    public function __construct( LoggerFactory $loggerFactory, 
    private readonly SandboxGatewayInterface $gateway ) 
{
 parent::__construct($loggerFactory); 
}
 /** * InitializeAgent. */ 
    public function initAgent(string $sandboxId, InitAgentRequest $request): AgentResponse 
{
 $this->logger->info('[Sandbox][Agent] Initializing agent', [ 'sandbox_id' => $sandboxId, 'user_id' => $request->getuser Id(), 'task_mode' => $request->getTaskMode(), 'agent_mode' => $request->getAgentMode(), 'model_id' => $request->getModelId(), ]); try 
{
 // ThroughGatewayForwardAgent API $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', SandboxEndpoints::AGENT_MESSAGES_CHAT, $request->toArray() ); $response = AgentResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('[Sandbox][Agent] Agent initialized successfully', [ 'sandbox_id' => $sandboxId, 'agent_id' => $response->getAgentId(), ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Agent] Failed to initialize agent', [ 'sandbox_id' => $sandboxId, 'code' => $response->getCode(), 'message' => $response->getMessage(), ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Agent] Unexpected error when initializing agent', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); return AgentResponse::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 /** * SendMessagegive Agent. */ 
    public function sendChatMessage(string $sandboxId, ChatMessageRequest $request): AgentResponse 
{
 $this->logger->debug('[Sandbox][Agent] Sending chat message to agent', [ 'sandbox_id' => $sandboxId, 'user_id' => $request->getuser Id(), 'task_id' => $request->getTaskId(), 'prompt_length' => strlen($request->getPrompt()), 'model_id' => $request->getModelId(), ]); try 
{
 // ThroughGatewayForwardAgent API $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', SandboxEndpoints::AGENT_MESSAGES_CHAT, $request->toArray() ); $response = AgentResponse::fromGatewayResult($result); $this->logger->debug('[Sandbox][Agent] Chat message sent to agent', [ 'sandbox_id' => $sandboxId, 'success' => $response->isSuccess(), 'message_id' => $response->getMessageId(), 'has_response' => $response->hasResponseMessage(), ]); return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Agent] Unexpected error when sending chat message', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), ]); return AgentResponse::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 /** * SendInterruptMessagegive Agent. */ 
    public function sendInterruptMessage(string $sandboxId, InterruptRequest $request): AgentResponse 
{
 $this->logger->info('[Sandbox][Agent] Sending interrupt message to agent', [ 'sandbox_id' => $sandboxId, 'user_id' => $request->getuser Id(), 'task_id' => $request->getTaskId(), 'remark' => $request->getRemark(), ]); try 
{
 // ThroughGatewayForwardAgent API $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', SandboxEndpoints::AGENT_MESSAGES_CHAT, $request->toArray() ); $response = AgentResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('[Sandbox][Agent] Interrupt message sent successfully', [ 'sandbox_id' => $sandboxId, 'user_id' => $request->getuser Id(), 'task_id' => $request->getTaskId(), ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Agent] Failed to send interrupt message', [ 'sandbox_id' => $sandboxId, 'code' => $response->getCode(), 'message' => $response->getMessage(), ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Agent] Unexpected error when sending interrupt message', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), ]); return AgentResponse::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 /** * Getworkspace Status. */ 
    public function getWorkspaceStatus(string $sandboxId): AgentResponse 
{
 $this->logger->debug('[Sandbox][Agent] Getting workspace status', [ 'sandbox_id' => $sandboxId, ]); try 
{
 // ThroughGatewayForwardAgent API $result = $this->gateway->proxySandboxRequest( $sandboxId, 'GET', 'api/v1/workspace/status' ); $response = AgentResponse::fromGatewayResult($result); $this->logger->debug('[Sandbox][Agent] Workspace status retrieved', [ 'sandbox_id' => $sandboxId, 'success' => $response->isSuccess(), 'status' => $response->getDataValue('status'), ]); return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Agent] Unexpected error when getting workspace status', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), ]); return AgentResponse::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 /** * SaveFilesandbox . */ 
    public function saveFiles(string $sandboxId, SaveFilesRequest $request): AgentResponse 
{
 $this->logger->info('[Sandbox][Agent] Saving files to sandbox', [ 'sandbox_id' => $sandboxId, 'file_count' => $request->getFileCount(), ]); try 
{
 // ThroughGatewayForwardsandbox FileEditAPI $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', 'api/v1/files/save', $request->toArray() ); $response = AgentResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('[Sandbox][Agent] Files saved successfully', [ 'sandbox_id' => $sandboxId, 'file_count' => $request->getFileCount(), ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Agent] Failed to save files', [ 'sandbox_id' => $sandboxId, 'code' => $response->getCode(), 'message' => $response->getMessage(), ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Agent] Unexpected error when saving files', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), ]); return AgentResponse::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 
    public function executeScriptTask(string $sandboxId, ScriptTaskRequest $request): AgentResponse 
{
 $this->logger->info('[Sandbox][Agent] Executing script task', [ 'sandbox_id' => $sandboxId, 'task_id' => $request->getTaskId(), ]); try 
{
 // ThroughGatewayForwardsandbox FileEditAPI $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', '/api/task/script-task', $request->toArray() ); $response = AgentResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('[Sandbox][Agent] Files saved successfully', [ 'sandbox_id' => $sandboxId, 'script_name' => $request->getScriptName(), 'arguments' => $request->getArguments(), ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Agent] Failed to save files', [ 'sandbox_id' => $sandboxId, 'code' => $response->getCode(), 'message' => $response->getMessage(), ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Agent] Unexpected error when executing script task', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), ]); return AgentResponse::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 /** * Rollbackspecified checkpoint. */ 
    public function rollbackcheck point(string $sandboxId, check pointRollbackRequest $request): AgentResponse 
{
 $this->logger->info('[Sandbox][Agent] Rolling back to checkpoint', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), ]); try 
{
 // ThroughGatewayForwardsandbox checkpointRollbackAPI $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', 'api/checkpoints/rollback', $request->toArray() ); $response = AgentResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('[Sandbox][Agent] check point rollback successful', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), 'message' => $response->getMessage(), ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Agent] Failed to rollback checkpoint', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), 'code' => $response->getCode(), 'message' => $response->getMessage(), ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Agent] Unexpected error when rolling back checkpoint', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), 'error' => $e->getMessage(), ]); return AgentResponse::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 /** * StartRollbackspecified checkpointmark Statusdelete . */ 
    public function rollbackcheck pointStart(string $sandboxId, check pointRollbackStartRequest $request): AgentResponse 
{
 $this->logger->info('[Sandbox][Agent] Starting checkpoint rollback', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), ]); try 
{
 // ThroughGatewayForwardsandbox checkpointRollbackStartAPI $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', 'api/checkpoints/rollback/start', $request->toArray() ); $response = AgentResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('[Sandbox][Agent] check point rollback start successful', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), 'message' => $response->getMessage(), ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Agent] Failed to start checkpoint rollback', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), 'code' => $response->getCode(), 'message' => $response->getMessage(), ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Agent] Unexpected error when starting checkpoint rollback', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), 'error' => $e->getMessage(), ]); return AgentResponse::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 /** * SubmitRollbackspecified checkpointdelete recalled status Message. */ 
    public function rollbackcheck pointCommit(string $sandboxId, check pointRollbackCommitRequest $request): AgentResponse 
{
 $this->logger->info('[Sandbox][Agent] Committing checkpoint rollback', [ 'sandbox_id' => $sandboxId, ]); try 
{
 // ThroughGatewayForwardsandbox checkpointRollbackSubmitAPI $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', 'api/checkpoints/rollback/commit', $request->toArray() ); $response = AgentResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('[Sandbox][Agent] check point rollback commit successful', [ 'sandbox_id' => $sandboxId, 'message' => $response->getMessage(), ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Agent] Failed to commit checkpoint rollback', [ 'sandbox_id' => $sandboxId, 'code' => $response->getCode(), 'message' => $response->getMessage(), ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Agent] Unexpected error when committing checkpoint rollback', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), ]); return AgentResponse::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 /** * UndoRollbacksandbox checkpointrecalled status MessageResumeas NormalStatus. */ 
    public function rollbackcheck pointUndo(string $sandboxId, check pointRollbackUndoRequest $request): AgentResponse 
{
 $this->logger->info('[Sandbox][Agent] Undoing checkpoint rollback', [ 'sandbox_id' => $sandboxId, ]); try 
{
 // ThroughGatewayForwardsandbox checkpointRollbackUndoAPI $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', 'api/checkpoints/rollback/undo', $request->toArray() ); $response = AgentResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('[Sandbox][Agent] check point rollback undo successful', [ 'sandbox_id' => $sandboxId, 'message' => $response->getMessage(), ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Agent] Failed to undo checkpoint rollback', [ 'sandbox_id' => $sandboxId, 'code' => $response->getCode(), 'message' => $response->getMessage(), ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Agent] Unexpected error when undoing checkpoint rollback', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), ]); return AgentResponse::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 /** * check Rollbackspecified checkpointRow. */ 
    public function rollbackcheck pointcheck (string $sandboxId, check pointRollbackcheck Request $request): AgentResponse 
{
 $this->logger->info('[Sandbox][Agent] check ing checkpoint rollback feasibility', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), ]); try 
{
 // ThroughGatewayForwardsandbox checkpointRollbackcheck API $result = $this->gateway->proxySandboxRequest( $sandboxId, 'POST', 'api/checkpoints/rollback/check', $request->toArray() ); $response = AgentResponse::fromGatewayResult($result); if ($response->isSuccess()) 
{
 $this->logger->info('[Sandbox][Agent] check point rollback check successful', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), 'can_rollback' => $response->getDataValue('can_rollback'), ]); 
}
 else 
{
 $this->logger->error('[Sandbox][Agent] Failed to check checkpoint rollback', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), 'code' => $response->getCode(), 'message' => $response->getMessage(), ]); 
}
 return $response; 
}
 catch (Exception $e) 
{
 $this->logger->error('[Sandbox][Agent] Unexpected error when checking checkpoint rollback', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $request->getTargetMessageId(), 'error' => $e->getMessage(), ]); return AgentResponse::fromApiResponse([ 'code' => 2000, 'message' => 'Unexpected error: ' . $e->getMessage(), 'data' => [], ]); 
}
 
}
 
}
 

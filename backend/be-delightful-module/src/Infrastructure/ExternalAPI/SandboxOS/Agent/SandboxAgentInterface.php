<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent;

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
/** * sandbox AgentInterface * AgentThroughGatewayForwardImplementation. */

interface SandboxAgentInterface 
{
 /** * InitializeAgent. * * @param string $sandboxId Sandbox ID * @param InitAgentRequest $request InitializeRequest * @return AgentResponse InitializeResult */ 
    public function initAgent(string $sandboxId, InitAgentRequest $request): AgentResponse; /** * SendMessagegive Agent. * * @param string $sandboxId Sandbox ID * @param ChatMessageRequest $request MessageRequest * @return AgentResponse AgentResponse */ 
    public function sendChatMessage(string $sandboxId, ChatMessageRequest $request): AgentResponse; /** * SendInterruptMessagegive Agent. * * @param string $sandboxId Sandbox ID * @param InterruptRequest $request InterruptRequest * @return AgentResponse InterruptResponse */ 
    public function sendInterruptMessage(string $sandboxId, InterruptRequest $request): AgentResponse; /** * Getworkspace Status. * * @param string $sandboxId Sandbox ID * @return AgentResponse workspace StatusResponse */ 
    public function getWorkspaceStatus(string $sandboxId): AgentResponse; /** * SaveFilesandbox . * * @param string $sandboxId Sandbox ID * @param SaveFilesRequest $request FileSaveRequest * @return AgentResponse SaveResponse */ 
    public function saveFiles(string $sandboxId, SaveFilesRequest $request): AgentResponse; /** * execute Task. * * @param string $sandboxId Sandbox ID * @param ScriptTaskRequest $request TaskRequest * @return AgentResponse execute Response */ 
    public function executeScriptTask(string $sandboxId, ScriptTaskRequest $request): AgentResponse; /** * Rollbackspecified checkpoint. * * @param string $sandboxId Sandbox ID * @param check pointRollbackRequest $request checkpointRollbackRequest * @return AgentResponse RollbackResponse */ 
    public function rollbackcheck point(string $sandboxId, check pointRollbackRequest $request): AgentResponse; /** * StartRollbackspecified checkpointmark Statusdelete . * * @param string $sandboxId Sandbox ID * @param check pointRollbackStartRequest $request checkpointRollbackStartRequest * @return AgentResponse RollbackResponse */ 
    public function rollbackcheck pointStart(string $sandboxId, check pointRollbackStartRequest $request): AgentResponse; /** * SubmitRollbackspecified checkpointdelete recalled status Message. * * @param string $sandboxId Sandbox ID * @param check pointRollbackCommitRequest $request checkpointRollbackSubmitRequest * @return AgentResponse RollbackResponse */ 
    public function rollbackcheck pointCommit(string $sandboxId, check pointRollbackCommitRequest $request): AgentResponse; /** * UndoRollbacksandbox checkpointrecalled status MessageResumeas NormalStatus. * * @param string $sandboxId Sandbox ID * @param check pointRollbackUndoRequest $request checkpointRollbackUndoRequest * @return AgentResponse RollbackResponse */ 
    public function rollbackcheck pointUndo(string $sandboxId, check pointRollbackUndoRequest $request): AgentResponse; /** * check Rollbackspecified checkpointRow. * * @param string $sandboxId Sandbox ID * @param check pointRollbackcheck Request $request checkpointRollbackcheck Request * @return AgentResponse check Response */ 
    public function rollbackcheck pointcheck (string $sandboxId, check pointRollbackcheck Request $request): AgentResponse; 
}
 

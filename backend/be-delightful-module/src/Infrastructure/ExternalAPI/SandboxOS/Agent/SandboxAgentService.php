<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AbstractSandboxOS;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\ChatMessageRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\CheckpointRollbackCheckRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\CheckpointRollbackCommitRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\CheckpointRollbackRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\CheckpointRollbackStartRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\CheckpointRollbackUndoRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\InitAgentRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\InterruptRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\SaveFilesRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request\ScriptTaskRequest;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Response\AgentResponse;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Constants\SandboxEndpoints;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayInterface;
use Exception;
use Hyperf\Logger\LoggerFactory;

/**
 * Sandbox Agent service implementation
 * Communicates with Agent via Gateway proxy
 */
class SandboxAgentService extends AbstractSandboxOS implements SandboxAgentInterface
{
    public function __construct(
        LoggerFactory $loggerFactory,
        private readonly SandboxGatewayInterface $gateway
    ) {
        parent::__construct($loggerFactory);
    }

    /**
     * Initialize an Agent.
     */
    public function initAgent(string $sandboxId, InitAgentRequest $request): AgentResponse
    {
        $this->logger->info('[Sandbox][Agent] Initializing agent', [
            'sandbox_id' => $sandboxId,
            'user_id' => $request->getUserId(),
            'task_mode' => $request->getTaskMode(),
            'agent_mode' => $request->getAgentMode(),
            'model_id' => $request->getModelId(),
        ]);

        try {
            // Forward to Agent API via Gateway
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                SandboxEndpoints::AGENT_MESSAGES_CHAT,
                $request->toArray()
            );

            $response = AgentResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('[Sandbox][Agent] Agent initialized successfully', [
                    'sandbox_id' => $sandboxId,
                    'agent_id' => $response->getAgentId(),
                ]);
            } else {
                $this->logger->error('[Sandbox][Agent] Failed to initialize agent', [
                    'sandbox_id' => $sandboxId,
                    'code' => $response->getCode(),
                    'message' => $response->getMessage(),
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('[Sandbox][Agent] Unexpected error when initializing agent', [
                'sandbox_id' => $sandboxId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return AgentResponse::fromApiResponse([
                'code' => 2000,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    /**
     * Send a chat message to the Agent.
     */
    public function sendChatMessage(string $sandboxId, ChatMessageRequest $request): AgentResponse
    {
        $this->logger->debug('[Sandbox][Agent] Sending chat message to agent', [
            'sandbox_id' => $sandboxId,
            'user_id' => $request->getUserId(),
            'task_id' => $request->getTaskId(),
            'prompt_length' => strlen($request->getPrompt()),
            'model_id' => $request->getModelId(),
        ]);

        try {
            // Forward to Agent API via Gateway
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                SandboxEndpoints::AGENT_MESSAGES_CHAT,
                $request->toArray()
            );

            $response = AgentResponse::fromGatewayResult($result);

            $this->logger->debug('[Sandbox][Agent] Chat message sent to agent', [
                'sandbox_id' => $sandboxId,
                'success' => $response->isSuccess(),
                'message_id' => $response->getMessageId(),
                'has_response' => $response->hasResponseMessage(),
            ]);

            return $response;
        } catch (Exception $e) {
            $this->logger->error('[Sandbox][Agent] Unexpected error when sending chat message', [
                'sandbox_id' => $sandboxId,
                'error' => $e->getMessage(),
            ]);

            return AgentResponse::fromApiResponse([
                'code' => 2000,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    /**
     * Send an interrupt message to the Agent.
     */
    public function sendInterruptMessage(string $sandboxId, InterruptRequest $request): AgentResponse
    {
        $this->logger->info('[Sandbox][Agent] Sending interrupt message to agent', [
            'sandbox_id' => $sandboxId,
            'user_id' => $request->getUserId(),
            'task_id' => $request->getTaskId(),
            'remark' => $request->getRemark(),
        ]);

        try {
            // Forward to Agent API via Gateway
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                SandboxEndpoints::AGENT_MESSAGES_CHAT,
                $request->toArray()
            );

            $response = AgentResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('[Sandbox][Agent] Interrupt message sent successfully', [
                    'sandbox_id' => $sandboxId,
                    'user_id' => $request->getUserId(),
                    'task_id' => $request->getTaskId(),
                ]);
            } else {
                $this->logger->error('[Sandbox][Agent] Failed to send interrupt message', [
                    'sandbox_id' => $sandboxId,
                    'code' => $response->getCode(),
                    'message' => $response->getMessage(),
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('[Sandbox][Agent] Unexpected error when sending interrupt message', [
                'sandbox_id' => $sandboxId,
                'error' => $e->getMessage(),
            ]);

            return AgentResponse::fromApiResponse([
                'code' => 2000,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    /**
     * Get workspace status.
     */
    public function getWorkspaceStatus(string $sandboxId): AgentResponse
    {
        $this->logger->debug('[Sandbox][Agent] Getting workspace status', [
            'sandbox_id' => $sandboxId,
        ]);

        try {
            // Forward to Agent API via Gateway
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'GET',
                'api/v1/workspace/status'
            );

            $response = AgentResponse::fromGatewayResult($result);

            $this->logger->debug('[Sandbox][Agent] Workspace status retrieved', [
                'sandbox_id' => $sandboxId,
                'success' => $response->isSuccess(),
                'status' => $response->getDataValue('status'),
            ]);

            return $response;
        } catch (Exception $e) {
            $this->logger->error('[Sandbox][Agent] Unexpected error when getting workspace status', [
                'sandbox_id' => $sandboxId,
                'error' => $e->getMessage(),
            ]);

            return AgentResponse::fromApiResponse([
                'code' => 2000,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    /**
     * Save files to the sandbox.
     */
    public function saveFiles(string $sandboxId, SaveFilesRequest $request): AgentResponse
    {
        $this->logger->info('[Sandbox][Agent] Saving files to sandbox', [
            'sandbox_id' => $sandboxId,
            'file_count' => $request->getFileCount(),
        ]);

        try {
            // Forward to sandbox file edit API via Gateway
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                'api/v1/files/save',
                $request->toArray()
            );

            $response = AgentResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('[Sandbox][Agent] Files saved successfully', [
                    'sandbox_id' => $sandboxId,
                    'file_count' => $request->getFileCount(),
                ]);
            } else {
                $this->logger->error('[Sandbox][Agent] Failed to save files', [
                    'sandbox_id' => $sandboxId,
                    'code' => $response->getCode(),
                    'message' => $response->getMessage(),
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('[Sandbox][Agent] Unexpected error when saving files', [
                'sandbox_id' => $sandboxId,
                'error' => $e->getMessage(),
            ]);

            return AgentResponse::fromApiResponse([
                'code' => 2000,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    public function executeScriptTask(string $sandboxId, ScriptTaskRequest $request): AgentResponse
    {
        $this->logger->info('[Sandbox][Agent] Executing script task', [
            'sandbox_id' => $sandboxId,
            'task_id' => $request->getTaskId(),
        ]);

        try {
            // Forward to sandbox file edit API via Gateway
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                '/api/task/script-task',
                $request->toArray()
            );

            $response = AgentResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('[Sandbox][Agent] Files saved successfully', [
                    'sandbox_id' => $sandboxId,
                    'script_name' => $request->getScriptName(),
                    'arguments' => $request->getArguments(),
                ]);
            } else {
                $this->logger->error('[Sandbox][Agent] Failed to save files', [
                    'sandbox_id' => $sandboxId,
                    'code' => $response->getCode(),
                    'message' => $response->getMessage(),
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('[Sandbox][Agent] Unexpected error when executing script task', [
                'sandbox_id' => $sandboxId,
                'error' => $e->getMessage(),
            ]);

            return AgentResponse::fromApiResponse([
                'code' => 2000,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    /**
     * Roll back to the specified checkpoint.
     */
    public function rollbackCheckpoint(string $sandboxId, CheckpointRollbackRequest $request): AgentResponse
    {
        $this->logger->info('[Sandbox][Agent] Rolling back to checkpoint', [
            'sandbox_id' => $sandboxId,
            'target_message_id' => $request->getTargetMessageId(),
        ]);

        try {
            // Forward to sandbox checkpoint rollback API via Gateway
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                'api/checkpoints/rollback',
                $request->toArray()
            );

            $response = AgentResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('[Sandbox][Agent] Checkpoint rollback successful', [
                    'sandbox_id' => $sandboxId,
                    'target_message_id' => $request->getTargetMessageId(),
                    'message' => $response->getMessage(),
                ]);
            } else {
                $this->logger->error('[Sandbox][Agent] Failed to rollback checkpoint', [
                    'sandbox_id' => $sandboxId,
                    'target_message_id' => $request->getTargetMessageId(),
                    'code' => $response->getCode(),
                    'message' => $response->getMessage(),
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('[Sandbox][Agent] Unexpected error when rolling back checkpoint', [
                'sandbox_id' => $sandboxId,
                'target_message_id' => $request->getTargetMessageId(),
                'error' => $e->getMessage(),
            ]);

            return AgentResponse::fromApiResponse([
                'code' => 2000,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    /**
     * Start rollback to the specified checkpoint (mark state without deletion).
     */
    public function rollbackCheckpointStart(string $sandboxId, CheckpointRollbackStartRequest $request): AgentResponse
    {
        $this->logger->info('[Sandbox][Agent] Starting checkpoint rollback', [
            'sandbox_id' => $sandboxId,
            'target_message_id' => $request->getTargetMessageId(),
        ]);

        try {
            // Forward to sandbox checkpoint rollback start API via Gateway
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                'api/checkpoints/rollback/start',
                $request->toArray()
            );

            $response = AgentResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('[Sandbox][Agent] Checkpoint rollback start successful', [
                    'sandbox_id' => $sandboxId,
                    'target_message_id' => $request->getTargetMessageId(),
                    'message' => $response->getMessage(),
                ]);
            } else {
                $this->logger->error('[Sandbox][Agent] Failed to start checkpoint rollback', [
                    'sandbox_id' => $sandboxId,
                    'target_message_id' => $request->getTargetMessageId(),
                    'code' => $response->getCode(),
                    'message' => $response->getMessage(),
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('[Sandbox][Agent] Unexpected error when starting checkpoint rollback', [
                'sandbox_id' => $sandboxId,
                'target_message_id' => $request->getTargetMessageId(),
                'error' => $e->getMessage(),
            ]);

            return AgentResponse::fromApiResponse([
                'code' => 2000,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    /**
     * Commit rollback to the specified checkpoint (physically delete withdrawn messages).
     */
    public function rollbackCheckpointCommit(string $sandboxId, CheckpointRollbackCommitRequest $request): AgentResponse
    {
        $this->logger->info('[Sandbox][Agent] Committing checkpoint rollback', [
            'sandbox_id' => $sandboxId,
        ]);

        try {
            // Forward to sandbox checkpoint rollback commit API via Gateway
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                'api/checkpoints/rollback/commit',
                $request->toArray()
            );

            $response = AgentResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('[Sandbox][Agent] Checkpoint rollback commit successful', [
                    'sandbox_id' => $sandboxId,
                    'message' => $response->getMessage(),
                ]);
            } else {
                $this->logger->error('[Sandbox][Agent] Failed to commit checkpoint rollback', [
                    'sandbox_id' => $sandboxId,
                    'code' => $response->getCode(),
                    'message' => $response->getMessage(),
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('[Sandbox][Agent] Unexpected error when committing checkpoint rollback', [
                'sandbox_id' => $sandboxId,
                'error' => $e->getMessage(),
            ]);

            return AgentResponse::fromApiResponse([
                'code' => 2000,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    /**
     * Undo sandbox checkpoint rollback (restore withdrawn messages to normal state).
     */
    public function rollbackCheckpointUndo(string $sandboxId, CheckpointRollbackUndoRequest $request): AgentResponse
    {
        $this->logger->info('[Sandbox][Agent] Undoing checkpoint rollback', [
            'sandbox_id' => $sandboxId,
        ]);

        try {
            // Forward to sandbox checkpoint rollback undo API via Gateway
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                'api/checkpoints/rollback/undo',
                $request->toArray()
            );

            $response = AgentResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('[Sandbox][Agent] Checkpoint rollback undo successful', [
                    'sandbox_id' => $sandboxId,
                    'message' => $response->getMessage(),
                ]);
            } else {
                $this->logger->error('[Sandbox][Agent] Failed to undo checkpoint rollback', [
                    'sandbox_id' => $sandboxId,
                    'code' => $response->getCode(),
                    'message' => $response->getMessage(),
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('[Sandbox][Agent] Unexpected error when undoing checkpoint rollback', [
                'sandbox_id' => $sandboxId,
                'error' => $e->getMessage(),
            ]);

            return AgentResponse::fromApiResponse([
                'code' => 2000,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }

    /**
     * Check feasibility of rolling back to the specified checkpoint.
     */
    public function rollbackCheckpointCheck(string $sandboxId, CheckpointRollbackCheckRequest $request): AgentResponse
    {
        $this->logger->info('[Sandbox][Agent] Checking checkpoint rollback feasibility', [
            'sandbox_id' => $sandboxId,
            'target_message_id' => $request->getTargetMessageId(),
        ]);

        try {
            // Forward to sandbox checkpoint rollback check API via Gateway
            $result = $this->gateway->proxySandboxRequest(
                $sandboxId,
                'POST',
                'api/checkpoints/rollback/check',
                $request->toArray()
            );

            $response = AgentResponse::fromGatewayResult($result);

            if ($response->isSuccess()) {
                $this->logger->info('[Sandbox][Agent] Checkpoint rollback check successful', [
                    'sandbox_id' => $sandboxId,
                    'target_message_id' => $request->getTargetMessageId(),
                    'can_rollback' => $response->getDataValue('can_rollback'),
                ]);
            } else {
                $this->logger->error('[Sandbox][Agent] Failed to check checkpoint rollback', [
                    'sandbox_id' => $sandboxId,
                    'target_message_id' => $request->getTargetMessageId(),
                    'code' => $response->getCode(),
                    'message' => $response->getMessage(),
                ]);
            }

            return $response;
        } catch (Exception $e) {
            $this->logger->error('[Sandbox][Agent] Unexpected error when checking checkpoint rollback', [
                'sandbox_id' => $sandboxId,
                'target_message_id' => $request->getTargetMessageId(),
                'error' => $e->getMessage(),
            ]);

            return AgentResponse::fromApiResponse([
                'code' => 2000,
                'message' => 'Unexpected error: ' . $e->getMessage(),
                'data' => [],
            ]);
        }
    }
}

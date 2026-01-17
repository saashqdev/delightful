<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent;

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

/**
 * Sandbox Agent interface
 * Defines Agent communication functions, forwarded via the Gateway.
 */
interface SandboxAgentInterface
{
    /**
     * Initialize an Agent.
     *
     * @param string $sandboxId Sandbox ID
     * @param InitAgentRequest $request Init request
     * @return AgentResponse Init result
     */
    public function initAgent(string $sandboxId, InitAgentRequest $request): AgentResponse;

    /**
     * Send a chat message to the Agent.
     *
     * @param string $sandboxId Sandbox ID
     * @param ChatMessageRequest $request Chat message request
     * @return AgentResponse Agent response
     */
    public function sendChatMessage(string $sandboxId, ChatMessageRequest $request): AgentResponse;

    /**
     * Send an interrupt message to the Agent.
     *
     * @param string $sandboxId Sandbox ID
     * @param InterruptRequest $request Interrupt request
     * @return AgentResponse Interrupt response
     */
    public function sendInterruptMessage(string $sandboxId, InterruptRequest $request): AgentResponse;

    /**
     * Get workspace status.
     *
     * @param string $sandboxId Sandbox ID
     * @return AgentResponse Workspace status response
     */
    public function getWorkspaceStatus(string $sandboxId): AgentResponse;

    /**
     * Save files to the sandbox.
     *
     * @param string $sandboxId Sandbox ID
     * @param SaveFilesRequest $request File save request
     * @return AgentResponse Save response
     */
    public function saveFiles(string $sandboxId, SaveFilesRequest $request): AgentResponse;

    /**
     * Execute a script task.
     *
     * @param string $sandboxId Sandbox ID
     * @param ScriptTaskRequest $request Script task request
     * @return AgentResponse Execution response
     */
    public function executeScriptTask(string $sandboxId, ScriptTaskRequest $request): AgentResponse;

    /**
     * Roll back to a specific checkpoint.
     *
     * @param string $sandboxId Sandbox ID
     * @param CheckpointRollbackRequest $request Checkpoint rollback request
     * @return AgentResponse Rollback response
     */
    public function rollbackCheckpoint(string $sandboxId, CheckpointRollbackRequest $request): AgentResponse;

    /**
     * Start rollback to a specific checkpoint (mark state, do not delete).
     *
     * @param string $sandboxId Sandbox ID
     * @param CheckpointRollbackStartRequest $request Checkpoint rollback start request
     * @return AgentResponse Rollback response
     */
    public function rollbackCheckpointStart(string $sandboxId, CheckpointRollbackStartRequest $request): AgentResponse;

    /**
     * Commit rollback to a specific checkpoint (physically delete messages in withdrawn state).
     *
     * @param string $sandboxId Sandbox ID
     * @param CheckpointRollbackCommitRequest $request Checkpoint rollback commit request
     * @return AgentResponse Rollback response
     */
    public function rollbackCheckpointCommit(string $sandboxId, CheckpointRollbackCommitRequest $request): AgentResponse;

    /**
     * Undo checkpoint rollback (restore withdrawn messages to normal state).
     *
     * @param string $sandboxId Sandbox ID
     * @param CheckpointRollbackUndoRequest $request Checkpoint rollback undo request
     * @return AgentResponse Rollback response
     */
    public function rollbackCheckpointUndo(string $sandboxId, CheckpointRollbackUndoRequest $request): AgentResponse;

    /**
     * Check feasibility of rollback to a specific checkpoint.
     *
     * @param string $sandboxId Sandbox ID
     * @param CheckpointRollbackCheckRequest $request Checkpoint rollback check request
     * @return AgentResponse Check response
     */
    public function checkRollbackCheckpoint(string $sandboxId, CheckpointRollbackCheckRequest $request): AgentResponse;
}
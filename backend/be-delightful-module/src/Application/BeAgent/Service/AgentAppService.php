<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Infrastructure\Core\Exception\BusinessException;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\ChatInstruction;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskContext;
use Delightful\BeDelightful\Domain\BeAgent\Service\AgentDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Constant\WorkspaceStatus;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Response\AgentResponse;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Exception\SandboxOperationException;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\BatchStatusResult;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\SandboxStatusResult;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Agent application service.
 * Responsible for coordinating Agent domain service calls, following DDD principles.
 */
readonly class AgentAppService
{
    private LoggerInterface $logger;

    public function __construct(
        private LoggerFactory $loggerFactory,
        private readonly AgentDomainService $agentDomainService,
        private readonly TopicDomainService $topicDomainService,
        private readonly TaskFileDomainService $taskFileDomainService,
        private readonly ProjectDomainService $projectDomainService,
    ) {
        $this->logger = $this->loggerFactory->get('sandbox');
    }

    /**
     * Get sandbox status.
     *
     * @param string $sandboxId Sandbox ID
     * @return SandboxStatusResult Sandbox status result
     */
    public function getSandboxStatus(string $sandboxId): SandboxStatusResult
    {
        return $this->agentDomainService->getSandboxStatus($sandboxId);
    }

    /**
     * Get batch sandbox status.
     *
     * @param array $sandboxIds Array of sandbox IDs
     * @return BatchStatusResult Batch sandbox status result
     */
    public function getBatchSandboxStatus(array $sandboxIds): BatchStatusResult
    {
        return $this->agentDomainService->getBatchSandboxStatus($sandboxIds);
    }

    /**
     * Send message to agent.
     */
    public function sendChatMessage(DataIsolation $dataIsolation, TaskContext $taskContext): void
    {
        $this->agentDomainService->sendChatMessage($dataIsolation, $taskContext);
    }

    /**
     * Send interrupt message to Agent.
     *
     * @param DataIsolation $dataIsolation Data isolation context
     * @param string $sandboxId Sandbox ID
     * @param string $taskId Task ID
     * @param string $reason Interrupt reason
     * @return AgentResponse Interrupt response
     */
    public function sendInterruptMessage(
        DataIsolation $dataIsolation,
        string $sandboxId,
        string $taskId,
        string $reason,
    ): AgentResponse {
        return $this->agentDomainService->sendInterruptMessage($dataIsolation, $sandboxId, $taskId, $reason);
    }

    /**
     * Get workspace status.
     *
     * @param string $sandboxId Sandbox ID
     * @return AgentResponse Workspace status response
     */
    public function getWorkspaceStatus(string $sandboxId): AgentResponse
    {
        return $this->agentDomainService->getWorkspaceStatus($sandboxId);
    }

    /**
     * Wait for workspace to be ready.
     * Poll workspace status until initialization is complete, failed, or timeout.
     *
     * @param string $sandboxId Sandbox ID
     * @param int $timeoutSeconds Timeout in seconds, default 10 minutes
     * @param int $intervalSeconds Polling interval in seconds, default 2 seconds
     */
    public function waitForWorkspaceReady(string $sandboxId, int $timeoutSeconds = 600, int $intervalSeconds = 2): void
    {
        $this->logger->info('[Sandbox][App] Waiting for workspace to be ready', [
            'sandbox_id' => $sandboxId,
            'timeout_seconds' => $timeoutSeconds,
            'interval_seconds' => $intervalSeconds,
        ]);

        $startTime = time();
        $endTime = $startTime + $timeoutSeconds;

        while (time() < $endTime) {
            try {
                $response = $this->getWorkspaceStatus($sandboxId);
                $status = $response->getDataValue('status');

                $this->logger->debug('[Sandbox][App] Workspace status check', [
                    'sandbox_id' => $sandboxId,
                    'status' => $status,
                    'status_description' => WorkspaceStatus::getDescription($status),
                    'elapsed_seconds' => time() - $startTime,
                ]);

                // Exit when status is ready
                if (WorkspaceStatus::isReady($status)) {
                    $this->logger->info('[Sandbox][App] Workspace is ready', [
                        'sandbox_id' => $sandboxId,
                        'elapsed_seconds' => time() - $startTime,
                    ]);
                    return;
                }

                // Throw exception when status is error
                if (WorkspaceStatus::isError($status)) {
                    $this->logger->error('[Sandbox][App] Workspace initialization failed', [
                        'sandbox_id' => $sandboxId,
                        'status' => $status,
                        'status_description' => WorkspaceStatus::getDescription($status),
                        'elapsed_seconds' => time() - $startTime,
                    ]);
                    throw new SandboxOperationException('Wait for workspace ready', 'Workspace initialization failed with status: ' . WorkspaceStatus::getDescription($status), 3001);
                }

                // Wait for next poll
                sleep($intervalSeconds);
            } catch (SandboxOperationException $e) {
                // Rethrow sandbox operation exception
                throw $e;
            } catch (Throwable $e) {
                $this->logger->error('[Sandbox][App] Error while checking workspace status', [
                    'sandbox_id' => $sandboxId,
                    'error' => $e->getMessage(),
                    'elapsed_seconds' => time() - $startTime,
                ]);
                throw new SandboxOperationException('Wait for workspace ready', 'Error checking workspace status: ' . $e->getMessage(), 3002);
            }
        }

        // Timeout
        $this->logger->error('[Sandbox][App] Workspace ready timeout', [
            'sandbox_id' => $sandboxId,
            'timeout_seconds' => $timeoutSeconds,
        ]);
        throw new SandboxOperationException('Wait for workspace ready', 'Workspace ready timeout after ' . $timeoutSeconds . ' seconds', 3003);
    }

    /**
     * Ensure sandbox is initialized and workspace is in ready state.
     *
     * @param DataIsolation $dataIsolation Data isolation context
     * @param int $topicId Topic ID
     * @return string Sandbox ID
     * @throws BusinessException When initialization fails
     */
    public function ensureSandboxInitialized(DataIsolation $dataIsolation, int $topicId): string
    {
        $this->logger->info('[Sandbox][App] Ensuring sandbox is initialized', [
            'topic_id' => $topicId,
        ]);

        // Get topic information
        $topicEntity = $this->topicDomainService->getTopicById($topicId);
        if (is_null($topicEntity)) {
            throw new BusinessException('Topic not found for ID: ' . $topicId);
        }

        $sandboxId = $topicEntity->getSandboxId();

        // Check workspace status
        try {
            $response = $this->getWorkspaceStatus($sandboxId);
            $status = $response->getDataValue('status');

            // If workspace is already ready, return directly
            if (WorkspaceStatus::isReady($status)) {
                $this->logger->info('[Sandbox][App] Workspace already ready', [
                    'sandbox_id' => $sandboxId,
                    'workspace_status' => $status,
                ]);
                return $sandboxId;
            }

            // Workspace not ready, need to reinitialize
            $this->logger->info('[Sandbox][App] Workspace not ready, will reinitialize', [
                'sandbox_id' => $sandboxId,
                'workspace_status' => $status,
            ]);
        } catch (SandboxOperationException $e) {
            // Workspace status check failed, need to reinitialize
            $this->logger->warning('[Sandbox][App] Failed to check workspace status, will reinitialize', [
                'sandbox_id' => $sandboxId,
                'error' => $e->getMessage(),
            ]);
        }

        // Create or reinitialize sandbox
        $sandboxId = $this->createAndInitializeSandbox($dataIsolation, $topicEntity);

        $this->logger->info('[Sandbox][App] Sandbox initialized successfully', [
            'sandbox_id' => $sandboxId,
            'topic_id' => $topicId,
        ]);

        return $sandboxId;
    }

    /**
     * Rollback to specified checkpoint.
     *
     * @param string $sandboxId Sandbox ID
     * @param string $targetMessageId Target message ID
     * @return AgentResponse Rollback response
     */
    public function rollbackCheckpoint(string $sandboxId, string $targetMessageId): AgentResponse
    {
        $this->logger->info('[Sandbox][App] Rollback checkpoint requested', [
            'sandbox_id' => $sandboxId,
            'target_message_id' => $targetMessageId,
        ]);

        // Execute sandbox rollback
        $response = $this->agentDomainService->rollbackCheckpoint($sandboxId, $targetMessageId);

        // Sandbox rollback failed, log and return early
        if (! $response->isSuccess()) {
            $this->logger->error('[Sandbox][App] Checkpoint rollback failed', [
                'sandbox_id' => $sandboxId,
                'target_message_id' => $targetMessageId,
                'code' => $response->getCode(),
                'message' => $response->getMessage(),
            ]);

            // Sandbox rollback failed, don't execute message rollback
            $this->logger->info('[Sandbox][App] Skipping message rollback due to sandbox rollback failure', [
                'sandbox_id' => $sandboxId,
                'target_message_id' => $targetMessageId,
            ]);

            return $response;
        }

        // Sandbox rollback successful, log and execute message rollback
        $this->logger->info('[Sandbox][App] Checkpoint rollback successful', [
            'sandbox_id' => $sandboxId,
            'target_message_id' => $targetMessageId,
            'sandbox_response' => $response->getMessage(),
        ]);

        // Execute message rollback
        $this->topicDomainService->rollbackMessages($targetMessageId);

        $this->logger->info('[Sandbox][App] Message rollback completed successfully', [
            'sandbox_id' => $sandboxId,
            'target_message_id' => $targetMessageId,
        ]);

        return $response;
    }

    /**
     * Start rollback to specified checkpoint (call sandbox gateway and mark message status).
     *
     * @param DataIsolation $dataIsolation Data isolation context
     * @param int $topicId Topic ID
     * @param string $targetMessageId Target message ID
     * @return string Operation result message
     */
    public function rollbackCheckpointStart(DataIsolation $dataIsolation, int $topicId, string $targetMessageId): string
    {
        $this->logger->info('[Sandbox][App] Rollback checkpoint start requested', [
            'topic_id' => $topicId,
            'target_message_id' => $targetMessageId,
        ]);

        // Validate topic exists and belongs to current user
        $topicEntity = $this->topicDomainService->getTopicById($topicId);
        if (is_null($topicEntity)) {
            throw new BusinessException('Topic not found for ID: ' . $topicId);
        }

        if ($topicEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            throw new BusinessException('Access denied for topic ID: ' . $topicId);
        }

        // Ensure sandbox is initialized and get sandbox ID
        $sandboxId = $this->ensureSandboxInitialized($dataIsolation, $topicId);

        // Call sandbox gateway to start rollback
        $sandboxResponse = $this->agentDomainService->rollbackCheckpointStart($sandboxId, $targetMessageId);

        if (! $sandboxResponse->isSuccess()) {
            $this->logger->error('[Sandbox][App] Sandbox rollback start failed', [
                'sandbox_id' => $sandboxId,
                'target_message_id' => $targetMessageId,
                'error' => $sandboxResponse->getMessage(),
            ]);
            throw new BusinessException('Sandbox rollback start failed: ' . $sandboxResponse->getMessage());
        }

        // After sandbox operation success, mark message status
        $this->topicDomainService->rollbackMessagesStart($targetMessageId);

        $this->logger->info('[Sandbox][App] Message rollback start completed successfully', [
            'topic_id' => $topicId,
            'target_message_id' => $targetMessageId,
            'sandbox_response' => $sandboxResponse->getMessage(),
        ]);

        return 'Sandbox and messages rollback started successfully';
    }

    /**
     * Commit rollback to specified checkpoint (call sandbox gateway and physically delete recalled messages).
     *
     * @param DataIsolation $dataIsolation Data isolation context
     * @param int $topicId Topic ID
     * @return string Operation result message
     */
    public function rollbackCheckpointCommit(DataIsolation $dataIsolation, int $topicId): string
    {
        $this->logger->info('[Sandbox][App] Rollback checkpoint commit requested', [
            'topic_id' => $topicId,
        ]);

        // Validate topic exists and belongs to current user
        $topicEntity = $this->topicDomainService->getTopicById($topicId);
        if (is_null($topicEntity)) {
            throw new BusinessException('Topic not found for ID: ' . $topicId);
        }

        if ($topicEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            throw new BusinessException('Access denied for topic ID: ' . $topicId);
        }

        // Ensure sandbox is initialized and get sandbox ID
        $sandboxId = $this->ensureSandboxInitialized($dataIsolation, $topicId);

        // Call sandbox gateway to commit rollback
        $sandboxResponse = $this->agentDomainService->rollbackCheckpointCommit($sandboxId);

        if (! $sandboxResponse->isSuccess()) {
            $this->logger->error('[Sandbox][App] Sandbox rollback commit failed', [
                'sandbox_id' => $sandboxId,
                'error' => $sandboxResponse->getMessage(),
            ]);
            throw new BusinessException('Sandbox rollback commit failed: ' . $sandboxResponse->getMessage());
        }

        // After sandbox operation success, physically delete recalled messages
        $this->topicDomainService->rollbackMessagesCommit($topicId, $dataIsolation->getCurrentUserId());

        $this->logger->info('[Sandbox][App] Message rollback commit completed successfully', [
            'topic_id' => $topicId,
            'sandbox_response' => $sandboxResponse->getMessage(),
        ]);

        return 'Sandbox and messages rollback committed successfully';
    }

    /**
     * Undo rollback operation (call sandbox gateway and restore recalled messages to normal status).
     *
     * @param DataIsolation $dataIsolation Data isolation context
     * @param int $topicId Topic ID
     * @return string Operation result message
     */
    public function rollbackCheckpointUndo(DataIsolation $dataIsolation, int $topicId): string
    {
        $this->logger->info('[Sandbox][App] Rollback checkpoint undo requested', [
            'topic_id' => $topicId,
            'user_id' => $dataIsolation->getCurrentUserId(),
        ]);

        // Validate topic exists and belongs to current user
        $topicEntity = $this->topicDomainService->getTopicById($topicId);
        if (is_null($topicEntity)) {
            $this->logger->error('[Sandbox][App] Topic not found for undo', [
                'topic_id' => $topicId,
                'user_id' => $dataIsolation->getCurrentUserId(),
            ]);
            throw new BusinessException('Topic not found for ID: ' . $topicId);
        }

        if ($topicEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            $this->logger->error('[Sandbox][App] Access denied for topic undo', [
                'topic_id' => $topicId,
                'topic_user_id' => $topicEntity->getUserId(),
                'current_user_id' => $dataIsolation->getCurrentUserId(),
            ]);
            throw new BusinessException('Access denied for topic ID: ' . $topicId);
        }

        // Ensure sandbox is initialized and get sandbox ID
        $sandboxId = $this->ensureSandboxInitialized($dataIsolation, $topicId);

        // Call sandbox gateway to undo rollback
        $sandboxResponse = $this->agentDomainService->rollbackCheckpointUndo($sandboxId);

        if (! $sandboxResponse->isSuccess()) {
            $this->logger->error('[Sandbox][App] Sandbox rollback undo failed', [
                'sandbox_id' => $sandboxId,
                'error' => $sandboxResponse->getMessage(),
            ]);
            throw new BusinessException('Sandbox rollback undo failed: ' . $sandboxResponse->getMessage());
        }

        // After sandbox operation success, undo message recall (restore to normal status)
        $this->topicDomainService->rollbackMessagesUndo($topicId, $dataIsolation->getCurrentUserId());

        $this->logger->info('[Sandbox][App] Message rollback undo completed successfully', [
            'topic_id' => $topicId,
            'user_id' => $dataIsolation->getCurrentUserId(),
            'sandbox_response' => $sandboxResponse->getMessage(),
        ]);

        return 'Sandbox and messages rollback undone successfully';
    }

    /**
     * Check feasibility of rollback to specified checkpoint.
     *
     * @param DataIsolation $dataIsolation Data isolation context
     * @param int $topicId Topic ID
     * @param string $targetMessageId Target message ID
     * @return AgentResponse Check result response
     */
    public function rollbackCheckpointCheck(DataIsolation $dataIsolation, int $topicId, string $targetMessageId): AgentResponse
    {
        $this->logger->info('[Sandbox][App] Rollback checkpoint check requested', [
            'topic_id' => $topicId,
            'target_message_id' => $targetMessageId,
        ]);

        // Validate topic exists and belongs to current user
        $topicEntity = $this->topicDomainService->getTopicById($topicId);
        if (is_null($topicEntity)) {
            $this->logger->error('[Sandbox][App] Topic not found for rollback check', [
                'topic_id' => $topicId,
                'user_id' => $dataIsolation->getCurrentUserId(),
            ]);
            throw new BusinessException('Topic not found for ID: ' . $topicId);
        }

        if ($topicEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            $this->logger->error('[Sandbox][App] Access denied for topic rollback check', [
                'topic_id' => $topicId,
                'topic_user_id' => $topicEntity->getUserId(),
                'current_user_id' => $dataIsolation->getCurrentUserId(),
            ]);
            throw new BusinessException('Access denied for topic ID: ' . $topicId);
        }

        // Ensure sandbox is initialized and get sandbox ID
        $sandboxId = $this->ensureSandboxInitialized($dataIsolation, $topicId);

        // Call domain service to check rollback feasibility
        $response = $this->agentDomainService->rollbackCheckpointCheck($sandboxId, $targetMessageId);

        // Log check results
        if ($response->isSuccess()) {
            $this->logger->info('[Sandbox][App] Checkpoint rollback check completed successfully', [
                'topic_id' => $topicId,
                'target_message_id' => $targetMessageId,
                'can_rollback' => $response->getDataValue('can_rollback'),
            ]);
        } else {
            $this->logger->warning('[Sandbox][App] Checkpoint rollback check failed', [
                'topic_id' => $topicId,
                'target_message_id' => $targetMessageId,
                'error' => $response->getMessage(),
            ]);
        }

        return $response;
    }

    /**
     * Upgrade sandbox image.
     *
     * @param DataIsolation $dataIsolation Data isolation context
     * @param string $messageId Message ID
     * @param string $contextType Context type, default is continue
     * @return AgentResponse Upgrade response result
     * @throws BusinessException When upgrade fails
     */
    public function upgradeSandbox(
        DataIsolation $dataIsolation,
        string $messageId,
        string $contextType = 'continue'
    ): AgentResponse {
        $this->logger->info('[Sandbox][App] Upgrading sandbox image', [
            'message_id' => $messageId,
            'context_type' => $contextType,
            'user_id' => $dataIsolation->getCurrentUserId(),
            'organization_code' => $dataIsolation->getCurrentOrganizationCode(),
        ]);

        try {
            // Call domain service to execute upgrade
            $response = $this->agentDomainService->upgradeSandbox($messageId, $contextType);

            $this->logger->info('[Sandbox][App] Sandbox upgrade completed successfully', [
                'message_id' => $messageId,
                'context_type' => $contextType,
                'user_id' => $dataIsolation->getCurrentUserId(),
            ]);

            return $response;
        } catch (SandboxOperationException $e) {
            $this->logger->error('[Sandbox][App] Sandbox upgrade failed', [
                'message_id' => $messageId,
                'context_type' => $contextType,
                'user_id' => $dataIsolation->getCurrentUserId(),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw new BusinessException('Sandbox upgrade failed: ' . $e->getMessage());
        } catch (Throwable $e) {
            $this->logger->error('[Sandbox][App] Unexpected error during sandbox upgrade', [
                'message_id' => $messageId,
                'context_type' => $contextType,
                'user_id' => $dataIsolation->getCurrentUserId(),
                'error' => $e->getMessage(),
            ]);

            throw new BusinessException('Unexpected error during sandbox upgrade: ' . $e->getMessage());
        }
    }

    /**
     * Create and initialize sandbox.
     *
     * @param DataIsolation $dataIsolation Data isolation context
     * @param TopicEntity $topicEntity Topic entity
     * @return string Sandbox ID
     */
    private function createAndInitializeSandbox(DataIsolation $dataIsolation, TopicEntity $topicEntity): string
    {
        // Get full work directory path
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($dataIsolation->getCurrentOrganizationCode());
        $fullWorkdir = WorkDirectoryUtil::getFullWorkdir($fullPrefix, $topicEntity->getWorkDir() ?? '');

        $sandboxId = $topicEntity->getSandboxId();

        // Create sandbox container
        $sandboxId = $this->agentDomainService->createSandbox(
            $dataIsolation,
            (string) $topicEntity->getProjectId(),
            $sandboxId,
            $fullWorkdir
        );

        // Create TaskEntity, fully utilize TopicEntity data
        $taskEntity = new TaskEntity();
        $taskEntity->setTopicId($topicEntity->getId());
        $taskEntity->setProjectId($topicEntity->getProjectId());
        $taskEntity->setWorkspaceId($topicEntity->getWorkspaceId());
        $taskEntity->setSandboxId($sandboxId);
        $taskEntity->setWorkDir($topicEntity->getWorkDir() ?? '');
        $taskEntity->setUserId($topicEntity->getUserId());
        $taskEntity->setTaskMode($topicEntity->getTaskMode());

        // If TopicEntity has current task ID, also set it to TaskEntity
        if ($topicEntity->getCurrentTaskId()) {
            $taskEntity->setId($topicEntity->getCurrentTaskId());
            $taskEntity->setTaskId((string) $topicEntity->getCurrentTaskId());
        }

        // Create TaskContext, fully utilize all related data from TopicEntity
        $taskContext = new TaskContext(
            task: $taskEntity,
            dataIsolation: $dataIsolation,
            chatConversationId: $topicEntity->getChatConversationId(),
            chatTopicId: $topicEntity->getChatTopicId(),
            agentUserId: $topicEntity->getCreatedUid() ?: $topicEntity->getUserId(), // Use creator ID or user ID
            sandboxId: $sandboxId,
            taskId: $topicEntity->getCurrentTaskId() ? (string) $topicEntity->getCurrentTaskId() : '',
            instruction: ChatInstruction::Normal,
            agentMode: $topicEntity->getTopicMode() ?: 'general',
            workspaceId: (string) $topicEntity->getWorkspaceId(),
        );

        $projectEntity = $this->projectDomainService->getProjectNotUserId($topicEntity->getProjectId());

        // Initialize Agent
        $this->agentDomainService->initializeAgent($dataIsolation, $taskContext, projectOrganizationCode: $projectEntity->getUserOrganizationCode());

        // Wait for workspace to be ready
        $this->waitForWorkspaceReady($sandboxId, 60, 2);

        return $sandboxId;
    }
}

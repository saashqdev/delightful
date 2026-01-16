<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Infrastructure\Core\Exception\BusinessException;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\ChatInstruction;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskContext;
use Delightful\BeDelightful\Domain\SuperAgent\Service\AgentDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Constant\WorkspaceStatus;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Response\AgentResponse;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Exception\SandboxOperationException;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\BatchStatusResult;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\SandboxStatusResult;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
/** * AgentApplyService * AgentServicecall FollowDDD. */ readonly

class AgentAppService 
{
 
    private LoggerInterface $logger; 
    public function __construct( 
    private LoggerFactory $loggerFactory, 
    private readonly AgentDomainService $agentDomainService, 
    private readonly TopicDomainService $topicDomainService, 
    private readonly TaskFileDomainService $taskFileDomainService, 
    private readonly ProjectDomainService $projectDomainService, ) 
{
 $this->logger = $this->loggerFactory->get('sandbox'); 
}
 /** * Get sandbox status * * @param string $sandboxId Sandbox ID * @return SandboxStatusResult Sandbox statusResult */ 
    public function getSandboxStatus(string $sandboxId): SandboxStatusResult 
{
 return $this->agentDomainService->getSandboxStatus($sandboxId); 
}
 /** * BatchGet sandbox status * * @param array $sandboxIds Sandbox IDArray * @return BatchStatusResult BatchSandbox statusResult */ 
    public function getBatchSandboxStatus(array $sandboxIds): BatchStatusResult 
{
 return $this->agentDomainService->getBatchSandboxStatus($sandboxIds); 
}
 /** * SendMessagegive agent. */ 
    public function sendChatMessage(DataIsolation $dataIsolation, TaskContext $taskContext): void 
{
 $this->agentDomainService->sendChatMessage($dataIsolation, $taskContext); 
}
 /** * SendInterruptMessagegive Agent. * * @param DataIsolation $dataIsolation DataContext * @param string $sandboxId Sandbox ID * @param string $taskId TaskID * @param string $reason Interrupt * @return AgentResponse InterruptResponse */ 
    public function sendInterruptMessage( DataIsolation $dataIsolation, string $sandboxId, string $taskId, string $reason, ): AgentResponse 
{
 return $this->agentDomainService->sendInterruptMessage($dataIsolation, $sandboxId, $taskId, $reason); 
}
 /** * Getworkspace Status. * * @param string $sandboxId Sandbox ID * @return AgentResponse workspace StatusResponse */ 
    public function getWorkspaceStatus(string $sandboxId): AgentResponse 
{
 return $this->agentDomainService->getWorkspaceStatus($sandboxId); 
}
 /** * Waitingworkspace . * workspace StatusInitializecomplete Failedor Timeout. * * @param string $sandboxId Sandbox ID * @param int $timeoutSeconds TimeoutTimeseconds Default10 * @param int $intervalSeconds Intervalseconds Default2seconds */ 
    public function waitForWorkspaceReady(string $sandboxId, int $timeoutSeconds = 600, int $intervalSeconds = 2): void 
{
 $this->logger->info('[Sandbox][App] Waiting for workspace to be ready', [ 'sandbox_id' => $sandboxId, 'timeout_seconds' => $timeoutSeconds, 'interval_seconds' => $intervalSeconds, ]); $startTime = time(); $endTime = $startTime + $timeoutSeconds; while (time() < $endTime) 
{
 try 
{
 $response = $this->getWorkspaceStatus($sandboxId); $status = $response->getDataValue('status'); $this->logger->debug('[Sandbox][App] Workspace status check', [ 'sandbox_id' => $sandboxId, 'status' => $status, 'status_description' => WorkspaceStatus::getDescription($status), 'elapsed_seconds' => time() - $startTime, ]); // Statusas Exit if (WorkspaceStatus::isReady($status)) 
{
 $this->logger->info('[Sandbox][App] Workspace is ready', [ 'sandbox_id' => $sandboxId, 'elapsed_seconds' => time() - $startTime, ]); return; 
}
 // Statusas ErrorThrowException if (WorkspaceStatus::isError($status)) 
{
 $this->logger->error('[Sandbox][App] Workspace initialization failed', [ 'sandbox_id' => $sandboxId, 'status' => $status, 'status_description' => WorkspaceStatus::getDescription($status), 'elapsed_seconds' => time() - $startTime, ]); throw new SandboxOperationException('Wait for workspace ready', 'Workspace initialization failed with status: ' . WorkspaceStatus::getDescription($status), 3001); 
}
 // Waitingnext poll sleep($intervalSeconds); 
}
 catch (SandboxOperationException $e) 
{
 // NewThrowsandbox Exception throw $e; 
}
 catch (Throwable $e) 
{
 $this->logger->error('[Sandbox][App] Error while checking workspace status', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), 'elapsed_seconds' => time() - $startTime, ]); throw new SandboxOperationException('Wait for workspace ready', 'Error checking workspace status: ' . $e->getMessage(), 3002); 
}
 
}
 // Timeout $this->logger->error('[Sandbox][App] Workspace ready timeout', [ 'sandbox_id' => $sandboxId, 'timeout_seconds' => $timeoutSeconds, ]); throw new SandboxOperationException('Wait for workspace ready', 'Workspace ready timeout after ' . $timeoutSeconds . ' seconds', 3003); 
}
 /** * Ensuresandbox Initializeand workspace readyStatus. * * @param DataIsolation $dataIsolation DataContext * @param int $topicId topic ID * @return string Sandbox ID * @throws BusinessException WhenInitialization failed */ 
    public function ensureSandboxInitialized(DataIsolation $dataIsolation, int $topicId): string 
{
 $this->logger->info('[Sandbox][App] Ensuring sandbox is initialized', [ 'topic_id' => $topicId, ]); // Gettopic info $topicEntity = $this->topicDomainService->getTopicById($topicId); if (is_null($topicEntity)) 
{
 throw new BusinessException('Topic not found for ID: ' . $topicId); 
}
 $sandboxId = $topicEntity->getSandboxId(); // check workspace Status try 
{
 $response = $this->getWorkspaceStatus($sandboxId); $status = $response->getDataValue('status'); // Ifworkspace already directly Return if (WorkspaceStatus::isReady($status)) 
{
 $this->logger->info('[Sandbox][App] Workspace already ready', [ 'sandbox_id' => $sandboxId, 'workspace_status' => $status, ]); return $sandboxId; 
}
 // workspace need NewInitialize $this->logger->info('[Sandbox][App] Workspace not ready, will reinitialize', [ 'sandbox_id' => $sandboxId, 'workspace_status' => $status, ]); 
}
 catch (SandboxOperationException $e) 
{
 // workspace Statuscheck Failedneed NewInitialize $this->logger->warning('[Sandbox][App] Failed to check workspace status, will reinitialize', [ 'sandbox_id' => $sandboxId, 'error' => $e->getMessage(), ]); 
}
 // Createor NewInitializesandbox $sandboxId = $this->createAndInitializeSandbox($dataIsolation, $topicEntity); $this->logger->info('[Sandbox][App] Sandbox initialized successfully', [ 'sandbox_id' => $sandboxId, 'topic_id' => $topicId, ]); return $sandboxId; 
}
 /** * Rollbackspecified checkpoint. * * @param string $sandboxId Sandbox ID * @param string $targetMessageId TargetMessageID * @return AgentResponse RollbackResponse */ 
    public function rollbackcheck point(string $sandboxId, string $targetMessageId): AgentResponse 
{
 $this->logger->info('[Sandbox][App] Rollback checkpoint requested', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $targetMessageId, ]); // execute sandbox Rollback $response = $this->agentDomainService->rollbackcheck point($sandboxId, $targetMessageId); // sandbox Rollback failedrecord LogReturn if (! $response->isSuccess()) 
{
 $this->logger->error('[Sandbox][App] check point rollback failed', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $targetMessageId, 'code' => $response->getCode(), 'message' => $response->getMessage(), ]); // sandbox Rollback failedexecute MessageRollback $this->logger->info('[Sandbox][App] Skipping message rollback due to sandbox rollback failure', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $targetMessageId, ]); return $response; 
}
 // sandbox RollbackSuccessrecord Logexecute MessageRollback $this->logger->info('[Sandbox][App] check point rollback successful', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $targetMessageId, 'sandbox_response' => $response->getMessage(), ]); // execute MessageRollback $this->topicDomainService->rollbackMessages($targetMessageId); $this->logger->info('[Sandbox][App] Message rollback completed successfully', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $targetMessageId, ]); return $response; 
}
 /** * StartRollbackspecified checkpointcall sandbox mark MessageStatus. * * @param DataIsolation $dataIsolation DataContext * @param int $topicId topic ID * @param string $targetMessageId TargetMessageID * @return string ResultMessage */ 
    public function rollbackcheck pointStart(DataIsolation $dataIsolation, int $topicId, string $targetMessageId): string 
{
 $this->logger->info('[Sandbox][App] Rollback checkpoint start requested', [ 'topic_id' => $topicId, 'target_message_id' => $targetMessageId, ]); // Validate topic Existand belongs to current user $topicEntity = $this->topicDomainService->getTopicById($topicId); if (is_null($topicEntity)) 
{
 throw new BusinessException('Topic not found for ID: ' . $topicId); 
}
 if ($topicEntity->getuser Id() !== $dataIsolation->getcurrent user Id()) 
{
 throw new BusinessException('Access denied for topic ID: ' . $topicId); 
}
 // Ensuresandbox InitializeGet sandbox ID $sandboxId = $this->ensureSandboxInitialized($dataIsolation, $topicId); // call sandbox StartRollback $sandboxResponse = $this->agentDomainService->rollbackcheck pointStart($sandboxId, $targetMessageId); if (! $sandboxResponse->isSuccess()) 
{
 $this->logger->error('[Sandbox][App] Sandbox rollback start failed', [ 'sandbox_id' => $sandboxId, 'target_message_id' => $targetMessageId, 'error' => $sandboxResponse->getMessage(), ]); throw new BusinessException('Sandbox rollback start failed: ' . $sandboxResponse->getMessage()); 
}
 // sandbox Successexecute MessageStatusmark $this->topicDomainService->rollbackMessagesStart($targetMessageId); $this->logger->info('[Sandbox][App] Message rollback start completed successfully', [ 'topic_id' => $topicId, 'target_message_id' => $targetMessageId, 'sandbox_response' => $sandboxResponse->getMessage(), ]); return 'Sandbox and messages rollback started successfully'; 
}
 /** * SubmitRollbackspecified checkpointcall sandbox delete recalled status Message. * * @param DataIsolation $dataIsolation DataContext * @param int $topicId topic ID * @return string ResultMessage */ 
    public function rollbackcheck pointCommit(DataIsolation $dataIsolation, int $topicId): string 
{
 $this->logger->info('[Sandbox][App] Rollback checkpoint commit requested', [ 'topic_id' => $topicId, ]); // Validate topic Existand belongs to current user $topicEntity = $this->topicDomainService->getTopicById($topicId); if (is_null($topicEntity)) 
{
 throw new BusinessException('Topic not found for ID: ' . $topicId); 
}
 if ($topicEntity->getuser Id() !== $dataIsolation->getcurrent user Id()) 
{
 throw new BusinessException('Access denied for topic ID: ' . $topicId); 
}
 // Ensuresandbox InitializeGet sandbox ID $sandboxId = $this->ensureSandboxInitialized($dataIsolation, $topicId); // call sandbox SubmitRollback $sandboxResponse = $this->agentDomainService->rollbackcheck pointCommit($sandboxId); if (! $sandboxResponse->isSuccess()) 
{
 $this->logger->error('[Sandbox][App] Sandbox rollback commit failed', [ 'sandbox_id' => $sandboxId, 'error' => $sandboxResponse->getMessage(), ]); throw new BusinessException('Sandbox rollback commit failed: ' . $sandboxResponse->getMessage()); 
}
 // sandbox Successexecute delete recalled status Message $this->topicDomainService->rollbackMessagesCommit($topicId, $dataIsolation->getcurrent user Id()); $this->logger->info('[Sandbox][App] Message rollback commit completed successfully', [ 'topic_id' => $topicId, 'sandbox_response' => $sandboxResponse->getMessage(), ]); return 'Sandbox and messages rollback committed successfully'; 
}
 /** * UndoRollbackcall sandbox recalled status MessageResumeas NormalStatus. * * @param DataIsolation $dataIsolation DataContext * @param int $topicId topic ID * @return string ResultMessage */ 
    public function rollbackcheck pointUndo(DataIsolation $dataIsolation, int $topicId): string 
{
 $this->logger->info('[Sandbox][App] Rollback checkpoint undo requested', [ 'topic_id' => $topicId, 'user_id' => $dataIsolation->getcurrent user Id(), ]); // Validate topic Existand belongs to current user $topicEntity = $this->topicDomainService->getTopicById($topicId); if (is_null($topicEntity)) 
{
 $this->logger->error('[Sandbox][App] Topic not found for undo', [ 'topic_id' => $topicId, 'user_id' => $dataIsolation->getcurrent user Id(), ]); throw new BusinessException('Topic not found for ID: ' . $topicId); 
}
 if ($topicEntity->getuser Id() !== $dataIsolation->getcurrent user Id()) 
{
 $this->logger->error('[Sandbox][App] Access denied for topic undo', [ 'topic_id' => $topicId, 'topic_user_id' => $topicEntity->getuser Id(), 'current_user_id' => $dataIsolation->getcurrent user Id(), ]); throw new BusinessException('Access denied for topic ID: ' . $topicId); 
}
 // Ensuresandbox InitializeGet sandbox ID $sandboxId = $this->ensureSandboxInitialized($dataIsolation, $topicId); // call sandbox UndoRollback $sandboxResponse = $this->agentDomainService->rollbackcheck pointUndo($sandboxId); if (! $sandboxResponse->isSuccess()) 
{
 $this->logger->error('[Sandbox][App] Sandbox rollback undo failed', [ 'sandbox_id' => $sandboxId, 'error' => $sandboxResponse->getMessage(), ]); throw new BusinessException('Sandbox rollback undo failed: ' . $sandboxResponse->getMessage()); 
}
 // sandbox Successexecute MessageUndoResumeas NormalStatus $this->topicDomainService->rollbackMessagesUndo($topicId, $dataIsolation->getcurrent user Id()); $this->logger->info('[Sandbox][App] Message rollback undo completed successfully', [ 'topic_id' => $topicId, 'user_id' => $dataIsolation->getcurrent user Id(), 'sandbox_response' => $sandboxResponse->getMessage(), ]); return 'Sandbox and messages rollback undone successfully'; 
}
 /** * check Rollbackspecified checkpointRow. * * @param DataIsolation $dataIsolation DataContext * @param int $topicId topic ID * @param string $targetMessageId TargetMessageID * @return AgentResponse check ResultResponse */ 
    public function rollbackcheck pointcheck (DataIsolation $dataIsolation, int $topicId, string $targetMessageId): AgentResponse 
{
 $this->logger->info('[Sandbox][App] Rollback checkpoint check requested', [ 'topic_id' => $topicId, 'target_message_id' => $targetMessageId, ]); // Validate topic Existand belongs to current user $topicEntity = $this->topicDomainService->getTopicById($topicId); if (is_null($topicEntity)) 
{
 $this->logger->error('[Sandbox][App] Topic not found for rollback check', [ 'topic_id' => $topicId, 'user_id' => $dataIsolation->getcurrent user Id(), ]); throw new BusinessException('Topic not found for ID: ' . $topicId); 
}
 if ($topicEntity->getuser Id() !== $dataIsolation->getcurrent user Id()) 
{
 $this->logger->error('[Sandbox][App] Access denied for topic rollback check', [ 'topic_id' => $topicId, 'topic_user_id' => $topicEntity->getuser Id(), 'current_user_id' => $dataIsolation->getcurrent user Id(), ]); throw new BusinessException('Access denied for topic ID: ' . $topicId); 
}
 // Ensuresandbox InitializeGet sandbox ID $sandboxId = $this->ensureSandboxInitialized($dataIsolation, $topicId); // call Servicecheck RollbackRow $response = $this->agentDomainService->rollbackcheck pointcheck ($sandboxId, $targetMessageId); // record check Result if ($response->isSuccess()) 
{
 $this->logger->info('[Sandbox][App] check point rollback check completed successfully', [ 'topic_id' => $topicId, 'target_message_id' => $targetMessageId, 'can_rollback' => $response->getDataValue('can_rollback'), ]); 
}
 else 
{
 $this->logger->warning('[Sandbox][App] check point rollback check failed', [ 'topic_id' => $topicId, 'target_message_id' => $targetMessageId, 'error' => $response->getMessage(), ]); 
}
 return $response; 
}
 /** * sandbox . * * @param DataIsolation $dataIsolation DataContext * @param string $messageId MessageID * @param string $contextType ContextTypeDefault tocontinue * @return AgentResponse ResponseResult * @throws BusinessException WhenUpgrade failedThrowException */ 
    public function upgradeSandbox( DataIsolation $dataIsolation, string $messageId, string $contextType = 'continue' ): AgentResponse 
{
 $this->logger->info('[Sandbox][App] Upgrading sandbox image', [ 'message_id' => $messageId, 'context_type' => $contextType, 'user_id' => $dataIsolation->getcurrent user Id(), 'organization_code' => $dataIsolation->getcurrent OrganizationCode(), ]); try 
{
 // call Serviceexecute $response = $this->agentDomainService->upgradeSandbox($messageId, $contextType); $this->logger->info('[Sandbox][App] Sandbox upgrade completed successfully', [ 'message_id' => $messageId, 'context_type' => $contextType, 'user_id' => $dataIsolation->getcurrent user Id(), ]); return $response; 
}
 catch (SandboxOperationException $e) 
{
 $this->logger->error('[Sandbox][App] Sandbox upgrade failed', [ 'message_id' => $messageId, 'context_type' => $contextType, 'user_id' => $dataIsolation->getcurrent user Id(), 'error' => $e->getMessage(), 'code' => $e->getCode(), ]); throw new BusinessException('Sandbox upgrade failed: ' . $e->getMessage()); 
}
 catch (Throwable $e) 
{
 $this->logger->error('[Sandbox][App] Unexpected error during sandbox upgrade', [ 'message_id' => $messageId, 'context_type' => $contextType, 'user_id' => $dataIsolation->getcurrent user Id(), 'error' => $e->getMessage(), ]); throw new BusinessException('Unexpected error during sandbox upgrade: ' . $e->getMessage()); 
}
 
}
 /** * CreateInitializesandbox . * * @param DataIsolation $dataIsolation DataContext * @param TopicEntity $topicEntity topic * @return string Sandbox ID */ 
    private function createAndInitializeSandbox(DataIsolation $dataIsolation, TopicEntity $topicEntity): string 
{
 // Getcomplete working directory Path $fullPrefix = $this->taskFileDomainService->getFullPrefix($dataIsolation->getcurrent OrganizationCode()); $fullWorkdir = WorkDirectoryUtil::getFullWorkdir($fullPrefix, $topicEntity->getWorkDir() ?? ''); $sandboxId = $topicEntity->getSandboxId(); // Createsandbox including er $sandboxId = $this->agentDomainService->createSandbox( $dataIsolation, (string) $topicEntity->getProjectId(), $sandboxId, $fullWorkdir ); // CreateTaskEntityTopicEntityData $taskEntity = new TaskEntity(); $taskEntity->setTopicId($topicEntity->getId()); $taskEntity->setProjectId($topicEntity->getProjectId()); $taskEntity->setWorkspaceId($topicEntity->getWorkspaceId()); $taskEntity->setSandboxId($sandboxId); $taskEntity->setWorkDir($topicEntity->getWorkDir() ?? ''); $taskEntity->setuser Id($topicEntity->getuser Id()); $taskEntity->setTaskMode($topicEntity->getTaskMode()); // IfTopicEntityHavecurrent TaskIDalso Set TaskEntity if ($topicEntity->getcurrent TaskId()) 
{
 $taskEntity->setId($topicEntity->getcurrent TaskId()); $taskEntity->setTaskId((string) $topicEntity->getcurrent TaskId()); 
}
 // CreateTaskContextTopicEntityAllrelated Data $taskContext = new TaskContext( task: $taskEntity, dataIsolation: $dataIsolation, chatConversationId: $topicEntity->getChatConversationId(), chatTopicId: $topicEntity->getChatTopicId(), agentuser Id: $topicEntity->getCreatedUid() ?: $topicEntity->getuser Id(), // Usingcreator IDor user ID sandboxId: $sandboxId, taskId: $topicEntity->getcurrent TaskId() ? (string) $topicEntity->getcurrent TaskId() : '', instruction: ChatInstruction::Normal, agentMode: $topicEntity->getTopicMode() ?: 'general', workspaceId: (string) $topicEntity->getWorkspaceId(), ); $projectEntity = $this->projectDomainService->getProjectNotuser Id($topicEntity->getProjectId()); // InitializeAgent $this->agentDomainService->initializeAgent($dataIsolation, $taskContext, projectOrganizationCode: $projectEntity->getuser OrganizationCode()); // Waitingworkspace $this->waitForWorkspaceReady($sandboxId, 60, 2); return $sandboxId; 
}
 
}
 

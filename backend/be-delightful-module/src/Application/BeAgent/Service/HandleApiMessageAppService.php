<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Domain\Contact\Entity\Magicuser Entity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\MagicDepartmentuser DomainService;
use App\Domain\Contact\Service\Magicuser DomainService;
use App\Domain\ModelGateway\Entity\ValueObject\AccessTokenType;
use App\Domain\ModelGateway\Service\AccessTokenDomainService;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\EventException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Dtyq\AsyncEvent\AsyncEventUtil;
use Delightful\BeDelightful\Application\SuperAgent\DTO\TaskMessageDTO;
use Delightful\BeDelightful\Application\SuperAgent\DTO\user MessageDTO;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\ChatInstruction;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\CreationSource;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskContext;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Event\RunTaskBeforeEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Service\AgentDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TopicDomainService;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Odin\Message\Role;
use Psr\Log\LoggerInterface;
use Throwable;
use function Hyperf\Translation\trans;
/** * Handle user Message Application Service * Responsible for handling the complete business process of users sending messages to agents. */

class HandleApiMessageAppService extends AbstractAppService 
{
 
    protected LoggerInterface $logger; 
    public function __construct( 
    private readonly TopicDomainService $topicDomainService, 
    private readonly TaskDomainService $taskDomainService, 
    private readonly MagicDepartmentuser DomainService $departmentuser DomainService, 
    private readonly TopicTaskAppService $topicTaskAppService, 
    private readonly Fileprocess AppService $fileprocess AppService, 
    private readonly AgentDomainService $agentDomainService, 
    private readonly AccessTokenDomainService $accessTokenDomainService, 
    private readonly Magicuser DomainService $userDomainService, 
    private readonly TaskFileDomainService $taskFileDomainService, 
    private readonly ProjectDomainService $projectDomainService, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(get_class($this)); 
}
 /* * user send message to agent */ 
    public function handleApiMessage(DataIsolation $dataIsolation, user MessageDTO $userMessageDTO) 
{
 $topicId = 0; $taskId = ''; try 
{
 // Get topic information $topicEntity = $this->topicDomainService->getTopicByChatTopicId($dataIsolation, $userMessageDTO->getChatTopicId()); if (is_null($topicEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 $topicId = $topicEntity->getId(); // check message before task starts $this->beforeHandleChatMessage($dataIsolation, $userMessageDTO->getInstruction(), $topicEntity, $userMessageDTO->getLanguage(), $userMessageDTO->getModelId()); // Get task mode from DTO, fallback to topic's task mode if empty $taskMode = $userMessageDTO->getTaskMode(); if ($taskMode === '') 
{
 $taskMode = $topicEntity->getTaskMode(); 
}
 $data = [ 'user_id' => $dataIsolation->getcurrent user Id(), 'workspace_id' => $topicEntity->getWorkspaceId(), 'project_id' => $topicEntity->getProjectId(), 'topic_id' => $topicId, 'task_id' => '', // Initially empty, this is agent's task id 'task_mode' => $taskMode, 'sandbox_id' => $topicEntity->getSandboxId(), // current task prioritizes reusing previous topic's sandbox id 'prompt' => $userMessageDTO->getPrompt(), 'attachments' => $userMessageDTO->getAttachments(), 'mentions' => $userMessageDTO->getMentions(), 'task_status' => TaskStatus::WAITING->value, 'work_dir' => $topicEntity->getWorkDir() ?? '', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), ]; $taskEntity = TaskEntity::fromArray($data); // Initialize task $taskEntity = $this->taskDomainService->initTopicTask( dataIsolation: $dataIsolation, topicEntity: $topicEntity, taskEntity: $taskEntity ); $taskId = (string) $taskEntity->getId(); // Save user information $this->saveuser Message($dataIsolation, $taskEntity, $userMessageDTO); // check if this is the first task for the topic // If topic source is COPY, it's not the first task $isFirstTask = (empty($topicEntity->getcurrent TaskId()) || empty($topicEntity->getSandboxId())) && CreationSource::fromValue($topicEntity->getSource()) !== CreationSource::COPY; // Send message to agent $taskContext = new TaskContext( task: $taskEntity, dataIsolation: $dataIsolation, chatConversationId: $userMessageDTO->getChatConversationId(), chatTopicId: $userMessageDTO->getChatTopicId(), agentuser Id: $userMessageDTO->getAgentuser Id(), sandboxId: $topicEntity->getSandboxId(), taskId: (string) $taskEntity->getId(), instruction: ChatInstruction::FollowUp, agentMode: $userMessageDTO->getTopicMode(), isFirstTask: $isFirstTask, ); $sandboxID = $this->createAndSendMessageToAgent($dataIsolation, $taskContext); $taskEntity->setSandboxId($sandboxID); // Update task status $this->topicTaskAppService->updateTaskStatus( dataIsolation: $dataIsolation, task: $taskEntity, status: TaskStatus::RUNNING ); 
}
 catch (EventException $e) 
{
 $this->logger->error(sprintf( 'Initialize task, event processing failed: %s', $e->getMessage() )); // Send error message directly to client // $this->clientMessageAppService->sendErrorMessageToClient( // topicId: $topicId, // taskId: $taskId, // chatTopicId: $userMessageDTO->getChatTopicId(), // chatConversationId: $userMessageDTO->getChatConversationId(), // errorMessage: $e->getMessage() // ); throw new BusinessException('Initialize task, event processing failed', 500); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'handleChatMessage Error: %s, user : %s file: %s line: %s trace: %s', $e->getMessage(), $dataIsolation->getcurrent user Id(), $e->getFile(), $e->getLine(), $e->getTraceAsString() )); // Send error message directly to client // $this->clientMessageAppService->sendErrorMessageToClient( // topicId: $topicId, // taskId: $taskId, // chatTopicId: $userMessageDTO->getChatTopicId(), // chatConversationId: $userMessageDTO->getChatConversationId(), // errorMessage: trans('agent.initialize_error') // ); throw new BusinessException('Initialize task failed', 500); 
}
 
}
 
    public function handleAgentTask(DataIsolation $dataIsolation, user MessageDTO $userMessageDTO): array 
{
 $topicId = 0; $taskId = ''; try 
{
 // Get topic information $topicEntity = $this->topicDomainService->getTopicByChatTopicId($dataIsolation, $userMessageDTO->getChatTopicId()); if (is_null($topicEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 $topicId = $topicEntity->getId(); // check message before task starts $this->beforeHandleChatMessage($dataIsolation, $userMessageDTO->getInstruction(), $topicEntity, $userMessageDTO->getLanguage(), $userMessageDTO->getModelId()); // Get task mode from DTO, fallback to topic's task mode if empty $taskMode = $userMessageDTO->getTaskMode(); if ($taskMode === '') 
{
 $taskMode = $topicEntity->getTaskMode(); 
}
 $data = [ 'user_id' => $dataIsolation->getcurrent user Id(), 'workspace_id' => $topicEntity->getWorkspaceId(), 'project_id' => $topicEntity->getProjectId(), 'topic_id' => $topicId, 'task_id' => '', // Initially empty, this is agent's task id 'task_mode' => $taskMode, 'sandbox_id' => $topicEntity->getSandboxId(), // current task prioritizes reusing previous topic's sandbox id 'prompt' => $userMessageDTO->getPrompt(), 'attachments' => $userMessageDTO->getAttachments(), 'mentions' => $userMessageDTO->getMentions(), 'task_status' => TaskStatus::WAITING->value, 'work_dir' => $topicEntity->getWorkDir() ?? '', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), ]; $taskEntity = TaskEntity::fromArray($data); // Initialize task $taskEntity = $this->taskDomainService->initTopicTask( dataIsolation: $dataIsolation, topicEntity: $topicEntity, taskEntity: $taskEntity ); $taskId = (string) $taskEntity->getId(); // Save user information $this->saveuser Message($dataIsolation, $taskEntity, $userMessageDTO); // check if this is the first task for the topic // If topic source is COPY, it's not the first task $isFirstTask = (empty($topicEntity->getcurrent TaskId()) || empty($topicEntity->getSandboxId())) && CreationSource::fromValue($topicEntity->getSource()) !== CreationSource::COPY; // Send message to agent $taskContext = new TaskContext( task: $taskEntity, dataIsolation: $dataIsolation, chatConversationId: $userMessageDTO->getChatConversationId(), chatTopicId: $userMessageDTO->getChatTopicId(), agentuser Id: $userMessageDTO->getAgentuser Id(), sandboxId: $topicEntity->getSandboxId(), taskId: (string) $taskEntity->getId(), instruction: ChatInstruction::FollowUp, agentMode: $userMessageDTO->getTopicMode(), isFirstTask: $isFirstTask, ); $sandboxID = $this->createAndSendMessageToAgent($dataIsolation, $taskContext); $taskEntity->setSandboxId($sandboxID); // Update task status $this->topicTaskAppService->updateTaskStatus( dataIsolation: $dataIsolation, task: $taskEntity, status: TaskStatus::RUNNING ); return ['sandbox_id' => $sandboxID, 'task_id' => $taskId]; 
}
 catch (EventException $e) 
{
 $this->logger->error(sprintf( 'Initialize task, event processing failed: %s', $e->getMessage() )); // Send error message directly to client // $this->clientMessageAppService->sendErrorMessageToClient( // topicId: $topicId, // taskId: $taskId, // chatTopicId: $userMessageDTO->getChatTopicId(), // chatConversationId: $userMessageDTO->getChatConversationId(), // errorMessage: $e->getMessage() // ); throw new BusinessException('Initialize task, event processing failed', 500); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'handleChatMessage Error: %s, user : %s file: %s line: %s trace: %s', $e->getMessage(), $dataIsolation->getcurrent user Id(), $e->getFile(), $e->getLine(), $e->getTraceAsString() )); // Send error message directly to client // $this->clientMessageAppService->sendErrorMessageToClient( // topicId: $topicId, // taskId: $taskId, // chatTopicId: $userMessageDTO->getChatTopicId(), // chatConversationId: $userMessageDTO->getChatConversationId(), // errorMessage: trans('agent.initialize_error') // ); throw new BusinessException('Initialize task failed', 500); 
}
 
}
 
    public function getuser Authorization(string $apiKey, string $uid = ''): Magicuser Entity 
{
 $accessToken = $this->accessTokenDomainService->getByAccessToken($apiKey); if (empty($accessToken)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::ACCESS_TOKEN_NOT_FOUND, 'Access token not found'); 
}
 if (empty($uid)) 
{
 if ($accessToken->getType() === AccessTokenType::Application->value) 
{
 $uid = $accessToken->getcreator (); 
}
 else 
{
 $uid = $accessToken->getRelationId(); 
}
 
}
 return $this->userDomainService->getByuser Id($uid); 
}
 /** * Pre-task detection. */ 
    private function beforeHandleChatMessage(DataIsolation $dataIsolation, ChatInstruction $instruction, TopicEntity $topicEntity, string $language, string $modelId = ''): void 
{
 // get the current task run count $currentTaskRunCount = $this->pulluser TopicStatus($dataIsolation); $taskRound = $this->taskDomainService->getTaskNumByTopicId($topicEntity->getId()); // get department ids $departmentIds = []; $departmentuser Entities = $this->departmentuser DomainService->getDepartmentuser sByuser Ids([$dataIsolation->getcurrent user Id()], $dataIsolation); foreach ($departmentuser Entities as $departmentuser Entity) 
{
 $departmentIds[] = $departmentuser Entity->getDepartmentId(); 
}
 AsyncEventUtil::dispatch(new RunTaskBeforeEvent($dataIsolation->getcurrent OrganizationCode(), $dataIsolation->getcurrent user Id(), $topicEntity->getId(), $taskRound, $currentTaskRunCount, $departmentIds, $language, $modelId)); $this->logger->info(sprintf('Dispatched task start event, topic id: %s, round: %d, currentTaskRunCount: %d (after real status check)', $topicEntity->getId(), $taskRound, $currentTaskRunCount)); 
}
 /** * Update topics and tasks by pulling sandbox status. */ 
    private function pulluser TopicStatus(DataIsolation $dataIsolation): int 
{
 // Get user's running tasks $topicEntities = $this->topicDomainService->getuser RunningTopics($dataIsolation); // Get sandbox IDs $sandboxIds = []; foreach ($topicEntities as $topicEntityItem) 
{
 $sandboxId = $topicEntityItem->getSandboxId(); if ($sandboxId === '') 
{
 continue; 
}
 $sandboxIds[] = $sandboxId; 
}
 // Batch query status $result = $this->agentDomainService->getBatchSandboxStatus($sandboxIds); // Get running sandbox IDs from remote result $runningSandboxIds = $result->getRunningSandboxIds(); // Find sandbox IDs that are not running (including missing ones) $updateSandboxIds = array_diff($sandboxIds, $runningSandboxIds); // Update topic status $this->topicDomainService->updateTopicStatusBySandboxIds($updateSandboxIds, TaskStatus::Suspended); // Update task status $this->taskDomainService->updateTaskStatusBySandboxIds($updateSandboxIds, TaskStatus::Suspended, 'Synchronize sandbox status'); $initialRunningCount = count($topicEntities); $suspendedCount = count($updateSandboxIds); // Number of tasks to suspend return $initialRunningCount - $suspendedCount; // Number of tasks actually running 
}
 /** * Initialize agent environment. */ 
    private function createAndSendMessageToAgent(DataIsolation $dataIsolation, TaskContext $taskContext): string 
{
 // Get projectEntity $projectEntity = $this->projectDomainService->getProjectNotuser Id($taskContext->getProjectId()); // Create sandbox container $fullPrefix = $this->taskFileDomainService->getFullPrefix($projectEntity->getuser OrganizationCode()); $fullWorkdir = WorkDirectoryUtil::getFullWorkdir($fullPrefix, $taskContext->getTask()->getWorkDir()); if (empty($taskContext->getSandboxId())) 
{
 $sandboxId = (string) $taskContext->getTopicId(); 
}
 else 
{
 $sandboxId = $taskContext->getSandboxId(); 
}
 $sandboxId = $this->agentDomainService->createSandbox($dataIsolation, (string) $taskContext->getProjectId(), $sandboxId, $fullWorkdir); $projectEntity = $this->projectDomainService->getProjectNotuser Id($taskContext->getTask()->getProjectId()); // Initialize agent $this->agentDomainService->initializeAgent($dataIsolation, $taskContext, projectOrganizationCode: $projectEntity->getuser OrganizationCode()); // Wait for workspace to be ready $this->agentDomainService->waitForWorkspaceReady($taskContext->getSandboxId()); // Send message to agent // $this->agentDomainService->sendChatMessage($dataIsolation, $taskContext); // Send message to agent return $sandboxId; 
}
 /** * Save user information and corresponding attachments. */ 
    private function saveuser Message(DataIsolation $dataIsolation, TaskEntity $taskEntity, user MessageDTO $userMessageDTO): void 
{
 // Convert mentions string to array if not null $mentionsArray = $userMessageDTO->getMentions() !== null ? json_decode($userMessageDTO->getMentions(), true) : null; // Convert attachments string to array if not null $attachmentsArray = $userMessageDTO->getAttachments() !== null ? json_decode($userMessageDTO->getAttachments(), true) : null; // Create TaskMessageDTO for user message $taskMessageDTO = new TaskMessageDTO( taskId: (string) $taskEntity->getId(), role: Role::user ->value, senderUid: $dataIsolation->getcurrent user Id(), receiverUid: $userMessageDTO->getAgentuser Id(), messageType: 'chat', content: $taskEntity->getPrompt(), status: null, steps: null, tool: null, topicId: $taskEntity->getTopicId(), event: '', attachments: $attachmentsArray, mentions: $mentionsArray, showInUi: true, messageId: null ); $taskMessageEntity = TaskMessageEntity::taskMessageDTOToTaskMessageEntity($taskMessageDTO); $this->taskDomainService->recordTaskMessage($taskMessageEntity); // process user uploaded attachments $attachmentsStr = $userMessageDTO->getAttachments(); $this->fileprocess AppService->processInitialAttachments($attachmentsStr, $taskEntity, $dataIsolation); 
}
 
}
 

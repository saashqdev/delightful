<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Application\Chat\Service\MagicChatMessageAppService;
use App\Application\Chat\Service\Magicuser info AppService;
use App\Application\File\Service\FileAppService;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\MagicSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Service\MagicChatFileDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\user Type;
use App\Domain\Contact\Service\Magicuser DomainService;
use App\Domain\ModelGateway\Service\AccessTokenDomainService;
use App\Domain\ModelGateway\Service\ApplicationDomainService;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\EventException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Dtyq\AsyncEvent\AsyncEventUtil;
use Delightful\BeDelightful\Application\SuperAgent\DTO\TaskMessageDTO;
use Delightful\BeDelightful\Application\SuperAgent\DTO\user MessageDTO;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\ChatInstruction;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\FileType;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MessageMetadata;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MessagePayload;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MessageType;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskContext;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\user info ValueObject;
use Delightful\BeDelightful\Domain\SuperAgent\Event\RunTaskAfterEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\RunTaskBeforeEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\RunTaskCallbackEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Service\MessageBuilderDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\WorkspaceDomainService;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\Config\WebSocketConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\SandboxResult;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\SandboxStruct;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\Volcengine\SandboxService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\WebSocket\WebSocketSession;
use Delightful\BeDelightful\Infrastructure\Utils\TaskStatusValidator;
use Delightful\BeDelightful\Infrastructure\Utils\tool process or;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\TopicTaskMessageDTO;
use Error;
use Exception;
use Hyperf\Codec\Json;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Parallel;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Odin\Message\Role;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class TaskAppService extends AbstractAppService 
{
 
    protected LoggerInterface $logger; /** * MessageBuildService */ 
    private MessageBuilderDomainService $messageBuilder; 
    public function __construct( 
    private readonly WorkspaceDomainService $workspaceDomainService, 
    private readonly TopicDomainService $topicDomainService, 
    private readonly TaskDomainService $taskDomainService, 
    private readonly MagicChatMessageAppService $chatMessageAppService, 
    private readonly Magicuser info AppService $userinfo AppService, 
    private readonly MagicChatFileDomainService $chatFileDomainService, 
    private readonly FileAppService $fileAppService, 
    private readonly SandboxService $sandboxService, 
    private readonly Fileprocess AppService $fileprocess AppService, 
    protected Magicuser DomainService $userDomainService, 
    protected TaskRepositoryInterface $taskRepository, 
    protected LockerInterface $locker, LoggerFactory $loggerFactory, 
    protected AccessTokenDomainService $accessTokenDomainService, 
    protected ApplicationDomainService $applicationDomainService, 
    protected ProjectDomainService $projectDomainService, ) 
{
 $this->messageBuilder = new MessageBuilderDomainService(); $this->logger = $loggerFactory->get(get_class($this)); 
}
 /** * InitializeTaskEstablish WebSocket connectionprocess Coroutine. */ 
    public function initAgentTask( DataIsolation $dataIsolation, string $agentuser Id, string $conversationId, string $chatTopicId, string $prompt, ?string $attachments = null, ChatInstruction $instruction = ChatInstruction::Normal, string $taskMode = '' ): string 
{
 $topicId = 0; $taskId = ''; try 
{
 $topicEntity = $this->topicDomainService->getTopicByChatTopicId($dataIsolation, $chatTopicId); if (is_null($topicEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 $topicId = $topicEntity->getId(); // check user TaskQuantityLimit $this->beforeInitTask($dataIsolation, $instruction, $topicEntity); // InitializeTask $userMessageDTO = new user MessageDTO( agentuser Id: $agentuser Id, chatConversationId: $conversationId, chatTopicId: $chatTopicId, topicId: $topicId, prompt: $prompt, attachments: $attachments, mentions: null, instruction: $instruction, taskMode: $taskMode ); // Get task mode from DTO, fallback to topic's task mode if empty $taskMode = $userMessageDTO->getTaskMode(); if ($taskMode === '') 
{
 $taskMode = $topicEntity->getTaskMode(); 
}
 $data = [ 'user_id' => $dataIsolation->getcurrent user Id(), 'workspace_id' => $topicEntity->getWorkspaceId(), 'project_id' => $topicEntity->getProjectId(), 'topic_id' => $topicId, 'task_id' => '', // Initially empty, this is agent's task id 'task_mode' => $taskMode, 'sandbox_id' => $topicEntity->getSandboxId(), // current task prioritizes reusing previous topic's sandbox id 'prompt' => $userMessageDTO->getPrompt(), 'attachments' => $userMessageDTO->getAttachments(), 'mentions' => $userMessageDTO->getMentions(), 'task_status' => TaskStatus::WAITING->value, 'work_dir' => $topicEntity->getWorkDir() ?? '', 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'), ]; $taskEntity = TaskEntity::fromArray($data); // Initialize task $taskEntity = $this->taskDomainService->initTopicTask( dataIsolation: $dataIsolation, topicEntity: $topicEntity, taskEntity: $taskEntity ); $taskEntity = $this->taskDomainService->initTopicTask($dataIsolation, $topicEntity, $taskEntity); $taskId = (string) $taskEntity->getId(); // InitializeContext $taskContext = new TaskContext( $taskEntity, $dataIsolation, $conversationId, $chatTopicId, $agentuser Id, $topicEntity->getSandboxId(), $taskId, $instruction, ); // Ifyes Interruptdirectly SendInterrupt if ($instruction == ChatInstruction::Interrupted) 
{
 $this->sendInternalMessageToSandbox($taskContext, $topicEntity); return $taskId; 
}
 // yes Pairinfo // process user Sendinfo // record user SendMessage $attachmentsArr = is_null($attachments) ? [] : json_decode($attachments, true); // Create TaskMessageDTO for user message $taskMessageDTO = new TaskMessageDTO( taskId: (string) $taskEntity->getId(), role: Role::user ->value, senderUid: $dataIsolation->getcurrent user Id(), receiverUid: $agentuser Id, messageType: 'chat', content: $prompt, status: null, steps: null, tool: null, topicId: $taskEntity->getTopicId(), event: '', attachments: $attachmentsArr, mentions: null, showInUi: true, messageId: null ); $taskMessageEntity = TaskMessageEntity::taskMessageDTOToTaskMessageEntity($taskMessageDTO); $this->taskDomainService->recordTaskMessage($taskMessageEntity); // process user Upload $this->fileprocess AppService->processInitialAttachments($attachments, $taskEntity, $dataIsolation); // Initializesandbox Environment // Don't havesandbox idyes Task $isFirstTaskMessage = empty($taskEntity->getSandboxId()); /** @var bool $isInitConfig */ [$isInitConfig, $sandboxId] = $this->initSandbox($taskEntity->getSandboxId()); if (empty($sandboxId)) 
{
 $this->updateTaskStatus( $taskEntity, $dataIsolation, $taskEntity->getTaskId(), TaskStatus::ERROR, 'Createsandbox Failed' ); throw new BusinessException('Createsandbox Failed', 500); 
}
 $this->logger->info(sprintf('Createsandbox Success: %s', $sandboxId)); $taskEntity->setSandboxId($sandboxId); // Set TaskStatusas Waiting $this->updateTaskStatus($taskEntity, $dataIsolation, $taskId, TaskStatus::WAITING); $taskContext->setSandboxId($sandboxId); // 5. Coroutineprocess WebSocket $requestId = CoContext::getOrSetRequestId(); Coroutine::create(function () use ($taskContext, $isInitConfig, $isFirstTaskMessage, $requestId) 
{
 try 
{
 CoContext::setRequestId($requestId);
$this->sendChatMessageToSandbox($taskContext, $isInitConfig, $isFirstTaskMessage); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'WebSocketprocess Exception: %s, TaskID: %s', $e->getMessage(), $taskContext->getTaskId() )); // UpdateTaskStatusas Error $this->updateTaskStatus( $taskContext->getTask(), $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::ERROR, $e->getMessage() ); 
}
 
}
); return $taskContext->getTaskId(); 
}
 catch (EventException $e) 
{
 $this->logger->error(sprintf( 'InitializeTask, Eventprocess ing failed: %s', $e->getMessage() )); // SendMessagegive Client $this->sendErrorMessageToClient($topicId, $taskId, $chatTopicId, $conversationId, $e->getMessage()); throw new BusinessException('InitializeTask, Eventprocess ing failed', 500); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'InitializeTaskFailed: %s', $e->getMessage() )); $text = 'System busy, please retry later'; if ($e->getCode() === GenericErrorCode::IllegalOperation->value) 
{
 $text = $e->getMessage(); 
}
 // SendMessagegive Client $this->sendErrorMessageToClient($topicId, (string) $taskId, $chatTopicId, $conversationId, $text); throw new BusinessException('InitializeTaskFailed', 500); 
}
 
}
 
    public function beforeInitTask(DataIsolation $dataIsolation, ChatInstruction $instruction, TopicEntity $topicEntity): void 
{
 if ($instruction == ChatInstruction::Interrupted) 
{
 return; 
}
 $topicEntities = $this->topicDomainService->getuser RunningTopics($dataIsolation); $currentTaskRunCount = count($topicEntities); // Original quantityAll assumed running if ($currentTaskRunCount > 0) 
{
 // Use coroutines to concurrently check real sandbox status $parallel = new Parallel(10); $requestId = CoContext::getOrSetRequestId(); foreach ($topicEntities as $index => $topicEntityItem) 
{
 $parallel->add(function () use ($topicEntityItem, $requestId) 
{
 CoContext::setRequestId($requestId);
// check real sandbox status and return 1 if not running (need to subtract) $realStatus = $this->updateTaskStatusFromSandbox($topicEntityItem); return $realStatus !== TaskStatus::RUNNING ? 1 : 0; 
}
, (string) $index); 
}
 try 
{
 $results = $parallel->wait(); // Subtract non-running topics from total count foreach ($results as $needSubtract) 
{
 $currentTaskRunCount -= $needSubtract; 
}
 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf('Failed to check real task status concurrently: %s', $e->getMessage())); // Fallback: use original count without real status check 
}
 
}
 $taskRound = $this->taskDomainService->getTaskNumByTopicId($topicEntity->getId());
AsyncEventUtil::dispatch(new RunTaskBeforeEvent($dataIsolation->getcurrent OrganizationCode(), $dataIsolation->getcurrent user Id(), $topicEntity->getId(), $taskRound, $currentTaskRunCount, [], '', '')); $this->logger->info(sprintf('Deliver TaskStartEvent, topic id: %s, round: %d, currentTaskRunCount: %d (after real status check)', $topicEntity->getId(), $taskRound, $currentTaskRunCount)); 
}
 
    public function updateTaskStatusFromSandbox(TopicEntity $topicEntity): TaskStatus 
{
 $this->logger->info(sprintf('Startcheck TaskStatus: topic_id=%s', $topicEntity->getId())); if (! $topicEntity->getSandboxId()) 
{
 return TaskStatus::WAITING; 
}
 // call SandboxServicegetStatusInterfaceGetincluding erStatus $result = $this->sandboxService->getStatus($topicEntity->getSandboxId()); // Ifsandbox Existand Statusas runningdirectly Return sandbox if ($result->getCode() === SandboxResult::Normal && $result->getSandboxData()->getStatus() === SandboxResult::SandboxRunnig) 
{
 $this->logger->info(sprintf('Sandbox statusNormal(running): sandboxId=%s', $topicEntity->getSandboxId())); return TaskStatus::RUNNING; 
}
 // record need CreateNewsandbox if ($result->getCode() === SandboxResult::NotFound) 
{
 $errMsg = 'Sandbox does not exist'; 
}
 elseif ($result->getCode() === SandboxResult::Normal && $result->getSandboxData()->getStatus() === 'exited') 
{
 $errMsg = 'Sandbox already exited'; 
}
 else 
{
 $errMsg = 'Sandbox exception'; 
}
 // Getcurrent Task $taskId = $topicEntity->getcurrent TaskId(); if ($taskId) 
{
 // UpdateTaskStatus $this->taskDomainService->updateTaskStatusByTaskId($taskId, TaskStatus::ERROR, $errMsg); 
}
 // Updatetopic Status $this->topicDomainService->updateTopicStatus($topicEntity->getId(), $taskId, TaskStatus::ERROR); // trigger complete Event AsyncEventUtil::dispatch(new RunTaskAfterEvent( $topicEntity->getuser OrganizationCode(), $topicEntity->getuser Id(), $topicEntity->getId(), $taskId, TaskStatus::ERROR->value, null )); $this->logger->info(sprintf('Endcheck TaskStatus: topic_id=%s, status=%s, error_msg=%s', $topicEntity->getId(), TaskStatus::ERROR->value, $errMsg)); return TaskStatus::ERROR; 
}
 /** * Sendterminate Taskinfo . * @throws Throwable */ 
    public function sendInternalMessageToSandbox(TaskContext $taskContext, TopicEntity $topicEntity, string $msg = ''): void 
{
 $text = empty($msg) ? 'Task has been terminated .' : $msg; // check sandbox whether Exist if (empty($topicEntity->getSandboxId())) 
{
 $this->logger->info('Sandbox id does not exist, directly update task status'); $this->updateTaskStatus($taskContext->getTask(), $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::Suspended, 'Sandbox id does not exist, directly update task status'); $this->sendErrorMessageToClient($topicEntity->getId(), (string) $taskContext->getTask()->getId(), $taskContext->getChatTopicId(), $taskContext->getChatConversationId(), $text); return; 
}
 // call Remotequery sandbox whether Exist $result = $this->sandboxService->checkSandboxExists($topicEntity->getSandboxId()); if ($result->getCode() == SandboxResult::NotFound || $result?->getSandboxData()?->getStatus() == SandboxResult::SandboxExited) 
{
 $this->logger->info('Sandbox does not exist or already exited, directly update task status'); $this->updateTaskStatus($taskContext->getTask(), $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::Suspended, 'Sandbox does not exist or already exited, directly update task status'); $this->sendErrorMessageToClient($topicEntity->getId(), (string) $taskContext->getTask()->getId(), $taskContext->getChatTopicId(), $taskContext->getChatConversationId(), $text); 
}
 // Ifsandbox ExistBuild websocket Row $websocketSession = $this->getSandboxWebsocketClient($taskContext); if (is_null($websocketSession)) 
{
 throw new BusinessException('Getsandbox websocketClientFailed', 500); 
}
 try 
{
 // Set $taskContext->getTask()->setPrompt('terminate Task'); $taskContext->setInstruction(ChatInstruction::Interrupted); $message = $this->messageBuilder->buildInterruptMessage( $taskContext->getcurrent user Id(), $taskContext->getTask()->getId(), $taskContext->getTask()->getTaskMode(), $msg ); $this->sendMessageToSandbox($websocketSession, $taskContext->getTask()->getId(), $message); 
}
 catch (Exception $e) 
{
 $this->logger->error(sprintf('terminate sandbox Taskinfo FailedErrorContentas : %s', $e->getMessage())); throw new BusinessException('Sendterminate TaskFailed', 500); 
}
 finally 
{
 $websocketSession->disconnect(); 
}
 
}
 /** * process topic TaskMessage. * * @param TopicTaskMessageDTO $messageDTO MessageDTO */ 
    public function handleTopicTaskMessage(TopicTaskMessageDTO $messageDTO): void 
{
 $this->logger->info(sprintf( 'Startprocess topic TaskMessagetask_id: %s , MessageContentas : %s', $messageDTO->getPayload()->getTaskId() ?? '', json_encode($messageDTO->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) )); // CreateDataObject $dataIsolation = DataIsolation::create( $messageDTO->getMetadata()->getOrganizationCode(), $messageDTO->getMetadata()->getuser Id() ); // process MessageEvent $topicEntity = $this->topicDomainService->getTopicByChatTopicId($dataIsolation, $messageDTO->getMetadata()->getChatTopicId()); if (is_null($topicEntity)) 
{
 throw new RuntimeException(sprintf('According tochattopic id: %s not found topic info ', $messageDTO->getMetadata()->getChatTopicId())); 
}
 // GetTaskinfo $taskEntity = $this->taskDomainService->getTaskById($topicEntity->getcurrent TaskId()); if (is_null($taskEntity)) 
{
 throw new RuntimeException(sprintf('According toTask id: %s not found Taskinfo ', $topicEntity->getcurrent TaskId() ?? '')); 
}
 // CreateTaskContext $taskContext = new TaskContext( task: $taskEntity, dataIsolation: $dataIsolation, chatConversationId: $messageDTO->getMetadata()?->getChatConversationId(), chatTopicId: $messageDTO->getMetadata()?->getChatTopicId(), agentuser Id: $messageDTO->getMetadata()?->getAgentuser Id(), sandboxId: $messageDTO->getMetadata()?->getSandboxId(), taskId: $messageDTO->getPayload()?->getTaskId(), instruction: ChatInstruction::tryFrom($messageDTO->getMetadata()?->getInstruction()) ?? ChatInstruction::Normal ); try 
{
 // process ReceiveMessage $this->handleReceivedMessage($messageDTO, $taskContext); // process TaskStatus $status = $messageDTO->getPayload()->getStatus(); $taskStatus = TaskStatus::tryFrom($status) ?? TaskStatus::ERROR; if (TaskStatus::tryFrom($status)) 
{
 $this->updateTaskStatus($taskContext->getTask(), $taskContext->getDataIsolation(), $taskContext->getTaskId(), $taskStatus); 
}
 AsyncEventUtil::dispatch(new RunTaskCallbackEvent( $taskContext->getcurrent OrganizationCode(), $taskContext->getcurrent user Id(), $taskContext->getTopicId(), $topicEntity->getTopicName(), $taskContext->getTask()->getId(), $messageDTO, $messageDTO->getMetadata()->getLanguage() )); $this->logger->info(sprintf( 'process topic TaskMessagecomplete message_id: %s', $messageDTO->getPayload()->getMessageId() )); 
}
 catch (EventException $e) 
{
 $this->logger->error(sprintf('Exception occurred during message event callback processing: %s', $e->getMessage())); $this->sendInternalMessageToSandbox($taskContext, $topicEntity, $e->getMessage()); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'process topic TaskMessageException: %s, message_id: %s', $e->getMessage(), $messageDTO->getPayload()->getMessageId() ), [ 'exception' => $e, 'message' => $messageDTO->toArray(), ]); 
}
 
}
 /** * GetLock. * * @param string $lockKey LockKey * @param string $lockowner LockHave * @param int $lockExpireSeconds LockExpiration timeseconds  * @return bool whether SuccessGetLock */ 
    public function acquireLock(string $lockKey, string $lockowner , int $lockExpireSeconds): bool 
{
 return $this->locker->mutexLock($lockKey, $lockowner , $lockExpireSeconds); 
}
 /** * ReleaseLock. * * @param string $lockKey LockKey * @param string $lockowner LockHave * @return bool whether SuccessReleaseLock */ 
    public function releaseLock(string $lockKey, string $lockowner ): bool 
{
 return $this->locker->release($lockKey, $lockowner ); 
}
 
    public function sendContinueMessageToSandbox(string $sandboxId, bool $isInit = false): bool 
{
 // Throughsandbox id $topicEntity = $this->topicDomainService->getTopicBySandboxId($sandboxId); if (is_null($topicEntity)) 
{
 throw new RuntimeException(sprintf('According tosandbox id: %s not found topic info ', $sandboxId)); 
}
 // CreateDataObject $dataIsolation = DataIsolation::create( $topicEntity->getuser OrganizationCode(), $topicEntity->getuser Id(), ); // GetTaskinfo $taskEntity = $this->taskDomainService->getTaskById($topicEntity->getcurrent TaskId()); if (is_null($taskEntity)) 
{
 throw new RuntimeException(sprintf('According toTask id: %s not found Taskinfo ', $topicEntity->getcurrent TaskId() ?? '')); 
}
 $taskContext = new TaskContext( task: $taskEntity, dataIsolation: $dataIsolation, chatConversationId: $topicEntity->getChatConversationId(), chatTopicId: $topicEntity->getChatTopicId(), agentuser Id: '', sandboxId: $sandboxId, taskId: (string) $taskEntity->getId(), instruction: ChatInstruction::FollowUp ); // Throughsandbox id query current $session = $this->getSandboxWebsocketClient($taskContext); if (is_null($session)) 
{
 throw new BusinessException('Getsandbox websocketClientFailed'); 
}
 // SendInitializeMessage if ($isInit) 
{
 $this->initTaskMessageToSandbox($session, $taskContext, false); 
}
 $chatMessage = $this->messageBuilder->buildContinueMessage( $dataIsolation->getcurrent user Id(), $taskContext->getChatConversationId(), ); $this->sendMessageToSandbox($session, $taskEntity->getId(), $chatMessage); return true; 
}
 /** * Summary of getTaskById. */ 
    public function getTaskById(int $taskId): ?TaskEntity 
{
 return $this->taskDomainService->getTaskById($taskId); 
}
 /** * Get websocket Client. */ 
    private function getSandboxWebsocketClient(TaskContext $taskContext): ?WebSocketSession 
{
 $config = new WebSocketConfig(); $task = $taskContext->getTask(); $sandboxId = $taskContext->getSandboxId(); $wsUrl = $this->sandboxService->getWebsocketUrl($sandboxId); // PrintJoinParameter $this->logger->info(sprintf( 'WebSocketJoinParameterURL: %sMaximumJoinTime: %dseconds ', $wsUrl, $config->getConnectTimeout() )); // Create WebSocket Session $session = new WebSocketSession( $config, $this->logger, $wsUrl, $task->getTaskId() ); try 
{
 $session->connect(); return $session; 
}
 catch (Exception $e) 
{
 $this->logger->error(sprintf( 'WebSocketConnection failedURL: %sError message: %s', $wsUrl, $e->getMessage() )); return null; 
}
 
}
 /** * process WebSocket */ 
    private function sendChatMessageToSandbox( TaskContext $taskContext, bool $isInitConfig, bool $isFirstTaskMessage, ): void 
{
 // Join $session = $this->getSandboxWebsocketClient($taskContext); if (is_null($session)) 
{
 throw new BusinessException('Getsandbox websocketClientFailed'); 
}
 try 
{
 // SendInitializeMessage if ($isInitConfig) 
{
 $this->initTaskMessageToSandbox($session, $taskContext, $isFirstTaskMessage); 
}
 // SendMessage $dataIsolation = $taskContext->getDataIsolation(); $task = $taskContext->getTask(); $attachmentUrls = $this->getAttachmentUrls($task->getAttachments(), $dataIsolation->getcurrent OrganizationCode()); $chatMessage = $this->messageBuilder->buildChatMessage( $dataIsolation->getcurrent user Id(), $task->getId(), $taskContext->getInstruction()->value, $task->getPrompt(), $attachmentUrls, $task->getTaskMode() ); $taskId = $this->sendMessageToSandbox($session, $task->getId(), $chatMessage); // InitializeSuccessUpdateStatusas running $taskContext->getTask()->setTaskId($taskId); // UpdateTaskas execute Status $this->updateTaskStatus($taskContext->getTask(), $taskContext->getDataIsolation(), $taskId, TaskStatus::RUNNING); // Configurationwhether need websocket loop $mode = config('super-magic.sandbox.pull_message_mode'); // websocket SchemaWaiting if ($mode === 'websocket') 
{
 $this->processMessageLoop($session, $taskContext); 
}
 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf('WebSocketSessionException: %s', $e->getMessage()), [ 'exception' => $e, 'task_id' => $taskContext->getTask()->getTaskId(), 'sandbox_id' => $taskContext->getTask()->getSandboxId(), ]); $this->updateTaskStatus($taskContext->getTask(), $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::ERROR, $e->getMessage()); $this->sendErrorMessageToClient($taskContext->getTask()->getTopicId(), (string) $taskContext->getTask()->getId(), $taskContext->getChatTopicId(), $taskContext->getChatConversationId(), 'RemoteServiceConnection failedplease later Retry'); throw $e; 
}
 finally 
{
 // EnsureJoinClose try 
{
 $session->disconnect(); $this->logger->info(sprintf( 'WebSocketSessionCloseSuccessTaskID: %s', $taskContext->getTaskId() )); 
}
 catch (Throwable $e) 
{
 $this->logger->warning(sprintf( 'CloseWebSocketConnection failedError: %sTaskID: %s', $e->getMessage(), $taskContext->getTaskId() )); 
}
 
}
 
}
 
    private function initTaskMessageToSandbox(WebSocketSession $session, TaskContext $taskContext, bool $isFirstTaskMessage): string 
{
 $dataIsolation = $taskContext->getDataIsolation(); $task = $taskContext->getTask(); // Get ProjectEntity $projectEntity = $this->projectDomainService->getProjectNotuser Id($task->getProjectId()); $uploadCredential = $this->getUploadCredential( $dataIsolation->getcurrent user Id(), $projectEntity->getuser OrganizationCode(), $task->getWorkDir() ); // Getuser info $userinfo = null; try 
{
 $userinfo Array = $this->userinfo AppService->getuser info ($dataIsolation->getcurrent user Id(), $dataIsolation); $userinfo = user info ValueObject::fromArray($userinfo Array); 
}
 catch (Throwable $e) 
{
 $this->logger->warning(sprintf( 'Getuser info Failed: %s, user ID: %s', $e->getMessage(), $dataIsolation->getcurrent user Id() )); 
}
 // UsingValueObjectSubstituteoriginal Array $messageMetadata = new MessageMetadata( agentuser Id: $taskContext->getAgentuser Id(), userId: $dataIsolation->getcurrent user Id(), organizationCode: $dataIsolation->getcurrent OrganizationCode(), chatConversationId: $taskContext->getChatConversationId(), chatTopicId: $taskContext->getChatTopicId(), instruction: $taskContext->getInstruction()->value, sandboxId: $taskContext->getSandboxId(), BeDelightfulTaskId: (string) $task->getId(), userinfo : $userinfo ); $topicEntity = $this->workspaceDomainService->getTopicById($task->getTopicId()); if (is_null($topicEntity)) 
{
 throw new RuntimeException('Initialize agent Found topic does not existtopic id: ' . $task->getTopicId()); 
}
 $sandboxConfig = ! empty($topicEntity->getSandboxConfig()) ? json_decode($topicEntity->getSandboxConfig(), true) : null; $initMessage = $this->messageBuilder->buildInitMessage( $dataIsolation->getcurrent user Id(), $uploadCredential, $messageMetadata, $isFirstTaskMessage, $sandboxConfig, $task->getTaskMode(), ); $this->logger->info(sprintf('[Send to Sandbox Init Message] task_id: %s, data: %s', $task->getTaskId(), json_encode($initMessage, JSON_UNESCAPED_UNICODE))); $session->send($initMessage); // WaitingInitializeResponse $message = $session->receive(900); if ($message === null) 
{
 throw new RuntimeException('Waiting agent InitializeResponseTimeout'); 
}
 $this->logger->info(sprintf( '[Receive from Sandbox Init Message] task_id: %s, data: %s', $task->getTaskId(), json_encode($message, JSON_UNESCAPED_UNICODE) )); // original MessageConvert toFormat $messageDTO = $this->convertWebSocketMessageToDTO($message); $payload = $messageDTO->getPayload(); // UsingNewFormatRowValidate if (! $payload->getType() || $payload->getType() !== MessageType::Init->value) 
{
 throw new RuntimeException('Received ExpectedInitializeResponseType'); 
}
 if ($payload->getStatus() === TaskStatus::ERROR->value) 
{
 throw new RuntimeException('agent Initialization failed: ' . json_encode($messageDTO->toArray(), JSON_UNESCAPED_UNICODE)); 
}
 return $payload->getTaskId(); 
}
 
    private function sendMessageToSandbox(WebSocketSession $session, int $taskId, array $chatMessage): string 
{
 $session->send($chatMessage); $this->logger->info(sprintf('[Send to Sandbox Chat Message] task_id: %d, data: %s', $taskId, json_encode($chatMessage, JSON_UNESCAPED_UNICODE))); // WaitingResponse $message = $session->receive(60); if ($message === null) 
{
 throw new RuntimeException('Waiting agent ResponseTimeout'); 
}
 $this->logger->info(sprintf( '[Receive from Sandbox Chat Message] task_id: %d, data: %s', $taskId, json_encode($message, JSON_UNESCAPED_UNICODE) )); // original MessageConvert toFormat $messageDTO = $this->convertWebSocketMessageToDTO($message); $payload = $messageDTO->getPayload(); // UsingNewFormatRowValidate if (! $payload->getType() || $payload->getType() !== MessageType::Chat->value) 
{
 throw new RuntimeException('Received ExpectedResponseType'); 
}
 if ($payload->getStatus() === TaskStatus::ERROR->value) 
{
 throw new RuntimeException('agent ResponseFailed: ' . json_encode($messageDTO->toArray(), JSON_UNESCAPED_UNICODE)); 
}
 return $payload->getTaskId(); 
}
 /** * process WebSocketMessage. */ 
    private function processMessageLoop( WebSocketSession $session, TaskContext $taskContext ): void 
{
 // AddMaximumprocess TimeLimitloop $startTime = time(); $config = new WebSocketConfig(); $taskTimeout = $config->getTaskTimeout(); $task = $taskContext->getTask(); while (true) 
{
 try 
{
 // check JoinStatus if (! $session->isConnected()) 
{
 $this->logger->warning('WebSocket connection disconnected, attempting to reconnect'); try 
{
 $session->connect(); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'NewConnection failed: %s, TaskID: %s', $e->getMessage(), $taskContext->getTaskId() )); $this->updateTaskStatus($task, $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::ERROR, $e->getMessage()); return; // Exitprocess 
}
 
}
 // ReceiveMessage $message = $session->receive($config->getReadTimeout()); if ($message === null) 
{
 // check Taskwhether already Timeout if (time() - $startTime > $taskTimeout) 
{
 $errMsg = sprintf( 'Taskprocess TimeoutTaskID: %sRowTime: %dseconds TaskTimeoutTime: %dseconds ', $taskContext->getTaskId(), time() - $startTime, $taskTimeout ); $this->logger->warning($errMsg); $this->updateTaskStatus($task, $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::ERROR, $errMsg); return; // Exitprocess 
}
 continue; 
}
 $this->logger->info('[Websocket Server] Received message from server: ' . json_encode($message, JSON_UNESCAPED_UNICODE)); // MessageConvert toFormat $messageDTO = $this->convertWebSocketMessageToDTO($message); // Set task id $taskContext->setTaskId($messageDTO->getPayload()->getTaskId() ?: $task->getTaskId()); // process MessageDeterminewhether need Continueprocess $shouldContinue = $this->handleReceivedMessage($messageDTO, $taskContext); if (! $shouldContinue) 
{
 $this->logger->info('[Taskalready complete ] task_id: ' . $taskContext->getTaskId()); break; // Ifyes terminate MessageExitloop 
}
 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'Task process MessageException: %s, TaskID: %s', $e->getMessage(), $taskContext->getTaskId() )); // Determinewhether yes ErrorIfyes terminate process if ($this->isFatalError($e)) 
{
 $this->updateTaskStatus($task, $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::ERROR, $e->getMessage()); return; // Exitprocess 
}
 // ErrorContinueprocess continue; 
}
 
}
 
}
 /** * process ReceiveMessage. * * @param TopicTaskMessageDTO $messageDTO Message * @param TaskContext $taskContext TaskContext * @return bool whether Continueprocess Message */ 
    private function handleReceivedMessage(TopicTaskMessageDTO $messageDTO, TaskContext $taskContext): bool 
{
 $payload = $messageDTO->getPayload(); // 1. Parse basic message info $messageType = $payload->getType() ?: 'unknown'; $content = $payload->getContent(); $status = $payload->getStatus() ?: TaskStatus::RUNNING->value; $tool = $payload->gettool () ?? []; $steps = $payload->getSteps() ?? []; $event = $payload->getEvent(); $attachments = $payload->getAttachments() ?? []; $projectArchive = $payload->getProjectArchive() ?? []; $showInUi = $payload->getShowInUi() ?? true; $messageId = $payload->getMessageId(); $correlationId = $payload->getCorrelationId(); // 2. process UnknownMessageType if (! MessageType::isValid($messageType)) 
{
 $this->logger->warning(sprintf( 'Received UnknownTypeMessageType: %sTaskID: %s', $messageType, $taskContext->getTaskId() )); return true; 
}
 // Ifyes sandbox Message if ($messageType == MessageType::ProjectArchive->value) 
{
 $this->workspaceDomainService->updateTopicSandboxConfig($taskContext->getDataIsolation(), $taskContext->getTopicId(), $projectArchive); return true; 
}
 // 3. process tool IfHave try 
{
 if (! empty($tool['attachments'])) 
{
 $this->processtool Attachments($tool, $taskContext); // Usingtool process orprocess FileIDMatch tool process or::processtool Attachments($tool); 
}
 // process Message $this->processMessageAttachments($attachments, $taskContext); // each Statusneed SomeSpecialprocess if ($status === TaskStatus::Suspended->value) 
{
 $this->pauseTaskSteps($steps); 
}
 elseif ($status === TaskStatus::FINISHED->value) 
{
 // Usingtool process orGenerate OutputContenttool $outputtool = tool process or::generateOutputContenttool ($attachments); if ($outputtool !== null) 
{
 $tool = $outputtool ; 
}
 
}
 // 4. record AIMessage $task = $taskContext->getTask(); // Create TaskMessageDTO for AI message $taskMessageDTO = new TaskMessageDTO( taskId: (string) $task->getId(), role: Role::Assistant->value, senderUid: $taskContext->getAgentuser Id(), receiverUid: $task->getuser Id(), messageType: $messageType, content: $content, status: $status, steps: $steps, tool: $tool, topicId: $task->getTopicId(), event: $event, attachments: $attachments, mentions: null, showInUi: $showInUi, messageId: $messageId, correlationId: $correlationId, ); $taskMessageEntity = TaskMessageEntity::taskMessageDTOToTaskMessageEntity($taskMessageDTO); $this->taskDomainService->recordTaskMessage($taskMessageEntity); // 5. SendMessageClient if ($showInUi) 
{
 $this->sendMessageToClient( topicId: $task->getTopicId(), taskId: (string) $task->getId(), chatTopicId: $taskContext->getChatTopicId(), chatConversationId: $taskContext->getChatConversationId(), content: $content, messageType: $messageType, status: $status, event: $event, steps: $steps, tool: $tool, attachments: $attachments, correlationId: $correlationId, ); 
}
 return true; 
}
 catch (Exception $e) 
{
 $this->logger->error(sprintf('Exception occurred during message processing: %s', $e->getMessage())); return true; 
}
 
}
 
    private function pauseTaskSteps(array &$steps): void 
{
 if (empty($steps)) 
{
 return; 
}
 // current Set as Pause foreach ($steps as $key => $step) 
{
 if ($step['status'] === TaskStatus::RUNNING->value) 
{
 // FrontendPauseStyle $steps[$key]['status'] = TaskStatus::Suspended->value;

}
 
}
 
}
 
    private function sendErrorMessageToClient(int $topicId, string $taskId, string $chatTopicId, string $chatConversationId, string $message): void 
{
 $this->sendMessageToClient( topicId: $topicId, taskId: $taskId, chatTopicId: $chatTopicId, chatConversationId: $chatConversationId, content: $message, messageType: MessageType::Error->value, status: TaskStatus::ERROR->value, event: '', steps: [], tool: [], attachments: [], correlationId: null, ); 
}
 /** * SendMessageClient. * * @param int $topicId topic ID * @param string $taskId TaskID * @param string $chatTopicId topic ID * @param string $chatConversationId SessionID * @param string $content MessageContent * @param string $messageType MessageType * @param string $status Status * @param string $event Event * @param null|array $steps * @param null|array $tool tool * @param null|array $attachments */ 
    private function sendMessageToClient( int $topicId, string $taskId, string $chatTopicId, string $chatConversationId, string $content, string $messageType, string $status, string $event, ?array $steps = null, ?array $tool = null, ?array $attachments = null, ?string $correlationId = null, ): void 
{
 // CreateMessageObject $message = $this->messageBuilder->createSuperAgentMessage( $topicId, $taskId, $content, $messageType, $status, $event, $steps, $tool, $attachments, $correlationId, ); // CreateColumn $seqDTO = new MagicSeqEntity(); $seqDTO->setObjectType(ConversationType::Ai); $seqDTO->setContent($message); $seqDTO->setSeqType(ChatMessageType::SuperAgentCard); $extra = new SeqExtra(); $extra->setTopicId($chatTopicId); $seqDTO->setExtra($extra); $seqDTO->setConversationId($chatConversationId); $this->logger->info('[Send to Client] Sendgive ClientMessage: ' . json_encode($message->toArray(), JSON_UNESCAPED_UNICODE)); // SendMessage $this->chatMessageAppService->aiSendMessage($seqDTO, (string) IdGenerator::getSnowId()); 
}
 /** * GetUpload */ 
    private function getUploadCredential(string $agentuser Id, string $organizationCode, string $workDir): array 
{
 /*$userAuthorization = new Magicuser Authorization(); $userAuthorization->setId($agentuser Id); $userAuthorization->setOrganizationCode($organizationCode); $userAuthorization->setuser Type(user Type::Ai);
*/ // sts token TemporarilySet 2 return $this->fileAppService->getStstemporary CredentialV2($organizationCode, 'private', $workDir, 3600 * 2); 
}
 /** * GetURL. */ 
    private function getAttachmentUrls(string $attachmentsJson, string $organizationCode): array 
{
 if (empty($attachmentsJson)) 
{
 return []; 
}
 $attachments = Json::decode($attachmentsJson); if (empty($attachments)) 
{
 return []; 
}
 $fileIds = []; foreach ($attachments as $attachment) 
{
 $fileId = $attachment['file_id'] ?? ''; if (empty($fileId)) 
{
 continue; 
}
 $fileIds[] = $fileId; 
}
 if (empty($fileIds)) 
{
 return []; 
}
 $files = []; $fileEntities = $this->chatFileDomainService->getFileEntitiesByFileIds($fileIds, null, null, true); foreach ($fileEntities as $fileEntity) 
{
 $files[] = [ 'file_extension' => $fileEntity->getFileExtension(), 'file_key' => $fileEntity->getFileKey(), 'file_size' => $fileEntity->getFileSize(), 'filename' => $fileEntity->getFileName(), 'display_filename' => $fileEntity->getFileName(), 'file_tag' => FileType::USER_UPLOAD->value, 'file_url' => $fileEntity->getExternalUrl(), ]; 
}
 return $files; 
}
 /** * Initializesandbox EnvironmentGet sandbox ID. * * @param string $sandboxId HaveSandbox IDIfHave * @return array [bool $needInit, string $sandboxId] FirstElementtable whether need InitializeConfigurationSecondElementas Sandbox ID */ 
    private function initSandbox(string $sandboxId): array 
{
 try 
{
 // IfHaveSandbox IDcheck Sandbox status if (! empty($sandboxId)) 
{
 // check sandbox whether Exist $result = $this->sandboxService->checkSandboxExists($sandboxId); // record Sandbox status $this->logger->info(sprintf( 'check Sandbox status: sandboxId=%s, code=%d, success=%s, data=%s', $sandboxId, $result->getCode(), $result->isSuccess() ? 'true' : 'false', json_encode($result->getSandboxData()->toArray(), JSON_UNESCAPED_UNICODE) )); // Ifsandbox Existand Statusas runningdirectly Return sandbox if ($result->getCode() === SandboxResult::Normal && $result->getSandboxData()->getStatus() === SandboxResult::SandboxRunnig) 
{
 $this->logger->info(sprintf('Sandbox status normal (running), directly using: sandboxId=%s', $sandboxId)); return [false, $sandboxId]; // not needed InitializeConfiguration 
}
 // record need CreateNewsandbox DebugUsingDon't haveIgnore if ($result->getCode() === SandboxResult::NotFound) 
{
 $this->logger->info(sprintf('Sandbox does not existCreateNewsandbox : sandboxId=%s', $sandboxId)); 
}
 elseif ($result->getCode() === SandboxResult::Normal && $result->getSandboxData()->getStatus() === SandboxResult::SandboxExited) 
{
 $this->logger->info(sprintf('Sandbox statusas exitedCreateNewsandbox : sandboxId=%s', $sandboxId)); 
}
 else 
{
 $this->logger->info(sprintf( 'Sandbox statusExceptionCreateNewsandbox : sandboxId=%s, status=%s', $sandboxId, $result->getSandboxData()->getStatus() )); 
}
 
}
 else 
{
 $this->logger->info('Sandbox IDEmptyCreateNewsandbox '); 
}
 // CreateNewsandbox $struct = new SandboxStruct(); $struct->setSandboxId($sandboxId); $result = $this->sandboxService->create($struct); // record CreateResult $this->logger->info(sprintf( 'Createsandbox Result: code=%d, success=%s, message=%s, data=%s, sandboxId=%s', $result->getCode(), $result->isSuccess() ? 'true' : 'false', $result->getMessage(), json_encode($result->getSandboxData()->toArray(), JSON_UNESCAPED_UNICODE), $result->getSandboxData()->getSandboxId() ?? 'null' )); // check CreateResult if (! $result->isSuccess()) 
{
 $this->logger->error(sprintf( 'Createsandbox Failed: code=%d, message=%s', $result->getCode(), $result->getMessage() )); return [false, '']; // Creation failed 
}
 // CreateSuccessReturn need InitializeConfiguration return [true, $result->getSandboxData()->getSandboxId()]; 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'sandbox InitializeException: %s, trace=%s', $e->getMessage(), $e->getTraceAsString() )); return [false, '']; 
}
 
}
 /** * UpdateTaskStatus. */ 
    private function updateTaskStatus( TaskEntity $task, DataIsolation $dataIsolation, string $taskId, TaskStatus $status, string $errMsg = '' ): void 
{
 try 
{
 // Getcurrent TaskStatusRow $currentTask = $this->taskDomainService->getTaskById($task->getId()); $currentStatus = $currentTask?->getStatus(); // Usingtool ClassValidate StatusConvert if (! TaskStatusValidator::isTransitionAllowed($currentStatus, $status)) 
{
 $reason = TaskStatusValidator::getRejectReason($currentStatus, $status); $this->logger->warning('DeclineStatusUpdate', [ 'task_id' => $taskId, 'current_status' => $currentStatus->value ?? null, 'new_status' => $status->value, 'reason' => $reason, 'error_msg' => $errMsg, ]); return; // DeclineUpdate 
}
 // execute StatusUpdate $this->taskDomainService->updateTaskStatus( status: $status, id: $task->getId(), taskId: $taskId, sandboxId: $task->getSandboxId(), errMsg: $errMsg ); // record SuccessLog $this->logger->info('TaskStatusUpdatecomplete ', [ 'task_id' => $taskId, 'previous_status' => $currentStatus->value ?? null, 'new_status' => $status->value, 'error_msg' => $errMsg, ]); 
}
 catch (Throwable $e) 
{
 $this->logger->error('UpdateTaskStatusFailed', [ 'task_id' => $taskId, 'status' => $status->value, 'error' => $e->getMessage(), 'error_msg' => $errMsg, ]); throw $e; 
}
 
}
 /** * Determinewhether as Error. * * @param Throwable $e ExceptionObject * @return bool whether as Error */ 
    private function isFatalError(Throwable $e): bool 
{
 // Connection errormemory Timeoutas Error $errorMessage = strtolower($e->getMessage()); return $e instanceof Error // PHPError || str_contains($errorMessage, 'memory') || str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'socket') || str_contains($errorMessage, 'closed'); 
}
 /** * process tool attachments in SaveTaskFiletable Filetable in . */ 
    private function processtool Attachments(?array &$tool, TaskContext $taskContext): void 
{
 if (empty($tool)) 
{
 return; 
}
 $task = $taskContext->getTask(); $dataIsolation = $taskContext->getDataIsolation(); // process tool ContentObject $this->processtool ContentStorage($tool, $taskContext); // process tool if (! empty($tool['attachments'])) 
{
 foreach ($tool['attachments'] as $i => $iValue) 
{
 $tool['attachments'][$i] = $this->processSingleAttachment( $iValue, $task, $dataIsolation ); 
}
 
}
 
}
 /** * process tool ContentObject. * * @param array $tool tool ArrayReferencePass * @param TaskContext $taskContext TaskContext */ 
    private function processtool ContentStorage(array &$tool, TaskContext $taskContext): void 
{
 // check whether EnabledObject $objectStorageEnabled = config('super-magic.task.tool_message.object_storage_enabled', true); if (! $objectStorageEnabled) 
{
 return; 
}
 // check tool Content $content = $tool['detail']['data']['content'] ?? ''; if (empty($content)) 
{
 return; 
}
 // check ContentLengthwhether ReachThreshold $minContentLength = config('super-magic.task.tool_message.min_content_length', 200); if (strlen($content) < $minContentLength) 
{
 return; 
}
 $this->logger->info(sprintf( 'Startprocess tool Contenttool ID: %sContentLength: %d', $tool['id'] ?? 'unknown', strlen($content) )); try 
{
 // BuildParameter $fileName = $tool['detail']['data']['file_name'] ?? 'tool_content.txt'; $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'txt'; $fileKey = ($tool['id'] ?? 'unknown') . '.' . $fileExtension; $task = $taskContext->getTask(); $workDir = rtrim($task->getWorkdir(), '/') . '/task_' . $task->getId() . '/.chat/'; // call Fileprocess AppServiceSaveContent $fileId = $this->fileprocess AppService->savetool MessageContent( fileName: $fileName, workDir: $workDir, fileKey: $fileKey, content: $content, dataIsolation: $taskContext->getDataIsolation(), projectId: $task->getProjectId(), topicId: $task->getTopicId(), taskId: (int) $task->getId() ); // Modifytool DataStructure $tool['detail']['data']['file_id'] = (string) $fileId; $tool['detail']['data']['content'] = ''; // ClearContent $tool['detail']['data']['file_extension'] = $fileExtension; $this->logger->info(sprintf( 'tool Contentcomplete tool ID: %sFileID: %dContentLength: %d', $tool['id'] ?? 'unknown', $fileId, strlen($content) )); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'tool ContentFailed: %stool ID: %sContentLength: %d', $e->getMessage(), $tool['id'] ?? 'unknown', strlen($content) )); // FailedMainrecord Error 
}
 
}
 
    private function processMessageAttachments(?array &$attachments, TaskContext $taskContext): void 
{
 if (empty($attachments)) 
{
 return; 
}
 $task = $taskContext->getTask(); $dataIsolation = $taskContext->getDataIsolation(); foreach ($attachments as $i => $iValue) 
{
 $attachments[$i] = $this->processSingleAttachment( $iValue, $task, $dataIsolation ); 
}
 
}
 /** * process SingleSaveTaskFiletable Filetable in . * * @param array $attachment info * @param TaskEntity $task Task * @param DataIsolation $dataIsolation DataObject * @return array process info */ 
    private function processSingleAttachment(array $attachment, TaskEntity $task, DataIsolation $dataIsolation): array 
{
 // check Field if (empty($attachment['file_key']) || empty($attachment['file_extension']) || empty($attachment['filename'])) 
{
 $this->logger->warning(sprintf( 'info IncompleteSkipprocess TaskID: %sContent: %s', $task->getTaskId(), json_encode($attachment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) )); return []; 
}
 try 
{
 // directly call Fileprocess AppServiceprocess [$fileId, $taskFileEntity] = $this->fileprocess AppService->processFileByFileKey( $attachment['file_key'], $dataIsolation, $attachment, $task->getProjectId(), $task->getTopicId(), (int) $task->getId(), $attachment['file_tag'] ?? FileType::PROCESS->value ); // SaveFileIDinfo in $attachment['file_id'] = (string) $fileId; $this->logger->info(sprintf( 'SaveSuccessFileID: %sTaskID: %sFile: %s', $fileId, $task->getTaskId(), $attachment['filename'] )); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'process Exception: %s, Name: %s, TaskID: %s', $e->getMessage(), $attachment['filename'] ?? 'Unknown', $task->getTaskId() )); 
}
 return $attachment; 
}
 /** * FromWebSocketReceiveMessageConvert toMessageFormat. * * @param array $message WebSocketReceiveMessage * @return TopicTaskMessageDTO MessageDTO */ 
    private function convertWebSocketMessageToDTO(array $message): TopicTaskMessageDTO 
{
 // BuildDataValueObject $metadata = MessageMetadata::fromArray($message['metadata'] ?? []); // CreateValueObject $payload = MessagePayload::fromArray($message['payload'] ?? []); // CreateDTO return new TopicTaskMessageDTO($metadata, $payload); 
}
 
}
 

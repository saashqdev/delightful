<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Application\Chat\Service\DelightfulChatMessageAppService;
use App\Application\Chat\Service\DelightfulUserInfoAppService;
use App\Application\File\Service\FileAppService;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Service\DelightfulChatFileDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\ModelGateway\Service\AccessTokenDomainService;
use App\Domain\ModelGateway\Service\ApplicationDomainService;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\EventException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\AsyncEvent\AsyncEventUtil;
use Delightful\BeDelightful\Application\BeAgent\DTO\TaskMessageDTO;
use Delightful\BeDelightful\Application\BeAgent\DTO\UserMessageDTO;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\ChatInstruction;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\FileType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MessageMetadata;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MessagePayload;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MessageType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskContext;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\UserInfoValueObject;
use Delightful\BeDelightful\Domain\BeAgent\Event\RunTaskAfterEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\RunTaskBeforeEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\RunTaskCallbackEvent;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Service\MessageBuilderDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\WorkspaceDomainService;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\Config\WebSocketConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\SandboxResult;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\SandboxStruct;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\Volcengine\SandboxService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox\WebSocket\WebSocketSession;
use Delightful\BeDelightful\Infrastructure\Utils\TaskStatusValidator;
use Delightful\BeDelightful\Infrastructure\Utils\ToolProcessor;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\TopicTaskMessageDTO;
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
    protected LoggerInterface $logger;

    /**
     * Message builder service
     */
    private MessageBuilderDomainService $messageBuilder;

    public function __construct(
        private readonly WorkspaceDomainService $workspaceDomainService,
        private readonly TopicDomainService $topicDomainService,
        private readonly TaskDomainService $taskDomainService,
        private readonly DelightfulChatMessageAppService $chatMessageAppService,
        private readonly DelightfulUserInfoAppService $userInfoAppService,
        private readonly DelightfulChatFileDomainService $chatFileDomainService,
        private readonly FileAppService $fileAppService,
        private readonly SandboxService $sandboxService,
        private readonly FileProcessAppService $fileProcessAppService,
        protected DelightfulUserDomainService $userDomainService,
        protected TaskRepositoryInterface $taskRepository,
        protected LockerInterface $locker,
        LoggerFactory $loggerFactory,
        protected AccessTokenDomainService $accessTokenDomainService,
        protected ApplicationDomainService $applicationDomainService,
        protected ProjectDomainService $projectDomainService,
    ) {
        $this->messageBuilder = new MessageBuilderDomainService();
        $this->logger = $loggerFactory->get(get_class($this));
    }

    /**
     * Initialize agent task, establish WebSocket connection, and start processing coroutine.
     */
    public function initAgentTask(
        DataIsolation $dataIsolation,
        string $agentUserId,
        string $conversationId,
        string $chatTopicId,
        string $prompt,
        ?string $attachments = null,
        ChatInstruction $instruction = ChatInstruction::Normal,
        string $taskMode = ''
    ): string {
        $topicId = 0;
        $taskId = '';
        try {
            $topicEntity = $this->topicDomainService->getTopicByChatTopicId($dataIsolation, $chatTopicId);
            if (is_null($topicEntity)) {
                ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
            }
            $topicId = $topicEntity->getId();
            // Check user task quantity limit and whitelist
            $this->beforeInitTask($dataIsolation, $instruction, $topicEntity);
            // Initialize task
            $userMessageDTO = new UserMessageDTO(
                agentUserId: $agentUserId,
                chatConversationId: $conversationId,
                chatTopicId: $chatTopicId,
                topicId: $topicId,
                prompt: $prompt,
                attachments: $attachments,
                mentions: null,
                instruction: $instruction,
                taskMode: $taskMode
            );

            // Get task mode from DTO, fallback to topic's task mode if empty
            $taskMode = $userMessageDTO->getTaskMode();
            if ($taskMode === '') {
                $taskMode = $topicEntity->getTaskMode();
            }
            $data = [
                'user_id' => $dataIsolation->getCurrentUserId(),
                'workspace_id' => $topicEntity->getWorkspaceId(),
                'project_id' => $topicEntity->getProjectId(),
                'topic_id' => $topicId,
                'task_id' => '', // Initially empty, this is agent's task id
                'task_mode' => $taskMode,
                'sandbox_id' => $topicEntity->getSandboxId(), // Current task prioritizes reusing previous topic's sandbox id
                'prompt' => $userMessageDTO->getPrompt(),
                'attachments' => $userMessageDTO->getAttachments(),
                'mentions' => $userMessageDTO->getMentions(),
                'task_status' => TaskStatus::WAITING->value,
                'work_dir' => $topicEntity->getWorkDir() ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $taskEntity = TaskEntity::fromArray($data);

            // Initialize task
            $taskEntity = $this->taskDomainService->initTopicTask(
                dataIsolation: $dataIsolation,
                topicEntity: $topicEntity,
                taskEntity: $taskEntity
            );

            $taskEntity = $this->taskDomainService->initTopicTask($dataIsolation, $topicEntity, $taskEntity);
            $taskId = (string) $taskEntity->getId();

            // Initialize context
            $taskContext = new TaskContext(
                $taskEntity,
                $dataIsolation,
                $conversationId,
                $chatTopicId,
                $agentUserId,
                $topicEntity->getSandboxId(),
                $taskId,
                $instruction,
            );

            // If is interrupt command，Send interrupt command directly
            if ($instruction == ChatInstruction::Interrupted) {
                $this->sendInternalMessageToSandbox($taskContext, $topicEntity);
                return $taskId;
            }

            // Remaining commands are chat messages
            // Process user sent information
            // Record user sent message
            $attachmentsArr = is_null($attachments) ? [] : json_decode($attachments, true);

            // Create TaskMessageDTO for user message
            $taskMessageDTO = new TaskMessageDTO(
                taskId: (string) $taskEntity->getId(),
                role: Role::User->value,
                senderUid: $dataIsolation->getCurrentUserId(),
                receiverUid: $agentUserId,
                messageType: 'chat',
                content: $prompt,
                status: null,
                steps: null,
                tool: null,
                topicId: $taskEntity->getTopicId(),
                event: '',
                attachments: $attachmentsArr,
                mentions: null,
                showInUi: true,
                messageId: null
            );

            $taskMessageEntity = TaskMessageEntity::taskMessageDTOToTaskMessageEntity($taskMessageDTO);

            $this->taskDomainService->recordTaskMessage($taskMessageEntity);
            // Process user uploaded attachment
            $this->fileProcessAppService->processInitialAttachments($attachments, $taskEntity, $dataIsolation);

            // Initialize sandbox environment
            // No sandboxid，Then must be first task
            $isFirstTaskMessage = empty($taskEntity->getSandboxId());
            /** @var bool $isInitConfig */
            [$isInitConfig, $sandboxId] = $this->initSandbox($taskEntity->getSandboxId());
            if (empty($sandboxId)) {
                $this->updateTaskStatus(
                    $taskEntity,
                    $dataIsolation,
                    $taskEntity->getTaskId(),
                    TaskStatus::ERROR,
                    'Failed to create sandbox'
                );
                throw new BusinessException('Failed to create sandbox', 500);
            }
            $this->logger->info(sprintf('Sandbox created successfully: %s', $sandboxId));
            $taskEntity->setSandboxId($sandboxId);
            // Set task status to waiting
            $this->updateTaskStatus($taskEntity, $dataIsolation, $taskId, TaskStatus::WAITING);
            $taskContext->setSandboxId($sandboxId);

            // 5. Start coroutine processingWebSocketCommunication
            $requestId = CoContext::getOrSetRequestId();
            Coroutine::create(function () use ($taskContext, $isInitConfig, $isFirstTaskMessage, $requestId) {
                try {
                    CoContext::setRequestId($requestId);
                    $this->sendChatMessageToSandbox($taskContext, $isInitConfig, $isFirstTaskMessage);
                } catch (Throwable $e) {
                    $this->logger->error(sprintf(
                        'WebSocketCommunication processing exception: %s, TaskID: %s',
                        $e->getMessage(),
                        $taskContext->getTaskId()
                    ));
                    // Update task status to error
                    $this->updateTaskStatus(
                        $taskContext->getTask(),
                        $taskContext->getDataIsolation(),
                        $taskContext->getTaskId(),
                        TaskStatus::ERROR,
                        $e->getMessage()
                    );
                }
            });

            return $taskContext->getTaskId();
        } catch (EventException $e) {
            $this->logger->error(sprintf(
                'Initialize task, Event processing failed: %s',
                $e->getMessage()
            ));
            // Send message to client
            $this->sendErrorMessageToClient($topicId, $taskId, $chatTopicId, $conversationId, $e->getMessage());
            throw new BusinessException('Initialize task, Event processing failed', 500);
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to initialize task: %s',
                $e->getMessage()
            ));

            $text = 'System busy. Please try again later';
            if ($e->getCode() === GenericErrorCode::IllegalOperation->value) {
                $text = $e->getMessage();
            }
            // Send message to client
            $this->sendErrorMessageToClient($topicId, (string) $taskId, $chatTopicId, $conversationId, $text);
            throw new BusinessException('Failed to initialize task', 500);
        }
    }

    public function beforeInitTask(DataIsolation $dataIsolation, ChatInstruction $instruction, TopicEntity $topicEntity): void
    {
        if ($instruction == ChatInstruction::Interrupted) {
            return;
        }

        $topicEntities = $this->topicDomainService->getUserRunningTopics($dataIsolation);
        $currentTaskRunCount = count($topicEntities); // Original count，Assume all running

        if ($currentTaskRunCount > 0) {
            // Use coroutines to concurrently check real sandbox status
            $parallel = new Parallel(10);
            $requestId = CoContext::getOrSetRequestId();

            foreach ($topicEntities as $index => $topicEntityItem) {
                $parallel->add(function () use ($topicEntityItem, $requestId) {
                    CoContext::setRequestId($requestId);
                    // Check real sandbox status and return 1 if not running (need to subtract)
                    $realStatus = $this->updateTaskStatusFromSandbox($topicEntityItem);
                    return $realStatus !== TaskStatus::RUNNING ? 1 : 0;
                }, (string) $index);
            }

            try {
                $results = $parallel->wait();
                // Subtract non-running topics from total count
                foreach ($results as $needSubtract) {
                    $currentTaskRunCount -= $needSubtract;
                }
            } catch (Throwable $e) {
                $this->logger->error(sprintf('Failed to check real task status concurrently: %s', $e->getMessage()));
                // Fallback: use original count without real status check
            }
        }

        $taskRound = $this->taskDomainService->getTaskNumByTopicId($topicEntity->getId());
        AsyncEventUtil::dispatch(new RunTaskBeforeEvent($dataIsolation->getCurrentOrganizationCode(), $dataIsolation->getCurrentUserId(), $topicEntity->getId(), $taskRound, $currentTaskRunCount, [], '', ''));
        $this->logger->info(sprintf('Deliver task start event.Topicid:%s, round: %d, currentTaskRunCount: %d (after real status check)', $topicEntity->getId(), $taskRound, $currentTaskRunCount));
    }

    public function updateTaskStatusFromSandbox(TopicEntity $topicEntity): TaskStatus
    {
        $this->logger->info(sprintf('Start checking task status: topic_id=%s', $topicEntity->getId()));
        if (! $topicEntity->getSandboxId()) {
            return TaskStatus::WAITING;
        }

        // CallSandboxServiceofgetStatusInterface get container status
        $result = $this->sandboxService->getStatus($topicEntity->getSandboxId());

        // If sandbox exists and status is running，Return that sandbox directly
        if ($result->getCode() === SandboxResult::Normal
            && $result->getSandboxData()->getStatus() === SandboxResult::SandboxRunnig) {
            $this->logger->info(sprintf('Sandbox status is normal(running): sandboxId=%s', $topicEntity->getSandboxId()));
            return TaskStatus::RUNNING;
        }

        // Record reason for creating new sandbox
        if ($result->getCode() === SandboxResult::NotFound) {
            $errMsg = 'Sandbox does not exist';
        } elseif ($result->getCode() === SandboxResult::Normal
            && $result->getSandboxData()->getStatus() === 'exited') {
            $errMsg = 'Sandbox already exited';
        } else {
            $errMsg = 'Sandbox exception';
        }

        // Get current task
        $taskId = $topicEntity->getCurrentTaskId();
        if ($taskId) {
            // Update task status
            $this->taskDomainService->updateTaskStatusByTaskId($taskId, TaskStatus::ERROR, $errMsg);
        }

        // Update topic status
        $this->topicDomainService->updateTopicStatus($topicEntity->getId(), $taskId, TaskStatus::ERROR);

        // Trigger complete event
        AsyncEventUtil::dispatch(new RunTaskAfterEvent(
            $topicEntity->getUserOrganizationCode(),
            $topicEntity->getUserId(),
            $topicEntity->getId(),
            $taskId,
            TaskStatus::ERROR->value,
            null
        ));

        $this->logger->info(sprintf('End checking task status: topic_id=%s, status=%s, error_msg=%s', $topicEntity->getId(), TaskStatus::ERROR->value, $errMsg));

        return TaskStatus::ERROR;
    }

    /**
     * Send terminate task message.
     * @throws Throwable
     */
    public function sendInternalMessageToSandbox(TaskContext $taskContext, TopicEntity $topicEntity, string $msg = ''): void
    {
        $text = empty($msg) ? 'Task already terminated.' : $msg;
        // Check if sandbox exists
        if (empty($topicEntity->getSandboxId())) {
            $this->logger->info('SandboxidDoes not exist，Update task status directly');
            $this->updateTaskStatus($taskContext->getTask(), $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::Suspended, 'SandboxidDoes not exist，Update task status directly');
            $this->sendErrorMessageToClient($topicEntity->getId(), (string) $taskContext->getTask()->getId(), $taskContext->getChatTopicId(), $taskContext->getChatConversationId(), $text);
            return;
        }
        // Call remote query if sandbox exists
        $result = $this->sandboxService->checkSandboxExists($topicEntity->getSandboxId());
        if ($result->getCode() == SandboxResult::NotFound || $result?->getSandboxData()?->getStatus() == SandboxResult::SandboxExited) {
            $this->logger->info('Sandbox does not exist or exit，Update task status directly');
            $this->updateTaskStatus($taskContext->getTask(), $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::Suspended, 'Sandbox does not exist or exit，Update task status directly');
            $this->sendErrorMessageToClient($topicEntity->getId(), (string) $taskContext->getTask()->getId(), $taskContext->getChatTopicId(), $taskContext->getChatConversationId(), $text);
        }
        // If sandbox exists, build websocket channel connection
        $websocketSession = $this->getSandboxWebsocketClient($taskContext);
        if (is_null($websocketSession)) {
            throw new BusinessException('Get sandboxwebsocketClient failed', 500);
        }
        try {
            // Set interrupt command
            $taskContext->getTask()->setPrompt('Terminate task');
            $taskContext->setInstruction(ChatInstruction::Interrupted);
            $message = $this->messageBuilder->buildInterruptMessage(
                $taskContext->getCurrentUserId(),
                $taskContext->getTask()->getId(),
                $taskContext->getTask()->getTaskMode(),
                $msg
            );
            $this->sendMessageToSandbox($websocketSession, $taskContext->getTask()->getId(), $message);
        } catch (Exception $e) {
            $this->logger->error(sprintf('Failed to terminate sandbox task message. Error content is: %s', $e->getMessage()));
            throw new BusinessException('Failed to send terminate task', 500);
        } finally {
            $websocketSession->disconnect();
        }
    }

    /**
     * Process topic task message.
     *
     * @param TopicTaskMessageDTO $messageDTO MessageDTO
     */
    public function handleTopicTaskMessage(TopicTaskMessageDTO $messageDTO): void
    {
        $this->logger->info(sprintf(
            'Start processing topic task message, task_id: %s. Message content is: %s',
            $messageDTO->getPayload()->getTaskId() ?? '',
            json_encode($messageDTO->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ));
        // Create data isolation object
        $dataIsolation = DataIsolation::create(
            $messageDTO->getMetadata()->getOrganizationCode(),
            $messageDTO->getMetadata()->getUserId()
        );
        // Dispatch event before processing message
        $topicEntity = $this->topicDomainService->getTopicByChatTopicId($dataIsolation, $messageDTO->getMetadata()->getChatTopicId());
        if (is_null($topicEntity)) {
            throw new RuntimeException(sprintf('According tochatTopic id: %s Topic information not found', $messageDTO->getMetadata()->getChatTopicId()));
        }

        // Get task information
        $taskEntity = $this->taskDomainService->getTaskById($topicEntity->getCurrentTaskId());
        if (is_null($taskEntity)) {
            throw new RuntimeException(sprintf('According to task id: %s Task information not found', $topicEntity->getCurrentTaskId() ?? ''));
        }

        // Create task context
        $taskContext = new TaskContext(
            task: $taskEntity,
            dataIsolation: $dataIsolation,
            chatConversationId: $messageDTO->getMetadata()?->getChatConversationId(),
            chatTopicId: $messageDTO->getMetadata()?->getChatTopicId(),
            agentUserId: $messageDTO->getMetadata()?->getAgentUserId(),
            sandboxId: $messageDTO->getMetadata()?->getSandboxId(),
            taskId: $messageDTO->getPayload()?->getTaskId(),
            instruction: ChatInstruction::tryFrom($messageDTO->getMetadata()?->getInstruction()) ?? ChatInstruction::Normal
        );

        try {
            // Process received message
            $this->handleReceivedMessage($messageDTO, $taskContext);

            // Process task status
            $status = $messageDTO->getPayload()->getStatus();
            $taskStatus = TaskStatus::tryFrom($status) ?? TaskStatus::ERROR;
            if (TaskStatus::tryFrom($status)) {
                $this->updateTaskStatus($taskContext->getTask(), $taskContext->getDataIsolation(), $taskContext->getTaskId(), $taskStatus);
            }

            AsyncEventUtil::dispatch(new RunTaskCallbackEvent(
                $taskContext->getCurrentOrganizationCode(),
                $taskContext->getCurrentUserId(),
                $taskContext->getTopicId(),
                $topicEntity->getTopicName(),
                $taskContext->getTask()->getId(),
                $messageDTO,
                $messageDTO->getMetadata()->getLanguage()
            ));

            $this->logger->info(sprintf(
                'Process topic task message complete, message_id: %s',
                $messageDTO->getPayload()->getMessageId()
            ));
        } catch (EventException $e) {
            $this->logger->error(sprintf('Exception occurred processing message event callback: %s', $e->getMessage()));
            $this->sendInternalMessageToSandbox($taskContext, $topicEntity, $e->getMessage());
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Process topic task message exception: %s, message_id: %s',
                $e->getMessage(),
                $messageDTO->getPayload()->getMessageId()
            ), [
                'exception' => $e,
                'message' => $messageDTO->toArray(),
            ]);
        }
    }

    /**
     * Get distributed mutex lock.
     *
     * @param string $lockKey Lock key name
     * @param string $lockOwner Lock holder
     * @param int $lockExpireSeconds Lock expiration time(seconds)
     * @return bool Whether successfully get lock
     */
    public function acquireLock(string $lockKey, string $lockOwner, int $lockExpireSeconds): bool
    {
        return $this->locker->mutexLock($lockKey, $lockOwner, $lockExpireSeconds);
    }

    /**
     * Release distributed mutex lock.
     *
     * @param string $lockKey Lock key name
     * @param string $lockOwner Lock holder
     * @return bool Whether successfully release lock
     */
    public function releaseLock(string $lockKey, string $lockOwner): bool
    {
        return $this->locker->release($lockKey, $lockOwner);
    }

    public function sendContinueMessageToSandbox(string $sandboxId, bool $isInit = false): bool
    {
        // Through sandboxid
        $topicEntity = $this->topicDomainService->getTopicBySandboxId($sandboxId);
        if (is_null($topicEntity)) {
            throw new RuntimeException(sprintf('According to sandbox id: %s Topic information not found', $sandboxId));
        }

        // Create data isolation object
        $dataIsolation = DataIsolation::create(
            $topicEntity->getUserOrganizationCode(),
            $topicEntity->getUserId(),
        );

        // Get task information
        $taskEntity = $this->taskDomainService->getTaskById($topicEntity->getCurrentTaskId());
        if (is_null($taskEntity)) {
            throw new RuntimeException(sprintf('According to task id: %s Task information not found', $topicEntity->getCurrentTaskId() ?? ''));
        }

        $taskContext = new TaskContext(
            task: $taskEntity,
            dataIsolation: $dataIsolation,
            chatConversationId: $topicEntity->getChatConversationId(),
            chatTopicId: $topicEntity->getChatTopicId(),
            agentUserId: '',
            sandboxId: $sandboxId,
            taskId: (string) $taskEntity->getId(),
            instruction: ChatInstruction::FollowUp
        );
        // Through sandbox id Query current
        $session = $this->getSandboxWebsocketClient($taskContext);
        if (is_null($session)) {
            throw new BusinessException('Get sandboxwebsocketClient failed');
        }

        // Send initialization message
        if ($isInit) {
            $this->initTaskMessageToSandbox($session, $taskContext, false);
        }
        $chatMessage = $this->messageBuilder->buildContinueMessage(
            $dataIsolation->getCurrentUserId(),
            $taskContext->getChatConversationId(),
        );

        $this->sendMessageToSandbox($session, $taskEntity->getId(), $chatMessage);

        return true;
    }

    /**
     * Summary of getTaskById.
     */
    public function getTaskById(int $taskId): ?TaskEntity
    {
        return $this->taskDomainService->getTaskById($taskId);
    }

    /**
     * Get websocket client cheap.
     */
    private function getSandboxWebsocketClient(TaskContext $taskContext): ?WebSocketSession
    {
        $config = new WebSocketConfig();
        $task = $taskContext->getTask();
        $sandboxId = $taskContext->getSandboxId();
        $wsUrl = $this->sandboxService->getWebsocketUrl($sandboxId);

        // Print connection parameters
        $this->logger->info(sprintf(
            'WebSocketConnection parameters. URL: %s, Maximum connection time: %d seconds',
            $wsUrl,
            $config->getConnectTimeout()
        ));

        // Create WebSocket Session
        $session = new WebSocketSession(
            $config,
            $this->logger,
            $wsUrl,
            $task->getTaskId()
        );

        try {
            $session->connect();
            return $session;
        } catch (Exception $e) {
            $this->logger->error(sprintf(
                'WebSocketConnection failed. URL: %s, Error message: %s',
                $wsUrl,
                $e->getMessage()
            ));
            return null;
        }
    }

    /**
     * ProcessWebSocketCommunication
     */
    private function sendChatMessageToSandbox(
        TaskContext $taskContext,
        bool $isInitConfig,
        bool $isFirstTaskMessage,
    ): void {
        // Establish connection
        $session = $this->getSandboxWebsocketClient($taskContext);
        if (is_null($session)) {
            throw new BusinessException('Get sandboxwebsocketClient failed');
        }
        try {
            // Send initialization message
            if ($isInitConfig) {
                $this->initTaskMessageToSandbox($session, $taskContext, $isFirstTaskMessage);
            }
            // Send chat message
            $dataIsolation = $taskContext->getDataIsolation();
            $task = $taskContext->getTask();
            $attachmentUrls = $this->getAttachmentUrls($task->getAttachments(), $dataIsolation->getCurrentOrganizationCode());
            $chatMessage = $this->messageBuilder->buildChatMessage(
                $dataIsolation->getCurrentUserId(),
                $task->getId(),
                $taskContext->getInstruction()->value,
                $task->getPrompt(),
                $attachmentUrls,
                $task->getTaskMode()
            );
            $taskId = $this->sendMessageToSandbox($session, $task->getId(), $chatMessage);
            // After successful initialization，Update status to running
            $taskContext->getTask()->setTaskId($taskId);
            // Update task to execution status
            $this->updateTaskStatus($taskContext->getTask(), $taskContext->getDataIsolation(), $taskId, TaskStatus::RUNNING);
            // Take a configuration here，Whether need to enter websocket Loop
            $mode = config('be-delightful.sandbox.pull_message_mode');
            // websocket Mode，Will continue waiting
            if ($mode === 'websocket') {
                $this->processMessageLoop($session, $taskContext);
            }
        } catch (Throwable $e) {
            $this->logger->error(sprintf('WebSocketSession exception: %s', $e->getMessage()), [
                'exception' => $e,
                'task_id' => $taskContext->getTask()->getTaskId(),
                'sandbox_id' => $taskContext->getTask()->getSandboxId(),
            ]);
            $this->updateTaskStatus($taskContext->getTask(), $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::ERROR, $e->getMessage());
            $this->sendErrorMessageToClient($taskContext->getTask()->getTopicId(), (string) $taskContext->getTask()->getId(), $taskContext->getChatTopicId(), $taskContext->getChatConversationId(), 'Remote server connection failed，Please try again later');
            throw $e;
        } finally {
            // Ensure connection closed
            try {
                $session->disconnect();
                $this->logger->info(sprintf(
                    'WebSocketSession close success. TaskID: %s',
                    $taskContext->getTaskId()
                ));
            } catch (Throwable $e) {
                $this->logger->warning(sprintf(
                    'CloseWebSocketConnection failed. Error: %s, TaskID: %s',
                    $e->getMessage(),
                    $taskContext->getTaskId()
                ));
            }
        }
    }

    private function initTaskMessageToSandbox(WebSocketSession $session, TaskContext $taskContext, bool $isFirstTaskMessage): string
    {
        $dataIsolation = $taskContext->getDataIsolation();
        $task = $taskContext->getTask();

        // Get ProjectEntity
        $projectEntity = $this->projectDomainService->getProjectNotUserId($task->getProjectId());

        $uploadCredential = $this->getUploadCredential(
            $dataIsolation->getCurrentUserId(),
            $projectEntity->getUserOrganizationCode(),
            $task->getWorkDir()
        );

        // Get user information
        $userInfo = null;
        try {
            $userInfoArray = $this->userInfoAppService->getUserInfo($dataIsolation->getCurrentUserId(), $dataIsolation);
            $userInfo = UserInfoValueObject::fromArray($userInfoArray);
        } catch (Throwable $e) {
            $this->logger->warning(sprintf(
                'Get user information failed: %s, UserID: %s',
                $e->getMessage(),
                $dataIsolation->getCurrentUserId()
            ));
        }

        // Use value object replace original array
        $messageMetadata = new MessageMetadata(
            agentUserId: $taskContext->getAgentUserId(),
            userId: $dataIsolation->getCurrentUserId(),
            organizationCode: $dataIsolation->getCurrentOrganizationCode(),
            chatConversationId: $taskContext->getChatConversationId(),
            chatTopicId: $taskContext->getChatTopicId(),
            instruction: $taskContext->getInstruction()->value,
            sandboxId: $taskContext->getSandboxId(),
            beDelightfulTaskId: (string) $task->getId(),
            userInfo: $userInfo
        );

        $topicEntity = $this->workspaceDomainService->getTopicById($task->getTopicId());
        if (is_null($topicEntity)) {
            throw new RuntimeException('Initialize agent found topic does not exist. Topic id: ' . $task->getTopicId());
        }
        $sandboxConfig = ! empty($topicEntity->getSandboxConfig()) ? json_decode($topicEntity->getSandboxConfig(), true) : null;
        $initMessage = $this->messageBuilder->buildInitMessage(
            $dataIsolation->getCurrentUserId(),
            $uploadCredential,
            $messageMetadata,
            $isFirstTaskMessage,
            $sandboxConfig,
            $task->getTaskMode(),
        );
        $this->logger->info(sprintf('[Send to Sandbox Init Message] task_id: %s, data: %s', $task->getTaskId(), json_encode($initMessage, JSON_UNESCAPED_UNICODE)));
        $session->send($initMessage);

        // Wait for initialization response
        $message = $session->receive(900);
        if ($message === null) {
            throw new RuntimeException('Wait agent Initialization response timeout');
        }

        $this->logger->info(sprintf(
            '[Receive from Sandbox Init Message] task_id: %s, data: %s',
            $task->getTaskId(),
            json_encode($message, JSON_UNESCAPED_UNICODE)
        ));

        // Convert original message to unified format
        $messageDTO = $this->convertWebSocketMessageToDTO($message);
        $payload = $messageDTO->getPayload();

        // Use new unified format for verification
        if (! $payload->getType() || $payload->getType() !== MessageType::Init->value) {
            throw new RuntimeException('Received unexpected initialization response type');
        }

        if ($payload->getStatus() === TaskStatus::ERROR->value) {
            throw new RuntimeException('agent Initialization failed: ' . json_encode($messageDTO->toArray(), JSON_UNESCAPED_UNICODE));
        }

        return $payload->getTaskId();
    }

    private function sendMessageToSandbox(WebSocketSession $session, int $taskId, array $chatMessage): string
    {
        $session->send($chatMessage);
        $this->logger->info(sprintf('[Send to Sandbox Chat Message] task_id: %d, data: %s', $taskId, json_encode($chatMessage, JSON_UNESCAPED_UNICODE)));

        // Wait for response
        $message = $session->receive(60);
        if ($message === null) {
            throw new RuntimeException('Wait agent Response timeout');
        }

        $this->logger->info(sprintf(
            '[Receive from Sandbox Chat Message] task_id: %d, data: %s',
            $taskId,
            json_encode($message, JSON_UNESCAPED_UNICODE)
        ));

        // Convert original message to unified format
        $messageDTO = $this->convertWebSocketMessageToDTO($message);
        $payload = $messageDTO->getPayload();

        // Use new unified format for verification
        if (! $payload->getType() || $payload->getType() !== MessageType::Chat->value) {
            throw new RuntimeException('Received unexpected response type');
        }

        if ($payload->getStatus() === TaskStatus::ERROR->value) {
            throw new RuntimeException('agent Response failed: ' . json_encode($messageDTO->toArray(), JSON_UNESCAPED_UNICODE));
        }

        return $payload->getTaskId();
    }

    /**
     * ProcessWebSocketMessage flow.
     */
    private function processMessageLoop(
        WebSocketSession $session,
        TaskContext $taskContext
    ): void {
        // Add maximum processing time limit, avoid infinite loop
        $startTime = time();
        $config = new WebSocketConfig();
        $taskTimeout = $config->getTaskTimeout();
        $task = $taskContext->getTask();

        while (true) {
            try {
                // Check connection status
                if (! $session->isConnected()) {
                    $this->logger->warning('WebSocketConnection disconnected.Try reconnect');
                    try {
                        $session->connect();
                    } catch (Throwable $e) {
                        $this->logger->error(sprintf(
                            'Reconnection failed: %s, TaskID: %s',
                            $e->getMessage(),
                            $taskContext->getTaskId()
                        ));

                        $this->updateTaskStatus($task, $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::ERROR, $e->getMessage());
                        return; // Exit processing
                    }
                }

                // Receive message
                $message = $session->receive($config->getReadTimeout());
                if ($message === null) {
                    // Periodically check if task timeout
                    if (time() - $startTime > $taskTimeout) {
                        $errMsg = sprintf(
                            'Task processing timeout，TaskID: %s，Runtime: %dseconds，Task timeout time: %dseconds',
                            $taskContext->getTaskId(),
                            time() - $startTime,
                            $taskTimeout
                        );
                        $this->logger->warning($errMsg);
                        $this->updateTaskStatus($task, $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::ERROR, $errMsg);
                        return; // Exit processing
                    }
                    continue;
                }

                $this->logger->info('[Websocket Server] Received message from server: ' . json_encode($message, JSON_UNESCAPED_UNICODE));

                // Convert message to unified format
                $messageDTO = $this->convertWebSocketMessageToDTO($message);

                // Set task id
                $taskContext->setTaskId($messageDTO->getPayload()->getTaskId() ?: $task->getTaskId());

                // Process message and determine if need to continue
                $shouldContinue = $this->handleReceivedMessage($messageDTO, $taskContext);
                if (! $shouldContinue) {
                    $this->logger->info('[Task already complete] task_id: ' . $taskContext->getTaskId());
                    break; // If is terminate message，ExitLoop
                }
            } catch (Throwable $e) {
                $this->logger->error(sprintf(
                    'Task Exception processing message: %s, TaskID: %s',
                    $e->getMessage(),
                    $taskContext->getTaskId()
                ));

                // Determine if fatal error，If yes then terminate processing
                if ($this->isFatalError($e)) {
                    $this->updateTaskStatus($task, $taskContext->getDataIsolation(), $taskContext->getTaskId(), TaskStatus::ERROR, $e->getMessage());
                    return; // Exit processing
                }

                // Non-fatal error，Continue processing
                continue;
            }
        }
    }

    /**
     * Process received message.
     *
     * @param TopicTaskMessageDTO $messageDTO Message
     * @param TaskContext $taskContext Task context
     * @return bool Whether continue processing message
     */
    private function handleReceivedMessage(TopicTaskMessageDTO $messageDTO, TaskContext $taskContext): bool
    {
        $payload = $messageDTO->getPayload();
        // 1. Parse message basic information
        $messageType = $payload->getType() ?: 'unknown';
        $content = $payload->getContent();
        $status = $payload->getStatus() ?: TaskStatus::RUNNING->value;
        $tool = $payload->getTool() ?? [];
        $steps = $payload->getSteps() ?? [];
        $event = $payload->getEvent();
        $attachments = $payload->getAttachments() ?? [];
        $projectArchive = $payload->getProjectArchive() ?? [];
        $showInUi = $payload->getShowInUi() ?? true;
        $messageId = $payload->getMessageId();
        $correlationId = $payload->getCorrelationId();

        // 2. Process unknown message type
        if (! MessageType::isValid($messageType)) {
            $this->logger->warning(sprintf(
                'Received unknown type message, type: %s, TaskID: %s',
                $messageType,
                $taskContext->getTaskId()
            ));
            return true;
        }

        // If is persistent sandbox message
        if ($messageType == MessageType::ProjectArchive->value) {
            $this->workspaceDomainService->updateTopicSandboxConfig($taskContext->getDataIsolation(), $taskContext->getTopicId(), $projectArchive);
            return true;
        }

        // 3. Process tool attachments(If have)
        try {
            if (! empty($tool['attachments'])) {
                $this->processToolAttachments($tool, $taskContext);
                // Use tool processor process fileIDMatch
                ToolProcessor::processToolAttachments($tool);
            }

            // Process message attachment
            $this->processMessageAttachments($attachments, $taskContext);

            // Each status need some special processing
            if ($status === TaskStatus::Suspended->value) {
                $this->pauseTaskSteps($steps);
            } elseif ($status === TaskStatus::FINISHED->value) {
                // Use tool processor generate output content tool
                $outputTool = ToolProcessor::generateOutputContentTool($attachments);
                if ($outputTool !== null) {
                    $tool = $outputTool;
                }
            }

            // 4. RecordAIMessage
            $task = $taskContext->getTask();

            // Create TaskMessageDTO for AI message
            $taskMessageDTO = new TaskMessageDTO(
                taskId: (string) $task->getId(),
                role: Role::Assistant->value,
                senderUid: $taskContext->getAgentUserId(),
                receiverUid: $task->getUserId(),
                messageType: $messageType,
                content: $content,
                status: $status,
                steps: $steps,
                tool: $tool,
                topicId: $task->getTopicId(),
                event: $event,
                attachments: $attachments,
                mentions: null,
                showInUi: $showInUi,
                messageId: $messageId,
                correlationId: $correlationId,
            );

            $taskMessageEntity = TaskMessageEntity::taskMessageDTOToTaskMessageEntity($taskMessageDTO);

            $this->taskDomainService->recordTaskMessage($taskMessageEntity);

            // 5. Send message to client
            if ($showInUi) {
                $this->sendMessageToClient(
                    topicId: $task->getTopicId(),
                    taskId: (string) $task->getId(),
                    chatTopicId: $taskContext->getChatTopicId(),
                    chatConversationId: $taskContext->getChatConversationId(),
                    content: $content,
                    messageType: $messageType,
                    status: $status,
                    event: $event,
                    steps: $steps,
                    tool: $tool,
                    attachments: $attachments,
                    correlationId: $correlationId,
                );
            }
            return true;
        } catch (Exception $e) {
            $this->logger->error(sprintf('Exception during message processing: %s', $e->getMessage()));
            return true;
        }
    }

    private function pauseTaskSteps(array &$steps): void
    {
        if (empty($steps)) {
            return;
        }
        // Set current step to pause
        foreach ($steps as $key => $step) {
            if ($step['status'] === TaskStatus::RUNNING->value) {
                // Frontend pause style
                $steps[$key]['status'] = TaskStatus::Suspended->value;
            }
        }
    }

    private function sendErrorMessageToClient(int $topicId, string $taskId, string $chatTopicId, string $chatConversationId, string $message): void
    {
        $this->sendMessageToClient(
            topicId: $topicId,
            taskId: $taskId,
            chatTopicId: $chatTopicId,
            chatConversationId: $chatConversationId,
            content: $message,
            messageType: MessageType::Error->value,
            status: TaskStatus::ERROR->value,
            event: '',
            steps: [],
            tool: [],
            attachments: [],
            correlationId: null,
        );
    }

    /**
     * Send message to client.
     *
     * @param int $topicId TopicID
     * @param string $taskId TaskID
     * @param string $chatTopicId Chat topicID
     * @param string $chatConversationId Chat session ID
     * @param string $content Message content
     * @param string $messageType Message type
     * @param string $status Status
     * @param string $event Event
     * @param null|array $steps Step
     * @param null|array $tool Tool
     * @param null|array $attachments Attachment
     */
    private function sendMessageToClient(
        int $topicId,
        string $taskId,
        string $chatTopicId,
        string $chatConversationId,
        string $content,
        string $messageType,
        string $status,
        string $event,
        ?array $steps = null,
        ?array $tool = null,
        ?array $attachments = null,
        ?string $correlationId = null,
    ): void {
        // Create message object
        $message = $this->messageBuilder->createBeAgentMessage(
            $topicId,
            $taskId,
            $content,
            $messageType,
            $status,
            $event,
            $steps,
            $tool,
            $attachments,
            $correlationId,
        );

        // Create serialized entity
        $seqDTO = new DelightfulSeqEntity();
        $seqDTO->setObjectType(ConversationType::Ai);
        $seqDTO->setContent($message);
        $seqDTO->setSeqType(ChatMessageType::BeAgentCard);

        $extra = new SeqExtra();
        $extra->setTopicId($chatTopicId);
        $seqDTO->setExtra($extra);
        $seqDTO->setConversationId($chatConversationId);

        $this->logger->info('[Send to Client] Send message to client: ' . json_encode($message->toArray(), JSON_UNESCAPED_UNICODE));

        // Send message
        $this->chatMessageAppService->aiSendMessage($seqDTO, (string) IdGenerator::getSnowId());
    }

    /**
     * Get upload credential
     */
    private function getUploadCredential(string $agentUserId, string $organizationCode, string $workDir): array
    {
        /*$userAuthorization = new DelightfulUserAuthorization();
        $userAuthorization->setId($agentUserId);
        $userAuthorization->setOrganizationCode($organizationCode);
        $userAuthorization->setUserType(UserType::Ai);*/
        // sts token Temporarily set 2 days
        return $this->fileAppService->getStsTemporaryCredentialV2($organizationCode, 'private', $workDir, 3600 * 2);
    }

    /**
     * Get attachmentURL.
     */
    private function getAttachmentUrls(string $attachmentsJson, string $organizationCode): array
    {
        if (empty($attachmentsJson)) {
            return [];
        }

        $attachments = Json::decode($attachmentsJson);
        if (empty($attachments)) {
            return [];
        }

        $fileIds = [];
        foreach ($attachments as $attachment) {
            $fileId = $attachment['file_id'] ?? '';
            if (empty($fileId)) {
                continue;
            }
            $fileIds[] = $fileId;
        }

        if (empty($fileIds)) {
            return [];
        }

        $files = [];
        $fileEntities = $this->chatFileDomainService->getFileEntitiesByFileIds($fileIds, null, null, true);
        foreach ($fileEntities as $fileEntity) {
            $files[] = [
                'file_extension' => $fileEntity->getFileExtension(),
                'file_key' => $fileEntity->getFileKey(),
                'file_size' => $fileEntity->getFileSize(),
                'filename' => $fileEntity->getFileName(),
                'display_filename' => $fileEntity->getFileName(),
                'file_tag' => FileType::USER_UPLOAD->value,
                'file_url' => $fileEntity->getExternalUrl(),
            ];
        }
        return $files;
    }

    /**
     * Initialize sandbox environment，Get sandboxID.
     *
     * @param string $sandboxId Existing sandboxID(If have)
     * @return array [bool $needInit, string $sandboxId] First element indicates if need to initialize configuration，Second element is sandboxID
     */
    private function initSandbox(string $sandboxId): array
    {
        try {
            // If already have sandboxID，First check sandbox status
            if (! empty($sandboxId)) {
                // Check if sandbox exists
                $result = $this->sandboxService->checkSandboxExists($sandboxId);

                // Record sandbox status
                $this->logger->info(sprintf(
                    'Check sandbox status: sandboxId=%s, code=%d, success=%s, data=%s',
                    $sandboxId,
                    $result->getCode(),
                    $result->isSuccess() ? 'true' : 'false',
                    json_encode($result->getSandboxData()->toArray(), JSON_UNESCAPED_UNICODE)
                ));

                // If sandbox exists and status is running，Return that sandbox directly
                if ($result->getCode() === SandboxResult::Normal
                    && $result->getSandboxData()->getStatus() === SandboxResult::SandboxRunnig) {
                    $this->logger->info(sprintf('Sandbox status is normal(running). Use directly: sandboxId=%s', $sandboxId));
                    return [false, $sandboxId]; // No need initialize configuration
                }

                // Record reason for creating new sandbox(Debug use，No business logic，Can be ignored)
                if ($result->getCode() === SandboxResult::NotFound) {
                    $this->logger->info(sprintf('Sandbox does not exist. Need to create new sandbox: sandboxId=%s', $sandboxId));
                } elseif ($result->getCode() === SandboxResult::Normal
                           && $result->getSandboxData()->getStatus() === SandboxResult::SandboxExited) {
                    $this->logger->info(sprintf('Sandbox status is exited. Need to create new sandbox: sandboxId=%s', $sandboxId));
                } else {
                    $this->logger->info(sprintf(
                        'Sandbox status exception. Need to create new sandbox: sandboxId=%s, status=%s',
                        $sandboxId,
                        $result->getSandboxData()->getStatus()
                    ));
                }
            } else {
                $this->logger->info('SandboxID is empty. Need to create new sandbox');
            }

            // Create new sandbox
            $struct = new SandboxStruct();
            $struct->setSandboxId($sandboxId);
            $result = $this->sandboxService->create($struct);

            // Record creation result
            $this->logger->info(sprintf(
                'Create sandbox result: code=%d, success=%s, message=%s, data=%s, sandboxId=%s',
                $result->getCode(),
                $result->isSuccess() ? 'true' : 'false',
                $result->getMessage(),
                json_encode($result->getSandboxData()->toArray(), JSON_UNESCAPED_UNICODE),
                $result->getSandboxData()->getSandboxId() ?? 'null'
            ));

            // Check creation result
            if (! $result->isSuccess()) {
                $this->logger->error(sprintf(
                    'Failed to create sandbox: code=%d, message=%s',
                    $result->getCode(),
                    $result->getMessage()
                ));
                return [false, '']; // Creation failed
            }

            // Creation successful，Return need initialize configuration
            return [true, $result->getSandboxData()->getSandboxId()];
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Sandbox initialization exception: %s, trace=%s',
                $e->getMessage(),
                $e->getTraceAsString()
            ));
            return [false, ''];
        }
    }

    /**
     * Update task status.
     */
    private function updateTaskStatus(
        TaskEntity $task,
        DataIsolation $dataIsolation,
        string $taskId,
        TaskStatus $status,
        string $errMsg = ''
    ): void {
        try {
            // Get current task status for validation
            $currentTask = $this->taskDomainService->getTaskById($task->getId());
            $currentStatus = $currentTask?->getStatus();

            // Use tool class verify status transition
            if (! TaskStatusValidator::isTransitionAllowed($currentStatus, $status)) {
                $reason = TaskStatusValidator::getRejectReason($currentStatus, $status);
                $this->logger->warning('Reject status update', [
                    'task_id' => $taskId,
                    'current_status' => $currentStatus->value ?? null,
                    'new_status' => $status->value,
                    'reason' => $reason,
                    'error_msg' => $errMsg,
                ]);
                return; // Silently reject update
            }

            // Execute status update
            $this->taskDomainService->updateTaskStatus(
                status: $status,
                id: $task->getId(),
                taskId: $taskId,
                sandboxId: $task->getSandboxId(),
                errMsg: $errMsg
            );

            // Record success log
            $this->logger->info('Task status update complete', [
                'task_id' => $taskId,
                'previous_status' => $currentStatus->value ?? null,
                'new_status' => $status->value,
                'error_msg' => $errMsg,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to update task status', [
                'task_id' => $taskId,
                'status' => $status->value,
                'error' => $e->getMessage(),
                'error_msg' => $errMsg,
            ]);
            throw $e;
        }
    }

    /**
     * Determine if fatal error.
     *
     * @param Throwable $e Exception object
     * @return bool Is fatal error
     */
    private function isFatalError(Throwable $e): bool
    {
        // Connection error,Insufficient memory,Timeout etc all viewed as fatal error
        $errorMessage = strtolower($e->getMessage());

        return $e instanceof Error  // PHPFatal error
            || str_contains($errorMessage, 'memory')
            || str_contains($errorMessage, 'timeout')
            || str_contains($errorMessage, 'socket')
            || str_contains($errorMessage, 'closed');
    }

    /**
     * Process attachments in tools，Save to task file table and chat file table.
     */
    private function processToolAttachments(?array &$tool, TaskContext $taskContext): void
    {
        if (empty($tool)) {
            return;
        }

        $task = $taskContext->getTask();
        $dataIsolation = $taskContext->getDataIsolation();

        // Process tool content storage to object storage
        $this->processToolContentStorage($tool, $taskContext);

        // Process tool attachments
        if (! empty($tool['attachments'])) {
            foreach ($tool['attachments'] as $i => $iValue) {
                $tool['attachments'][$i] = $this->processSingleAttachment(
                    $iValue,
                    $task,
                    $dataIsolation
                );
            }
        }
    }

    /**
     * Process tool content storage to object storage.
     *
     * @param array $tool Tool array(Reference passing)
     * @param TaskContext $taskContext Task context
     */
    private function processToolContentStorage(array &$tool, TaskContext $taskContext): void
    {
        // Check if enable object storage
        $objectStorageEnabled = config('be-delightful.task.tool_message.object_storage_enabled', true);
        if (! $objectStorageEnabled) {
            return;
        }

        // Check tool content
        $content = $tool['detail']['data']['content'] ?? '';
        if (empty($content)) {
            return;
        }

        // Check if content length reaches threshold
        $minContentLength = config('be-delightful.task.tool_message.min_content_length', 200);
        if (strlen($content) < $minContentLength) {
            return;
        }

        $this->logger->info(sprintf(
            'Start processing tool content storage，ToolID: %s，Content length: %d',
            $tool['id'] ?? 'unknown',
            strlen($content)
        ));

        try {
            // Build parameters
            $fileName = $tool['detail']['data']['file_name'] ?? 'tool_content.txt';
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'txt';
            $fileKey = ($tool['id'] ?? 'unknown') . '.' . $fileExtension;
            $task = $taskContext->getTask();
            $workDir = rtrim($task->getWorkdir(), '/') . '/task_' . $task->getId() . '/.chat/';

            // CallFileProcessAppServiceSave content
            $fileId = $this->fileProcessAppService->saveToolMessageContent(
                fileName: $fileName,
                workDir: $workDir,
                fileKey: $fileKey,
                content: $content,
                dataIsolation: $taskContext->getDataIsolation(),
                projectId: $task->getProjectId(),
                topicId: $task->getTopicId(),
                taskId: (int) $task->getId()
            );

            // Modify tool data structure
            $tool['detail']['data']['file_id'] = (string) $fileId;
            $tool['detail']['data']['content'] = ''; // Clear content
            $tool['detail']['data']['file_extension'] = $fileExtension;

            $this->logger->info(sprintf(
                'Tool content storage complete. ToolID: %s, FileID: %d, Original content length: %d',
                $tool['id'] ?? 'unknown',
                $fileId,
                strlen($content)
            ));
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Tool content storage failed: %s, ToolID: %s, Content length: %d',
                $e->getMessage(),
                $tool['id'] ?? 'unknown',
                strlen($content)
            ));
            // Storage failure does not affect main flow，Only record error
        }
    }

    private function processMessageAttachments(?array &$attachments, TaskContext $taskContext): void
    {
        if (empty($attachments)) {
            return;
        }

        $task = $taskContext->getTask();
        $dataIsolation = $taskContext->getDataIsolation();

        foreach ($attachments as $i => $iValue) {
            $attachments[$i] = $this->processSingleAttachment(
                $iValue,
                $task,
                $dataIsolation
            );
        }
    }

    /**
     * Process single attachment，Save to task file table and chat file table.
     *
     * @param array $attachment Attachment information
     * @param TaskEntity $task Task entity
     * @param DataIsolation $dataIsolation Data isolation object
     * @return array ProcessAfterofAttachment information
     */
    private function processSingleAttachment(array $attachment, TaskEntity $task, DataIsolation $dataIsolation): array
    {
        // Check required fields
        if (empty($attachment['file_key']) || empty($attachment['file_extension']) || empty($attachment['filename'])) {
            $this->logger->warning(sprintf(
                'Attachment information incomplete. Skip processing, TaskID: %s, Attachment content: %s',
                $task->getTaskId(),
                json_encode($attachment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));
            return [];
        }

        try {
            // Call directlyFileProcessAppServiceProcess attachment
            [$fileId, $taskFileEntity] = $this->fileProcessAppService->processFileByFileKey(
                $attachment['file_key'],
                $dataIsolation,
                $attachment,
                $task->getProjectId(),
                $task->getTopicId(),
                (int) $task->getId(),
                $attachment['file_tag'] ?? FileType::PROCESS->value
            );

            // Save fileIDtoAttachment informationin
            $attachment['file_id'] = (string) $fileId;

            $this->logger->info(sprintf(
                'Attachment saved successfully. FileID: %s, TaskID: %s, File name: %s',
                $fileId,
                $task->getTaskId(),
                $attachment['filename']
            ));
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Exception processing attachment: %s, Attachment name: %s, TaskID: %s',
                $e->getMessage(),
                $attachment['filename'] ?? 'Unknown',
                $task->getTaskId()
            ));
        }

        return $attachment;
    }

    /**
     * FromWebSocketConvert received message to unified message format.
     *
     * @param array $message WebSocketReceived message
     * @return TopicTaskMessageDTO Unified messageDTO
     */
    private function convertWebSocketMessageToDTO(array $message): TopicTaskMessageDTO
    {
        // Build metadata value object
        $metadata = MessageMetadata::fromArray($message['metadata'] ?? []);

        // Create payload value object
        $payload = MessagePayload::fromArray($message['payload'] ?? []);

        // CreateDTO
        return new TopicTaskMessageDTO($metadata, $payload);
    }
}

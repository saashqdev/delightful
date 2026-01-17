<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\BeAgent\Service\UsageCalculator\UsageCalculatorInterface;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Delightful\AsyncEvent\AsyncEventUtil;
use Delightful\BeDelightful\Application\BeAgent\Event\Publish\TopicMessageProcessPublisher;
use Delightful\BeDelightful\Domain\BeAgent\Constant\AgentConstant;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\MessageType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\BeAgent\Event\RunTaskAfterEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\RunTaskCallbackEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\TopicMessageProcessEvent;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskMessageModel;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\SandboxDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskMessageDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\SandboxStatus;
use Delightful\BeDelightful\Infrastructure\Utils\TaskStatusValidator;
use Delightful\BeDelightful\Infrastructure\Utils\ToolProcessor;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Delightful\BeDelightful\Infrastructure\Utils\WorkFileUtil;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\DeliverMessageResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\TopicTaskMessageDTO;
use Hyperf\Amqp\Producer;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class TopicTaskAppService extends AbstractAppService
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly FileProcessAppService $fileProcessAppService,
        private readonly ClientMessageAppService $clientMessageAppService,
        private readonly ProjectDomainService $projectDomainService,
        private readonly TopicDomainService $topicDomainService,
        private readonly TaskDomainService $taskDomainService,
        private readonly TaskFileDomainService $taskFileDomainService,
        private readonly TaskMessageDomainService $taskMessageDomainService,
        private readonly SandboxDomainService $sandboxDomainService,
        protected DelightfulUserDomainService $userDomainService,
        protected LockerInterface $locker,
        protected LoggerFactory $loggerFactory,
        protected TranslatorInterface $translator,
        protected UsageCalculatorInterface $usageCalculator,
    ) {
        $this->logger = $this->loggerFactory->get(get_class($this));
    }

    /**
     * Deliver topic task message.
     *
     * @return array Operation result
     */
    public function deliverTopicTaskMessage(TopicTaskMessageDTO $messageDTO): array
    {
        // Get current task id
        $taskId = $messageDTO->getMetadata()->getBeDelightfulTaskId();
        $taskEntity = $this->taskDomainService->getTaskById((int) $taskId);
        if (! $taskEntity) {
            $this->logger->warning('Invalidtask_id，Unable to process message', ['messageData' => $taskId]);
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'message_missing_task_id');
        }

        // Getsandbox_id
        $sandboxId = $messageDTO->getMetadata()->getSandboxId();
        $metadata = $messageDTO->getMetadata();
        $language = $this->translator->getLocale();
        $metadata->setLanguage($language);
        $messageDTO->setMetadata($metadata);
        $messageId = $messageDTO->getPayload()->getMessageId();
        $seqId = $messageDTO->getPayload()->getSeqId();

        $this->logger->info('Start processing topic task message delivery', [
            'sandbox_id' => $sandboxId,
            'message_id' => $messageDTO->getPayload()?->getMessageId(),
        ]);

        $lockKey = 'deliver_sandbox_message_lock:' . $sandboxId;
        $lockOwner = IdGenerator::getUniqueId32(); // Use unique ID as lock holder identifier
        $lockExpireSeconds = 10; // Lock expiration time (seconds) to prevent deadlock
        $lockAcquired = false;

        try {
            // Attempt to acquire distributed mutex lock
            $lockAcquired = $this->locker->spinLock($lockKey, $lockOwner, $lockExpireSeconds);
            if ($lockAcquired) {
                // 1. According tosandbox_idGettopic_id
                $topicEntity = $this->topicDomainService->getTopicBySandboxId($sandboxId);
                if (! $topicEntity) {
                    $this->logger->error('According tosandbox_idCorresponding not foundtopic', ['sandbox_id' => $sandboxId]);
                    ExceptionBuilder::throw(GenericErrorCode::SystemError, 'topic_not_found_by_sandbox_id');
                }

                // Determine seq_id Whether is expected value
                $exceptedSeqId = $this->taskMessageDomainService->getNextSeqId($topicEntity->getId(), $taskEntity->getId());
                if ($seqId !== $exceptedSeqId) {
                    $this->logger->error('seq_id Not expected value', ['seq_id' => $seqId, 'expected_seq_id' => $exceptedSeqId]);
                }

                $topicId = $topicEntity->getId();
                // 2. Complete at application layerDTOConvert to entity
                // Get message ID (prefer message ID from payload, generate new one if none)
                $messageId = $messageDTO->getPayload()?->getMessageId() ?: IdGenerator::getSnowId();
                $dataIsolation = DataIsolation::simpleMake($topicEntity->getUserOrganizationCode(), $topicEntity->getUserId());
                $aiUserEntity = $this->userDomainService->getByAiCode($dataIsolation, AgentConstant::BE_DELIGHTFUL_CODE);
                $messageEntity = $messageDTO->toTaskMessageEntity($topicId, $aiUserEntity->getUserId(), $topicEntity->getUserId());

                // 3. Store message to database(CallDomain layer service)
                $this->taskMessageDomainService->storeTopicTaskMessage($messageEntity, $messageDTO->toArray());

                // 4. Publish lightweight processing event
                $processEvent = new TopicMessageProcessEvent($topicId, $taskEntity->getId());
                $processPublisher = new TopicMessageProcessPublisher($processEvent);

                $producer = di(Producer::class);
                $result = $producer->produce($processPublisher);

                if (! $result) {
                    $this->logger->error('Failed to publish message processing event', [
                        'topic_id' => $topicId,
                        'sandbox_id' => $sandboxId,
                        'message_id' => $messageDTO->getPayload()?->getMessageId(),
                    ]);
                    ExceptionBuilder::throw(GenericErrorCode::SystemError, 'message_process_event_publish_failed');
                }

                $this->logger->info('Topic task message delivery complete', [
                    'topic_id' => $topicId,
                    'sandbox_id' => $sandboxId,
                    'message_id' => $messageDTO->getPayload()?->getMessageId(),
                ]);
            }
        } catch (Throwable $e) {
            $this->logger->error('Topic task message delivery failed', [
                'sandbox_id' => $sandboxId,
                'message_id' => $messageDTO->getPayload()?->getMessageId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            if ($lockAcquired) {
                if ($this->locker->release($lockKey, $lockOwner)) {
                    $this->logger->debug(sprintf('Lock released for sandbox %s by %s', $sandboxId, $lockOwner));
                } else {
                    // Log lock release failure, may require manual intervention
                    $this->logger->error(sprintf('Failed to release lock for sandbox %s held by %s. Manual intervention may be required.', $sandboxId, $lockOwner));
                }
            }
        }

        return DeliverMessageResponseDTO::fromResult(true, $messageId)->toArray();
    }

    public function handleTopicTaskMessage(TopicTaskMessageDTO $messageDTO): array
    {
        // 1，Initialize data
        $taskId = $messageDTO->getMetadata()->getBeDelightfulTaskId();
        $taskEntity = $this->taskDomainService->getTaskById((int) $taskId);
        if (! $taskEntity) {
            $this->logger->warning('Invalidtask_id，Unable to process message', ['messageData' => $taskId]);
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'message_missing_task_id');
        }

        $metadata = $messageDTO->getMetadata();
        $language = $this->translator->getLocale();
        $metadata->setLanguage($language);
        $messageDTO->setMetadata($metadata);
        $dataIsolation = DataIsolation::simpleMake($metadata->getOrganizationCode(), $metadata->getUserId());
        $messageId = $messageDTO->getPayload()->getMessageId();
        $topicId = $taskEntity->getTopicId();

        $this->logger->info('Start processing topic task message', [
            'topic_id' => $topicId,
            'task_id' => $taskId,
            'message_id' => $messageId,
        ]);

        // GetTopic level lock
        $lockKey = 'handle_topic_message_lock:' . $topicId;
        $lockOwner = IdGenerator::getUniqueId32();
        $lockExpireSeconds = 15; // Lock expiration time
        $lockAcquired = false;

        try {
            // TryGetDistributed lock
            $lockAcquired = $this->locker->spinLock($lockKey, $lockOwner, $lockExpireSeconds);
            if (! $lockAcquired) {
                $this->logger->warning('UnableGetTopic lock，Message processing skipped', [
                    'topic_id' => $topicId,
                    'message_id' => $messageId,
                ]);
                ExceptionBuilder::throw(GenericErrorCode::SystemError, 'unable_to_acquire_topic_lock');
            }

            $status = $messageDTO->getPayload()->getStatus();
            $taskStatus = TaskStatus::tryFrom($status) ?? TaskStatus::ERROR;
            $usage = [];
            if ($taskStatus->isFinal()) {
                // Calculate usage information when task is finished
                $usage = $this->usageCalculator->calculateUsage((int) $taskId);
            }

            // 2，Store message
            $messageEntity = $this->taskMessageDomainService->findByTopicIdAndMessageId($topicId, $messageId);
            if (is_null($messageEntity)) {
                // Create new message
                $messageEntity = $this->parseMessageContent($messageDTO);
                $messageEntity->setTopicId($topicId);
                $this->processMessageAttachment($dataIsolation, $taskEntity, $messageEntity);
                $this->processToolContent($dataIsolation, $taskEntity, $messageEntity);
                // Set usage if task is finished
                if (! empty($usage)) {
                    $messageEntity->setUsage($usage);
                }

                $this->taskMessageDomainService->storeTopicTaskMessage($messageEntity, [], TaskMessageModel::PROCESSING_STATUS_COMPLETED);
            }

            // 3. Push message to client(Async processing)
            if ($messageEntity->getShowInUi()) {
                $this->clientMessageAppService->sendMessageToClient(
                    messageId: $messageEntity->getId(),
                    topicId: $topicId,
                    taskId: (string) $taskEntity->getId(),
                    chatTopicId: $metadata->getChatTopicId(),
                    chatConversationId: $metadata->getChatConversationId(),
                    content: $messageEntity->getContent(),
                    messageType: $messageEntity->getType(),
                    status: $messageEntity->getStatus(),
                    event: $messageEntity->getEvent(),
                    steps: $messageEntity->getSteps() ?? [],
                    tool: $messageEntity->getTool() ?? [],
                    attachments: $messageEntity->getAttachments() ?? [],
                    correlationId: $messageDTO->getPayload()->getCorrelationId() ?? null,
                    usage: ! empty($usage) ? $usage : null,
                );
            }

            // 4. Update topic status
            if (TaskStatus::tryFrom($status)) {
                $this->updateTaskStatus(
                    dataIsolation: $dataIsolation,
                    task: $taskEntity,
                    status: $taskStatus,
                    errMsg: ''
                );
            }

            // 5. Dispatch callback event - Only triggered when task completes or fails，Does not include Stopped Status
            if ($taskStatus->isFinal()) {
                $this->dispatchCallbackEvent($messageDTO, $dataIsolation, $topicId, $taskEntity);
            }

            $this->logger->info('Topic task message processing complete', [
                'topic_id' => $topicId,
                'task_id' => $taskId,
                'message_id' => $messageId,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Topic task message processing failed', [
                'topic_id' => $topicId,
                'task_id' => $taskId,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            if ($lockAcquired) {
                if ($this->locker->release($lockKey, $lockOwner)) {
                    $this->logger->debug(sprintf('Topic lock released topic_id: %d, owner: %s', $topicId, $lockOwner));
                } else {
                    $this->logger->error(sprintf('Failed to release topic lock topic_id: %d, owner: %s, may require manual intervention', $topicId, $lockOwner));
                }
            }
        }

        return DeliverMessageResponseDTO::fromResult(true, $messageId)->toArray();
    }

    /**
     * Update task status.
     */
    public function updateTaskStatus(DataIsolation $dataIsolation, TaskEntity $task, TaskStatus $status, string $errMsg = ''): void
    {
        $taskId = (string) $task?->getId();
        try {
            // Get current task status for validation
            $currentStatus = $task?->getStatus();
            // Use utility class to validate status transition
            if (! TaskStatusValidator::isTransitionAllowed($currentStatus, $status)) {
                $reason = TaskStatusValidator::getRejectReason($currentStatus, $status);
                $this->logger->info('Rejected status update', [
                    'task_id' => $taskId,
                    'current_status' => $currentStatus->value ?? 'null',
                    'new_status' => $status->value,
                    'reason' => $reason,
                    'error_msg' => $errMsg,
                ]);
                return; // Silently reject update
            }

            // Execute status update
            $this->taskDomainService->updateTaskStatus(
                $status,
                $task->getId(),
                $taskId,
                $task->getSandboxId(),
                $errMsg
            );
            $this->topicDomainService->updateTopicStatusAndSandboxId($task->getTopicId(), $task->getId(), $status, $task->getSandboxId());

            $this->projectDomainService->updateProjectStatus($task->getProjectId(), $task->getTopicId(), $status);
            // Log success
            $this->logger->info('Task status update completed', [
                'task_id' => $taskId,
                'sandbox_id' => $task->getSandboxId(),
                'previous_status' => $currentStatus->value ?? 'null',
                'new_status' => $status->value,
                'error_msg' => $errMsg,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to update task status', [
                'task_id' => $taskId,
                'sandbox_id' => $task->getSandboxId(),
                'status' => $status->value,
                'error' => $e->getMessage(),
                'error_msg' => $errMsg,
            ]);
            throw $e;
        }
    }

    public function updateTaskStatusFromSandbox(TopicEntity $topicEntity): TaskStatus
    {
        $this->logger->info(sprintf('Start checking task status: topic_id=%s', $topicEntity->getId()));
        $sandboxId = ! empty($topicEntity->getSandboxId()) ? $topicEntity->getSandboxId() : (string) $topicEntity->getId();

        // CallSandboxServiceofgetStatusInterfaceGetContainerStatus
        $result = $this->sandboxDomainService->getSandboxStatus($sandboxId);

        // If sandbox exists andStatusis running，Return sandbox directly
        if ($result->getStatus() === SandboxStatus::RUNNING) {
            $this->logger->info(sprintf('SandboxStatusNormal(running): sandboxId=%s', $topicEntity->getSandboxId()));
            return TaskStatus::RUNNING;
        }

        $errMsg = $result->getStatus();

        // Get current task
        $taskId = $topicEntity->getCurrentTaskId();
        if ($taskId) {
            // Update taskStatus
            $this->taskDomainService->updateTaskStatusByTaskId($taskId, TaskStatus::ERROR, $errMsg);
        }

        // Update topic status
        $this->topicDomainService->updateTopicStatus($topicEntity->getId(), $taskId, TaskStatus::ERROR);

        // Trigger completion event
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

    public function processMessageAttachment(DataIsolation $dataIsolation, TaskEntity $task, TaskMessageEntity $message): void
    {
        $fileKeys = [];
        $fileInfoMap = [];
        // GetMessage attachment
        if (! empty($message->getAttachments())) {
            foreach ($message->getAttachments() as $attachment) {
                if (! empty($attachment['file_key'])) {
                    $fileKeys[] = $attachment['file_key'];
                    $fileInfoMap[$attachment['file_key']] = $attachment;
                }
            }
        }
        // Getin message，ToolofAttachment
        if (! empty($message->getTool()) && ! empty($message->getTool()['attachments'])) {
            foreach ($message->getTool()['attachments'] as $attachment) {
                if (! empty($attachment['file_key'])) {
                    $fileKeys[] = $attachment['file_key'];
                    $fileInfoMap[$attachment['file_key']] = $attachment;
                }
            }
        }
        if (empty($fileKeys)) {
            return;
        }
        // Through file_key Find file id
        $fileEntities = $this->taskFileDomainService->getByFileKeys($fileKeys);
        $fileIdMap = [];
        foreach ($fileEntities as $fileEntity) {
            $fileIdMap[$fileEntity->getFileKey()] = $fileEntity->getFileId();
        }

        // Handle missing file_keys: find which file_keys don't have corresponding records
        $missingFileKeys = array_diff($fileKeys, array_keys($fileIdMap));
        if (! empty($missingFileKeys)) {
            $projectEntity = $this->projectDomainService->getProjectNotUserId($task->getProjectId());
            if ($projectEntity) {
                // Process each missing file with file-level locking for better concurrency
                foreach ($missingFileKeys as $missingFileKey) {
                    if (! isset($fileInfoMap[$missingFileKey])) {
                        $this->logger->error(sprintf('Missing file_key: %s', $missingFileKey));
                        continue;
                    }

                    // Setup file-level lock to prevent concurrent processing of the same file
                    $lockKey = WorkDirectoryUtil::getFileLockerKey($missingFileKey, 'topic_message_attachment');
                    $lockOwner = IdGenerator::getUniqueId32();
                    $lockExpireSeconds = 2;
                    $lockAcquired = false;

                    try {
                        // Attempt to acquire distributed file-level lock
                        $lockAcquired = $this->locker->spinLock($lockKey, $lockOwner, $lockExpireSeconds);
                        if (! $lockAcquired) {
                            $this->logger->warning(sprintf(
                                'Failed to acquire file lock for missing file processing, file_key: %s, task_id: %s',
                                $missingFileKey,
                                $task->getTaskId()
                            ));
                            continue;
                        }

                        // Re-check file existence after acquiring lock to avoid duplicate creation
                        $existingFile = $this->taskFileDomainService->getByFileKey($missingFileKey);
                        if ($existingFile !== null) {
                            $fileIdMap[$missingFileKey] = $existingFile->getFileId();
                            $this->logger->info(sprintf(
                                'File already created by another process, file_key: %s, file_id: %d',
                                $missingFileKey,
                                $existingFile->getFileId()
                            ));
                            continue;
                        }

                        // Create file record
                        $storageType = WorkFileUtil::isSnapshotFile($missingFileKey)
                            ? StorageType::SNAPSHOT->value
                            : StorageType::WORKSPACE->value;
                        $fileInfoMap[$missingFileKey]['storage_type'] = $storageType;
                        $fallbackFileEntity = $this->taskFileDomainService->convertMessageAttachmentToTaskFileEntity($fileInfoMap[$missingFileKey], $task);
                        $this->logger->info(sprintf(
                            'Attachment not found in database, creating file record, task_id: %s, file_key: %s',
                            $task->getTaskId(),
                            $missingFileKey
                        ));
                        $savedEntity = $this->taskFileDomainService->saveProjectFile(
                            $dataIsolation,
                            $projectEntity,
                            $fallbackFileEntity,
                            $storageType
                        );
                        $fileIdMap[$missingFileKey] = $savedEntity->getFileId();

                        $this->logger->info(sprintf(
                            'Missing file processed successfully with file lock, file_key: %s, file_id: %d, task_id: %s',
                            $missingFileKey,
                            $savedEntity->getFileId(),
                            $task->getTaskId()
                        ));
                    } catch (Throwable $e) {
                        $this->logger->error(sprintf(
                            'Exception processing missing file with lock protection, file_key: %s, task_id: %s, error: %s',
                            $missingFileKey,
                            $task->getTaskId(),
                            $e->getMessage()
                        ));
                        // Continue processing other files instead of throwing
                    } finally {
                        // Ensure file-level lock is always released
                        if ($lockAcquired) {
                            if (! $this->locker->release($lockKey, $lockOwner)) {
                                $this->logger->error(sprintf(
                                    'Failed to release file lock for missing file processing, file_key: %s, task_id: %s',
                                    $missingFileKey,
                                    $task->getTaskId()
                                ));
                            }
                        }
                    }
                }
            } else {
                $this->logger->error(sprintf('Project not found, project_id: %s', $task->getProjectId()));
            }
        }

        // Put file_id assign to message attachment and message tool attachment
        if (! empty($fileIdMap)) {
            // ProcessMessage attachment
            $attachments = $message->getAttachments();
            if (! empty($attachments)) {
                foreach ($attachments as &$attachment) {
                    if (! empty($attachment['file_key']) && isset($fileIdMap[$attachment['file_key']])) {
                        $attachment['file_id'] = (string) $fileIdMap[$attachment['file_key']];
                    }
                }
                $message->setAttachments($attachments);
            }

            // ProcessToolAttachment
            $tool = $message->getTool();
            if (! empty($tool) && ! empty($tool['attachments'])) {
                foreach ($tool['attachments'] as &$attachment) {
                    if (! empty($attachment['file_key']) && isset($fileIdMap[$attachment['file_key']])) {
                        $attachment['file_id'] = (string) $fileIdMap[$attachment['file_key']];
                    }
                }
                $message->setTool($tool);
            }
        }

        // Special status handling: generate output content tool when task is finished
        if ($message->getStatus() === TaskStatus::FINISHED->value) {
            $outputTool = ToolProcessor::generateOutputContentTool($message->getAttachments());
            if ($outputTool !== null) {
                $message->setTool($outputTool);
            }
        }
    }

    private function parseMessageContent(TopicTaskMessageDTO $messageDTO): TaskMessageEntity
    {
        $payload = $messageDTO->getPayload();
        $metadata = $messageDTO->getMetadata();

        // Create TaskMessageEntity with messageId
        $taskMessageEntity = new TaskMessageEntity([
            'message_id' => $payload->getMessageId(),
        ]);

        // Set basic message information
        $messageType = $payload->getType() ?: 'unknown';
        $taskMessageEntity->setType($messageType);
        $taskMessageEntity->setContent($payload->getContent() ?? '');
        $taskMessageEntity->setStatus($payload->getStatus() ?: TaskStatus::RUNNING->value);
        $taskMessageEntity->setEvent($payload->getEvent() ?? '');
        $taskMessageEntity->setShowInUi($payload->getShowInUi() ?? true);

        // Set array fields
        $taskMessageEntity->setSteps($payload->getSteps() ?? []);
        $taskMessageEntity->setTool($payload->getTool() ?? []);
        $taskMessageEntity->setAttachments($payload->getAttachments() ?? []);

        // Set task and topic information
        $taskMessageEntity->setTaskId($payload->getTaskId() ?? '');

        // Set sender/receiver information (will be set properly when we have task context)
        $taskMessageEntity->setSenderType('assistant');
        $taskMessageEntity->setSenderUid($metadata->getAgentUserId() ?? '');
        $taskMessageEntity->setReceiverUid($metadata->getUserId() ?? '');
        $taskMessageEntity->setSeqId($messageDTO->getPayload()->getSeqId());
        $taskMessageEntity->setCorrelationId($payload->getCorrelationId());

        // Validate message type
        if (! MessageType::isValid($messageType)) {
            $this->logger->warning(sprintf(
                'Received unknown message type: %s, task_id: %s',
                $messageType,
                $payload->getTaskId()
            ));
        }

        return $taskMessageEntity;
    }

    private function processToolContent(DataIsolation $dataIsolation, TaskEntity $taskEntity, TaskMessageEntity $taskMessageEntity): void
    {
        // Process according to type
        $tool = $taskMessageEntity->getTool();
        $detailType = $tool['detail']['type'] ?? '';
        switch ($detailType) {
            case 'image':
                // Process image
                $this->processToolContentImage($taskMessageEntity);
                break;
            default:
                // Default process text
                $this->processToolContentStorage($dataIsolation, $taskEntity, $taskMessageEntity);
                break;
        }
    }

    private function processToolContentImage(TaskMessageEntity $taskMessageEntity): void
    {
        $tool = $taskMessageEntity->getTool();

        // GetFile name
        $fileName = $tool['detail']['data']['file_name'] ?? '';
        if (empty($fileName)) {
            return;
        }

        $fileKey = '';
        $attachments = $tool['attachments'] ?? [];
        foreach ($attachments as $attachment) {
            if ($attachment['filename'] === $fileName) {
                $fileKey = $attachment['file_key'];
                break; // Exit loop once found
            }
        }

        if (empty($fileKey)) {
            return;
        }

        $taskFileEntity = $this->taskFileDomainService->getByFileKey($fileKey);
        if ($taskFileEntity === null) {
            return;
        }

        // Extract source_file_id using the helper method
        $sourceFileId = $this->extractSourceFileIdFromAttachments(
            $attachments,
            $fileName,
            false // Image files typically don't have .diff suffix
        );

        $tool['detail']['data']['file_id'] = (string) $taskFileEntity->getFileId();
        $tool['detail']['data']['file_extension'] = pathinfo($fileName, PATHINFO_EXTENSION);

        // Add source_file_id if found
        if (! empty($sourceFileId)) {
            $tool['detail']['data']['source_file_id'] = $sourceFileId;
        }

        $taskMessageEntity->setTool($tool);
    }

    private function processToolContentStorage(DataIsolation $dataIsolation, TaskEntity $taskEntity, TaskMessageEntity $taskMessageEntity): void
    {
        // Check if object storage is enabled
        $objectStorageEnabled = config('be-delightful.task.tool_message.object_storage_enabled', true);
        if (! $objectStorageEnabled) {
            return;
        }

        // CheckToolContent
        $tool = $taskMessageEntity->getTool();
        $content = $tool['detail']['data']['content'] ?? '';
        $fileName = $tool['detail']['data']['file_name'] ?? 'tool_content.txt';

        // Extract source_file_id from attachments (regardless of content)
        $sourceFileId = $this->extractSourceFileIdFromAttachments(
            $tool['attachments'] ?? [],
            $fileName,
            true // Remove .diff suffix for matching
        );

        // Set source_file_id if available
        if (! empty($sourceFileId) && ! isset($tool['detail']['data']['source_file_id'])) {
            $tool['detail']['data']['source_file_id'] = $sourceFileId;
            $taskMessageEntity->setTool($tool);
        }

        // If content is empty, return early
        if (empty($content)) {
            return;
        }

        // CheckContent lengthWhether threshold is reached
        $minContentLength = config('be-delightful.task.tool_message.min_content_length', 500);
        if (strlen($content) < $minContentLength) {
            return;
        }

        $this->logger->info(sprintf(
            'Start processing tool content storage, ToolID: %s, Content length: %d',
            $tool['id'] ?? 'unknown',
            strlen($content)
        ));

        try {
            // Build parameters
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'txt';
            $fileKey = ($tool['id'] ?? 'unknown') . '.' . $fileExtension;
            $workDir = WorkDirectoryUtil::getTopicMessageDir($taskEntity->getUserId(), $taskEntity->getProjectId(), $taskEntity->getTopicId());

            // Call FileProcessAppService to save content
            $fileId = $this->fileProcessAppService->saveToolMessageContent(
                fileName: $fileName,
                workDir: $workDir,
                fileKey: $fileKey,
                content: $content,
                dataIsolation: $dataIsolation,
                projectId: $taskEntity->getProjectId(),
                topicId: $taskEntity->getTopicId(),
                taskId: (int) $taskEntity->getId()
            );

            // ModifyToolData structure
            $tool['detail']['data']['file_id'] = (string) $fileId;
            $tool['detail']['data']['content'] = ''; // Clear content
            if (! empty($sourceFileId)) {
                $tool['detail']['data']['source_file_id'] = $sourceFileId;
            }

            $taskMessageEntity->setTool($tool);

            $this->logger->info(sprintf(
                'Tool content storage complete, ToolID: %s, FileID: %d, Original content length: %d, source_file_id: %s',
                $tool['id'] ?? 'unknown',
                $fileId,
                strlen($content),
                $sourceFileId ?: 'null'
            ));
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Tool content storage failed: %s, ToolID: %s, Content length: %d',
                $e->getMessage(),
                $tool['id'] ?? 'unknown',
                strlen($content)
            ));
            // Storage failure does not affect main flow, only record error
        }
    }

    /**
     * Dispatch callback event.
     */
    private function dispatchCallbackEvent(
        TopicTaskMessageDTO $messageDTO,
        DataIsolation $dataIsolation,
        int $topicId,
        TaskEntity $taskEntity
    ): void {
        $this->logger->info('Dispatch RunTaskCallbackEvent event', [
            'topic_id' => $topicId,
            'task_id' => $taskEntity->getId(),
            'message_id' => $messageDTO->getPayload()->getMessageId(),
            'organization_code' => $dataIsolation->getCurrentOrganizationCode(),
            'user_id' => $dataIsolation->getCurrentUserId(),
        ]);
        // Dispatch RunTaskCallbackEvent
        AsyncEventUtil::dispatch(new RunTaskCallbackEvent(
            $dataIsolation->getCurrentOrganizationCode(),
            $dataIsolation->getCurrentUserId(),
            $topicId,
            '', // Pass empty string for topic name
            $taskEntity->getId(),
            $messageDTO,
            $messageDTO->getMetadata()->getLanguage()
        ));
    }

    /**
     * Extract source_file_id from attachments by matching filename.
     *
     * @param array $attachments Tool attachments array
     * @param string $fileName File name to match
     * @param bool $removeDiffSuffix Whether to remove .diff suffix for matching (default: true)
     * @return string Returns source_file_id if found, empty string otherwise
     */
    private function extractSourceFileIdFromAttachments(
        array $attachments,
        string $fileName,
        bool $removeDiffSuffix = true
    ): string {
        if (empty($attachments) || empty($fileName)) {
            return '';
        }

        // Prepare match filename
        $matchFileName = $fileName;
        if ($removeDiffSuffix && str_ends_with($fileName, '.diff')) {
            $matchFileName = substr($fileName, 0, -5); // Remove '.diff' suffix
        }

        // Find matching attachment
        foreach ($attachments as $attachment) {
            if (isset($attachment['filename']) && $attachment['filename'] === $matchFileName) {
                return $attachment['file_id'] ?? '';
            }
        }

        return '';
    }
}

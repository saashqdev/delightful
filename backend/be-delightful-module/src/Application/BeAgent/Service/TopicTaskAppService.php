<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\Magicuser DomainService;
use App\Domain\SuperAgent\Service\UsageCalculator\UsageCalculatorInterface;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Dtyq\AsyncEvent\AsyncEventUtil;
use Delightful\BeDelightful\Application\SuperAgent\Event\Publish\TopicMessageprocess Publisher;
use Delightful\BeDelightful\Domain\SuperAgent\Constant\AgentConstant;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MessageType;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Event\RunTaskAfterEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\RunTaskCallbackEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\TopicMessageprocess Event;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TaskMessageModel;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\SandboxDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskMessageDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\SandboxStatus;
use Delightful\BeDelightful\Infrastructure\Utils\TaskStatusValidator;
use Delightful\BeDelightful\Infrastructure\Utils\tool process or;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Delightful\BeDelightful\Infrastructure\Utils\WorkFileUtil;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\DeliverMessageResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\TopicTaskMessageDTO;
use Hyperf\Amqp\Producer;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class TopicTaskAppService extends AbstractAppService 
{
 
    private readonly LoggerInterface $logger; 
    public function __construct( 
    private readonly Fileprocess AppService $fileprocess AppService, 
    private readonly ClientMessageAppService $clientMessageAppService, 
    private readonly ProjectDomainService $projectDomainService, 
    private readonly TopicDomainService $topicDomainService, 
    private readonly TaskDomainService $taskDomainService, 
    private readonly TaskFileDomainService $taskFileDomainService, 
    private readonly TaskMessageDomainService $taskMessageDomainService, 
    private readonly SandboxDomainService $sandboxDomainService, 
    protected Magicuser DomainService $userDomainService, 
    protected LockerInterface $locker, 
    protected LoggerFactory $loggerFactory, 
    protected TranslatorInterface $translator, 
    protected UsageCalculatorInterface $usageCalculator, ) 
{
 $this->logger = $this->loggerFactory->get(get_class($this)); 
}
 /** * Deliver topic task message. * * @return array Operation result */ 
    public function deliverTopicTaskMessage(TopicTaskMessageDTO $messageDTO): array 
{
 // Getcurrent Task id $taskId = $messageDTO->getMetadata()->getBeDelightfulTaskId(); $taskEntity = $this->taskDomainService->getTaskById((int) $taskId); if (! $taskEntity) 
{
 $this->logger->warning('Invalidtask_idcannot process Message', ['messageData' => $taskId]); ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'message_missing_task_id'); 
}
 // Getsandbox_id $sandboxId = $messageDTO->getMetadata()->getSandboxId(); $metadata = $messageDTO->getMetadata(); $language = $this->translator->getLocale(); $metadata->setLanguage($language); $messageDTO->setMetadata($metadata); $messageId = $messageDTO->getPayload()->getMessageId(); $seqId = $messageDTO->getPayload()->getSeqId(); $this->logger->info('Start processing topic task message delivery', [ 'sandbox_id' => $sandboxId, 'message_id' => $messageDTO->getPayload()?->getMessageId(), ]); $lockKey = 'deliver_sandbox_message_lock:' . $sandboxId; $lockowner = IdGenerator::getUniqueId32(); // Use unique ID as lock holder identifier $lockExpireSeconds = 10; // Lock expiration time (seconds) to prevent deadlock $lockAcquired = false; try 
{
 // Attempt to acquire distributed mutex lock $lockAcquired = $this->locker->spinLock($lockKey, $lockowner , $lockExpireSeconds); if ($lockAcquired) 
{
 // 1. According tosandbox_idGettopic_id $topicEntity = $this->topicDomainService->getTopicBySandboxId($sandboxId); if (! $topicEntity) 
{
 $this->logger->error('Topic corresponding to sandbox_id not found', ['sandbox_id' => $sandboxId]); ExceptionBuilder::throw(GenericErrorCode::SystemError, 'topic_not_found_by_sandbox_id'); 
}
 // Determine seq_id whether yes Value $exceptedSeqId = $this->taskMessageDomainService->getNextSeqId($topicEntity->getId(), $taskEntity->getId()); if ($seqId !== $exceptedSeqId) 
{
 $this->logger->error('seq_id is not expected value', ['seq_id' => $seqId, 'expected_seq_id' => $exceptedSeqId]); 
}
 $topicId = $topicEntity->getId(); // 2. complete DTO to entity conversion at application layer // Get message ID (prefer message ID from payload, generate new one if none) $messageId = $messageDTO->getPayload()?->getMessageId() ?: IdGenerator::getSnowId(); $dataIsolation = DataIsolation::simpleMake($topicEntity->getuser OrganizationCode(), $topicEntity->getuser Id()); $aiuser Entity = $this->userDomainService->getByAiCode($dataIsolation, AgentConstant::SUPER_MAGIC_CODE); $messageEntity = $messageDTO->toTaskMessageEntity($topicId, $aiuser Entity->getuser Id(), $topicEntity->getuser Id()); // 3. MessageDatabasecall Service $this->taskMessageDomainService->storeTopicTaskMessage($messageEntity, $messageDTO->toArray()); // 4. Publishedprocess Event $processEvent = new TopicMessageprocess Event($topicId, $taskEntity->getId()); $processPublisher = new TopicMessageprocess Publisher($processEvent); $producer = di(Producer::class); $result = $producer->produce($processPublisher); if (! $result) 
{
 $this->logger->error('PublishedMessageprocess EventFailed', [ 'topic_id' => $topicId, 'sandbox_id' => $sandboxId, 'message_id' => $messageDTO->getPayload()?->getMessageId(), ]); ExceptionBuilder::throw(GenericErrorCode::SystemError, 'message_process_event_publish_failed'); 
}
 $this->logger->info('Topic task message delivery completed', [ 'topic_id' => $topicId, 'sandbox_id' => $sandboxId, 'message_id' => $messageDTO->getPayload()?->getMessageId(), ]); 
}
 
}
 catch (Throwable $e) 
{
 $this->logger->error('Topic task message delivery failed', [ 'sandbox_id' => $sandboxId, 'message_id' => $messageDTO->getPayload()?->getMessageId(), 'error' => $e->getMessage(), ]); throw $e; 
}
 finally 
{
 if ($lockAcquired) 
{
 if ($this->locker->release($lockKey, $lockowner )) 
{
 $this->logger->debug(sprintf('Lock released for sandbox %s by %s', $sandboxId, $lockowner )); 
}
 else 
{
 // Log lock release failure, may require manual intervention $this->logger->error(sprintf('Failed to release lock for sandbox %s held by %s. Manual intervention may be required.', $sandboxId, $lockowner )); 
}
 
}
 
}
 return DeliverMessageResponseDTO::fromResult(true, $messageId)->toArray(); 
}
 
    public function handleTopicTaskMessage(TopicTaskMessageDTO $messageDTO): array 
{
 // 1InitializeData $taskId = $messageDTO->getMetadata()->getBeDelightfulTaskId(); $taskEntity = $this->taskDomainService->getTaskById((int) $taskId); if (! $taskEntity) 
{
 $this->logger->warning('Invalidtask_idcannot process Message', ['messageData' => $taskId]); ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'message_missing_task_id'); 
}
 $metadata = $messageDTO->getMetadata(); $language = $this->translator->getLocale(); $metadata->setLanguage($language); $messageDTO->setMetadata($metadata); $dataIsolation = DataIsolation::simpleMake($metadata->getOrganizationCode(), $metadata->getuser Id()); $messageId = $messageDTO->getPayload()->getMessageId(); $topicId = $taskEntity->getTopicId(); $this->logger->info('Startprocess topic TaskMessage', [ 'topic_id' => $topicId, 'task_id' => $taskId, 'message_id' => $messageId, ]); // Gettopic LevelLock $lockKey = 'handle_topic_message_lock:' . $topicId; $lockowner = IdGenerator::getUniqueId32(); $lockExpireSeconds = 15; // LockExpiration time $lockAcquired = false; try 
{
 // try GetLock $lockAcquired = $this->locker->spinLock($lockKey, $lockowner , $lockExpireSeconds); if (! $lockAcquired) 
{
 $this->logger->warning('cannot Gettopic LockMessageprocess Skip', [ 'topic_id' => $topicId, 'message_id' => $messageId, ]); ExceptionBuilder::throw(GenericErrorCode::SystemError, 'unable_to_acquire_topic_lock'); 
}
 $status = $messageDTO->getPayload()->getStatus(); $taskStatus = TaskStatus::tryFrom($status) ?? TaskStatus::ERROR; $usage = []; if ($taskStatus->isFinal()) 
{
 // Calculate usage information when task is finished $usage = $this->usageCalculator->calculateUsage((int) $taskId); 
}
 // 2Message $messageEntity = $this->taskMessageDomainService->findByTopicIdAndMessageId($topicId, $messageId); if (is_null($messageEntity)) 
{
 // Create new message $messageEntity = $this->parseMessageContent($messageDTO); $messageEntity->setTopicId($topicId); $this->processMessageAttachment($dataIsolation, $taskEntity, $messageEntity); $this->processtool Content($dataIsolation, $taskEntity, $messageEntity); // Set usage if task is finished if (! empty($usage)) 
{
 $messageEntity->setUsage($usage); 
}
 $this->taskMessageDomainService->storeTopicTaskMessage($messageEntity, [], TaskMessageModel::PROCESSING_STATUS_COMPLETED); 
}
 // 3. PushMessagegive ClientAsyncprocess  if ($messageEntity->getShowInUi()) 
{
 $this->clientMessageAppService->sendMessageToClient( messageId: $messageEntity->getId(), topicId: $topicId, taskId: (string) $taskEntity->getId(), chatTopicId: $metadata->getChatTopicId(), chatConversationId: $metadata->getChatConversationId(), content: $messageEntity->getContent(), messageType: $messageEntity->getType(), status: $messageEntity->getStatus(), event: $messageEntity->getEvent(), steps: $messageEntity->getSteps() ?? [], tool: $messageEntity->gettool () ?? [], attachments: $messageEntity->getAttachments() ?? [], correlationId: $messageDTO->getPayload()->getCorrelationId() ?? null, usage: ! empty($usage) ? $usage : null, ); 
}
 // 4. Updatetopic Status if (TaskStatus::tryFrom($status)) 
{
 $this->updateTaskStatus( dataIsolation: $dataIsolation, task: $taskEntity, status: $taskStatus, errMsg: '' ); 
}
 // 5. Event - only AtTaskcomplete or Failedtrigger Exclude Stopped Status if ($taskStatus->isFinal()) 
{
 $this->dispatchCallbackEvent($messageDTO, $dataIsolation, $topicId, $taskEntity); 
}
 $this->logger->info('topic TaskMessageprocess complete ', [ 'topic_id' => $topicId, 'task_id' => $taskId, 'message_id' => $messageId, ]); 
}
 catch (Throwable $e) 
{
 $this->logger->error('topic TaskMessageprocess ing failed', [ 'topic_id' => $topicId, 'task_id' => $taskId, 'message_id' => $messageId, 'error' => $e->getMessage(), ]); throw $e; 
}
 finally 
{
 if ($lockAcquired) 
{
 if ($this->locker->release($lockKey, $lockowner )) 
{
 $this->logger->debug(sprintf('Releasetopic Lock topic_id: %d, owner: %s', $topicId, $lockowner )); 
}
 else 
{
 $this->logger->error(sprintf('Release topic lock failed topic_id: %d, owner: %s, may need manual intervention', $topicId, $lockowner )); 
}
 
}
 
}
 return DeliverMessageResponseDTO::fromResult(true, $messageId)->toArray(); 
}
 /** * Update task status. */ 
    public function updateTaskStatus(DataIsolation $dataIsolation, TaskEntity $task, TaskStatus $status, string $errMsg = ''): void 
{
 $taskId = (string) $task?->getId(); try 
{
 // Get current task status for validation $currentStatus = $task?->getStatus(); // Use utility

class to validate status transition if (! TaskStatusValidator::isTransitionAllowed($currentStatus, $status)) 
{
 $reason = TaskStatusValidator::getRejectReason($currentStatus, $status); $this->logger->info('Rejected status update', [ 'task_id' => $taskId, 'current_status' => $currentStatus->value ?? 'null', 'new_status' => $status->value, 'reason' => $reason, 'error_msg' => $errMsg, ]); return; // Silently reject update 
}
 // execute status update $this->taskDomainService->updateTaskStatus( $status, $task->getId(), $taskId, $task->getSandboxId(), $errMsg ); $this->topicDomainService->updateTopicStatusAndSandboxId($task->getTopicId(), $task->getId(), $status, $task->getSandboxId()); $this->projectDomainService->updateProjectStatus($task->getProjectId(), $task->getTopicId(), $status); // Log success $this->logger->info('Task status update completed', [ 'task_id' => $taskId, 'sandbox_id' => $task->getSandboxId(), 'previous_status' => $currentStatus->value ?? 'null', 'new_status' => $status->value, 'error_msg' => $errMsg, ]); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to update task status', [ 'task_id' => $taskId, 'sandbox_id' => $task->getSandboxId(), 'status' => $status->value, 'error' => $e->getMessage(), 'error_msg' => $errMsg, ]); throw $e; 
}
 
}
 
    public function updateTaskStatusFromSandbox(TopicEntity $topicEntity): TaskStatus 
{
 $this->logger->info(sprintf('Startcheck TaskStatus: topic_id=%s', $topicEntity->getId())); $sandboxId = ! empty($topicEntity->getSandboxId()) ? $topicEntity->getSandboxId() : (string) $topicEntity->getId(); // call SandboxServicegetStatusInterfaceGetincluding erStatus $result = $this->sandboxDomainService->getSandboxStatus($sandboxId); // Ifsandbox Existand Statusas runningdirectly Return sandbox if ($result->getStatus() === SandboxStatus::RUNNING) 
{
 $this->logger->info(sprintf('Sandbox statusNormal(running): sandboxId=%s', $topicEntity->getSandboxId())); return TaskStatus::RUNNING; 
}
 $errMsg = $result->getStatus(); // Getcurrent Task $taskId = $topicEntity->getcurrent TaskId(); if ($taskId) 
{
 // UpdateTaskStatus $this->taskDomainService->updateTaskStatusByTaskId($taskId, TaskStatus::ERROR, $errMsg); 
}
 // Updatetopic Status $this->topicDomainService->updateTopicStatus($topicEntity->getId(), $taskId, TaskStatus::ERROR); // trigger complete Event AsyncEventUtil::dispatch(new RunTaskAfterEvent( $topicEntity->getuser OrganizationCode(), $topicEntity->getuser Id(), $topicEntity->getId(), $taskId, TaskStatus::ERROR->value, null )); $this->logger->info(sprintf('Endcheck TaskStatus: topic_id=%s, status=%s, error_msg=%s', $topicEntity->getId(), TaskStatus::ERROR->value, $errMsg)); return TaskStatus::ERROR; 
}
 
    public function processMessageAttachment(DataIsolation $dataIsolation, TaskEntity $task, TaskMessageEntity $message): void 
{
 $fileKeys = []; $fileinfo Map = []; // GetMessage if (! empty($message->getAttachments())) 
{
 foreach ($message->getAttachments() as $attachment) 
{
 if (! empty($attachment['file_key'])) 
{
 $fileKeys[] = $attachment['file_key']; $fileinfo Map[$attachment['file_key']] = $attachment; 
}
 
}
 
}
 // GetMessagetool if (! empty($message->gettool ()) && ! empty($message->gettool ()['attachments'])) 
{
 foreach ($message->gettool ()['attachments'] as $attachment) 
{
 if (! empty($attachment['file_key'])) 
{
 $fileKeys[] = $attachment['file_key']; $fileinfo Map[$attachment['file_key']] = $attachment; 
}
 
}
 
}
 if (empty($fileKeys)) 
{
 return; 
}
 // Through file_key FindFile id $fileEntities = $this->taskFileDomainService->getByFileKeys($fileKeys); $fileIdMap = []; foreach ($fileEntities as $fileEntity) 
{
 $fileIdMap[$fileEntity->getFileKey()] = $fileEntity->getFileId(); 
}
 // Handle missing file_keys: find which file_keys don't have corresponding records $missingFileKeys = array_diff($fileKeys, array_keys($fileIdMap)); if (! empty($missingFileKeys)) 
{
 $projectEntity = $this->projectDomainService->getProjectNotuser Id($task->getProjectId()); if ($projectEntity) 
{
 // process each missing file with file-level locking for better concurrency foreach ($missingFileKeys as $missingFileKey) 
{
 if (! isset($fileinfo Map[$missingFileKey])) 
{
 $this->logger->error(sprintf('Missing file_key: %s', $missingFileKey)); continue; 
}
 // Setup file-level lock to prevent concurrent processing of the same file $lockKey = WorkDirectoryUtil::getFileLockerKey($missingFileKey, 'topic_message_attachment'); $lockowner = IdGenerator::getUniqueId32(); $lockExpireSeconds = 2; $lockAcquired = false; try 
{
 // Attempt to acquire distributed file-level lock $lockAcquired = $this->locker->spinLock($lockKey, $lockowner , $lockExpireSeconds); if (! $lockAcquired) 
{
 $this->logger->warning(sprintf( 'Failed to acquire file lock for missing file processing, file_key: %s, task_id: %s', $missingFileKey, $task->getTaskId() )); continue; 
}
 // Re-check file existence after acquiring lock to avoid duplicate creation $existingFile = $this->taskFileDomainService->getByFileKey($missingFileKey); if ($existingFile !== null) 
{
 $fileIdMap[$missingFileKey] = $existingFile->getFileId(); $this->logger->info(sprintf( 'File already created by another process, file_key: %s, file_id: %d', $missingFileKey, $existingFile->getFileId() )); continue; 
}
 // Create file record $storageType = WorkFileUtil::isSnapshotFile($missingFileKey) ? StorageType::SNAPSHOT->value : StorageType::WORKSPACE->value; $fileinfo Map[$missingFileKey]['storage_type'] = $storageType; $fallbackFileEntity = $this->taskFileDomainService->convertMessageAttachmentToTaskFileEntity($fileinfo Map[$missingFileKey], $task); $this->logger->info(sprintf( 'Attachment not found in database, creating file record, task_id: %s, file_key: %s', $task->getTaskId(), $missingFileKey )); $savedEntity = $this->taskFileDomainService->saveProjectFile( $dataIsolation, $projectEntity, $fallbackFileEntity, $storageType ); $fileIdMap[$missingFileKey] = $savedEntity->getFileId(); $this->logger->info(sprintf( 'Missing file processed successfully with file lock, file_key: %s, file_id: %d, task_id: %s', $missingFileKey, $savedEntity->getFileId(), $task->getTaskId() )); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'Exception processing missing file with lock protection, file_key: %s, task_id: %s, error: %s', $missingFileKey, $task->getTaskId(), $e->getMessage() )); // Continue processing other files instead of throwing 
}
 finally 
{
 // Ensure file-level lock is always released if ($lockAcquired) 
{
 if (! $this->locker->release($lockKey, $lockowner )) 
{
 $this->logger->error(sprintf( 'Failed to release file lock for missing file processing, file_key: %s, task_id: %s', $missingFileKey, $task->getTaskId() )); 
}
 
}
 
}
 
}
 
}
 else 
{
 $this->logger->error(sprintf('Project not found, project_id: %s', $task->getProjectId())); 
}
 
}
 // file_id Value MessageMessagetool if (! empty($fileIdMap)) 
{
 // process Message $attachments = $message->getAttachments(); if (! empty($attachments)) 
{
 foreach ($attachments as &$attachment) 
{
 if (! empty($attachment['file_key']) && isset($fileIdMap[$attachment['file_key']])) 
{
 $attachment['file_id'] = (string) $fileIdMap[$attachment['file_key']]; 
}
 
}
 $message->setAttachments($attachments); 
}
 // process tool $tool = $message->gettool (); if (! empty($tool) && ! empty($tool['attachments'])) 
{
 foreach ($tool['attachments'] as &$attachment) 
{
 if (! empty($attachment['file_key']) && isset($fileIdMap[$attachment['file_key']])) 
{
 $attachment['file_id'] = (string) $fileIdMap[$attachment['file_key']]; 
}
 
}
 $message->settool ($tool); 
}
 
}
 // Special status handling: generate output content tool when task is finished if ($message->getStatus() === TaskStatus::FINISHED->value) 
{
 $outputtool = tool process or::generateOutputContenttool ($message->getAttachments()); if ($outputtool !== null) 
{
 $message->settool ($outputtool ); 
}
 
}
 
}
 
    private function parseMessageContent(TopicTaskMessageDTO $messageDTO): TaskMessageEntity 
{
 $payload = $messageDTO->getPayload(); $metadata = $messageDTO->getMetadata(); // Create TaskMessageEntity with messageId $taskMessageEntity = new TaskMessageEntity([ 'message_id' => $payload->getMessageId(), ]); // Set basic message information $messageType = $payload->getType() ?: 'unknown'; $taskMessageEntity->setType($messageType); $taskMessageEntity->setContent($payload->getContent() ?? ''); $taskMessageEntity->setStatus($payload->getStatus() ?: TaskStatus::RUNNING->value); $taskMessageEntity->setEvent($payload->getEvent() ?? ''); $taskMessageEntity->setShowInUi($payload->getShowInUi() ?? true); // Set array fields $taskMessageEntity->setSteps($payload->getSteps() ?? []); $taskMessageEntity->settool ($payload->gettool () ?? []); $taskMessageEntity->setAttachments($payload->getAttachments() ?? []); // Set task and topic information $taskMessageEntity->setTaskId($payload->getTaskId() ?? ''); // Set sender/receiver information (will be set properly when we have task context) $taskMessageEntity->setSenderType('assistant'); $taskMessageEntity->setSenderUid($metadata->getAgentuser Id() ?? ''); $taskMessageEntity->setReceiverUid($metadata->getuser Id() ?? ''); $taskMessageEntity->setSeqId($messageDTO->getPayload()->getSeqId()); $taskMessageEntity->setCorrelationId($payload->getCorrelationId()); // Validate message type if (! MessageType::isValid($messageType)) 
{
 $this->logger->warning(sprintf( 'Received unknown message type: %s, task_id: %s', $messageType, $payload->getTaskId() )); 
}
 return $taskMessageEntity; 
}
 
    private function processtool Content(DataIsolation $dataIsolation, TaskEntity $taskEntity, TaskMessageEntity $taskMessageEntity): void 
{
 // According toTypeprocess $tool = $taskMessageEntity->gettool (); $detailType = $tool['detail']['type'] ?? ''; switch ($detailType) 
{
 case 'image': // process Image $this->processtool ContentImage($taskMessageEntity); break; default: // Defaultprocess Text $this->processtool ContentStorage($dataIsolation, $taskEntity, $taskMessageEntity); break; 
}
 
}
 
    private function processtool ContentImage(TaskMessageEntity $taskMessageEntity): void 
{
 $tool = $taskMessageEntity->gettool (); // GetFileName $fileName = $tool['detail']['data']['file_name'] ?? ''; if (empty($fileName)) 
{
 return; 
}
 $fileKey = ''; $attachments = $tool['attachments'] ?? []; foreach ($attachments as $attachment) 
{
 if ($attachment['filename'] === $fileName) 
{
 $fileKey = $attachment['file_key']; break; // Exit loop once found 
}
 
}
 if (empty($fileKey)) 
{
 return; 
}
 $taskFileEntity = $this->taskFileDomainService->getByFileKey($fileKey); if ($taskFileEntity === null) 
{
 return; 
}
 // Extract source_file_id using the helper method $sourceFileId = $this->extractSourceFileIdFromAttachments( $attachments, $fileName, false // Image files typically don't have .diff suffix ); $tool['detail']['data']['file_id'] = (string) $taskFileEntity->getFileId(); $tool['detail']['data']['file_extension'] = pathinfo($fileName, PATHINFO_EXTENSION); // Add source_file_id if found if (! empty($sourceFileId)) 
{
 $tool['detail']['data']['source_file_id'] = $sourceFileId; 
}
 $taskMessageEntity->settool ($tool); 
}
 
    private function processtool ContentStorage(DataIsolation $dataIsolation, TaskEntity $taskEntity, TaskMessageEntity $taskMessageEntity): void 
{
 // check whether EnabledObject $objectStorageEnabled = config('super-magic.task.tool_message.object_storage_enabled', true); if (! $objectStorageEnabled) 
{
 return; 
}
 // check tool Content $tool = $taskMessageEntity->gettool (); $content = $tool['detail']['data']['content'] ?? ''; $fileName = $tool['detail']['data']['file_name'] ?? 'tool_content.txt'; // Extract source_file_id from attachments (regardless of content) $sourceFileId = $this->extractSourceFileIdFromAttachments( $tool['attachments'] ?? [], $fileName, true // Remove .diff suffix for matching ); // Set source_file_id if available if (! empty($sourceFileId) && ! isset($tool['detail']['data']['source_file_id'])) 
{
 $tool['detail']['data']['source_file_id'] = $sourceFileId; $taskMessageEntity->settool ($tool); 
}
 // If content is empty, return early if (empty($content)) 
{
 return; 
}
 // check ContentLengthwhether ReachThreshold $minContentLength = config('super-magic.task.tool_message.min_content_length', 500); if (strlen($content) < $minContentLength) 
{
 return; 
}
 $this->logger->info(sprintf( 'Startprocess tool Contenttool ID: %sContentLength: %d', $tool['id'] ?? 'unknown', strlen($content) )); try 
{
 // BuildParameter $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'txt'; $fileKey = ($tool['id'] ?? 'unknown') . '.' . $fileExtension; $workDir = WorkDirectoryUtil::getTopicMessageDir($taskEntity->getuser Id(), $taskEntity->getProjectId(), $taskEntity->getTopicId()); // call Fileprocess AppServiceSaveContent $fileId = $this->fileprocess AppService->savetool MessageContent( fileName: $fileName, workDir: $workDir, fileKey: $fileKey, content: $content, dataIsolation: $dataIsolation, projectId: $taskEntity->getProjectId(), topicId: $taskEntity->getTopicId(), taskId: (int) $taskEntity->getId() ); // Modifytool DataStructure $tool['detail']['data']['file_id'] = (string) $fileId; $tool['detail']['data']['content'] = ''; // ClearContent if (! empty($sourceFileId)) 
{
 $tool['detail']['data']['source_file_id'] = $sourceFileId; 
}
 $taskMessageEntity->settool ($tool); $this->logger->info(sprintf( 'tool Contentcomplete tool ID: %sFileID: %dContentLength: %dsource_file_id: %s', $tool['id'] ?? 'unknown', $fileId, strlen($content), $sourceFileId ?: 'null' )); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'tool ContentFailed: %stool ID: %sContentLength: %d', $e->getMessage(), $tool['id'] ?? 'unknown', strlen($content) )); // FailedMainrecord Error 
}
 
}
 /** * Event. */ 
    private function dispatchCallbackEvent( TopicTaskMessageDTO $messageDTO, DataIsolation $dataIsolation, int $topicId, TaskEntity $taskEntity ): void 
{
 $this->logger->info('Dispatch RunTaskCallbackEvent event', [ 'topic_id' => $topicId, 'task_id' => $taskEntity->getId(), 'message_id' => $messageDTO->getPayload()->getMessageId(), 'organization_code' => $dataIsolation->getcurrent OrganizationCode(), 'user_id' => $dataIsolation->getcurrent user Id(), ]); // RunTaskCallbackEvent AsyncEventUtil::dispatch(new RunTaskCallbackEvent( $dataIsolation->getcurrent OrganizationCode(), $dataIsolation->getcurrent user Id(), $topicId, '', // topic NameEmptyString $taskEntity->getId(), $messageDTO, $messageDTO->getMetadata()->getLanguage() )); 
}
 /** * Extract source_file_id from attachments by matching filename. * * @param array $attachments tool attachments array * @param string $fileName File name to match * @param bool $removeDiffSuffix whether to remove .diff suffix for matching (default: true) * @return string Returns source_file_id if found, empty string otherwise */ 
    private function extractSourceFileIdFromAttachments( array $attachments, string $fileName, bool $removeDiffSuffix = true ): string 
{
 if (empty($attachments) || empty($fileName)) 
{
 return ''; 
}
 // Prepare match filename $matchFileName = $fileName; if ($removeDiffSuffix && str_ends_with($fileName, '.diff')) 
{
 $matchFileName = substr($fileName, 0, -5); // Remove '.diff' suffix 
}
 // Find matching attachment foreach ($attachments as $attachment) 
{
 if (isset($attachment['filename']) && $attachment['filename'] === $matchFileName) 
{
 return $attachment['file_id'] ?? ''; 
}
 
}
 return ''; 
}
 
}
 

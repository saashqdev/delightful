<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Service;

use App\Application\Chat\Service\DelightfulChatMessageAppService;
use App\Application\Speech\Assembler\AsrAssembler;
use App\Application\Speech\Assembler\ChatMessageAssembler;
use App\Application\Speech\DTO\AsrTaskStatusDTO;
use App\Application\Speech\DTO\ProcessSummaryTaskDTO;
use App\Application\Speech\DTO\Response\AsrFileDataDTO;
use App\Application\Speech\DTO\SummaryRequestDTO;
use App\Application\Speech\Enum\AsrRecordingStatusEnum;
use App\Application\Speech\Enum\AsrTaskStatusEnum;
use App\Domain\Asr\Constants\AsrConfig;
use App\Domain\Asr\Constants\AsrRedisKeys;
use App\Domain\Asr\Service\AsrTaskDomainService;
use App\Domain\Chat\DTO\Request\ChatRequest;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Service\DelightfulChatDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\ErrorCode\AsrErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus as SuperAgentTaskStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Service\MessageQueueDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\WorkspaceDomainService;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Engine\Coroutine;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * ASR file management application service responsible for core ASR orchestration.
 */
readonly class AsrFileAppService
{
    private LoggerInterface $logger;

    public function __construct(
        private ProjectDomainService $projectDomainService,
        private TaskFileDomainService $taskFileDomainService,
        private WorkspaceDomainService $workspaceDomainService,
        private DelightfulUserDomainService $magicUserDomainService,
        private ChatMessageAssembler $chatMessageAssembler,
        private DelightfulChatMessageAppService $magicChatMessageAppService,
        private DelightfulChatDomainService $magicChatDomainService,
        private TopicDomainService $superAgentTopicDomainService,
        private MessageQueueDomainService $messageQueueDomainService,
        private TranslatorInterface $translator,
        // Newly injected services
        private AsrTaskDomainService $asrTaskDomainService,
        private AsrValidationService $validationService,
        private AsrDirectoryService $directoryService,
        private AsrTitleGeneratorService $titleGeneratorService,
        private AsrSandboxService $asrSandboxService,
        private AsrPresetFileService $presetFileService,
        private LockerInterface $locker,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get('AsrFileAppService');
    }

    /**
     * Handle the full ASR summary workflow, including chat message delivery.
     */
    public function processSummaryWithChat(
        SummaryRequestDTO $summaryRequest,
        DelightfulUserAuthorization $userAuthorization
    ): array {
        try {
            $userId = $userAuthorization->getId();
            $organizationCode = $userAuthorization->getOrganizationCode();

            // 1. Validate topic and fetch conversation ID
            $topicEntity = $this->validationService->validateTopicOwnership((int) $summaryRequest->topicId, $userId);
            $chatTopicId = $topicEntity->getChatTopicId();
            $conversationId = $this->magicChatDomainService->getConversationIdByTopicId($chatTopicId);

            // 2. Validate task status (skip when a file_id exists)
            if (! $summaryRequest->hasFileId()) {
                $this->validationService->validateTaskStatus($summaryRequest->taskKey, $userId);
            }

            // 3. Validate project access
            $this->validationService->validateProjectAccess($summaryRequest->projectId, $userId, $organizationCode);

            // 4. Fetch project, workspace, and topic info
            [$projectName, $workspaceName] = $this->getProjectAndWorkspaceNames($summaryRequest->projectId);
            $topicName = $topicEntity->getTopicName();

            // 5. Update empty project/topic names when a generated title exists
            if (! empty($summaryRequest->generatedTitle) && $this->shouldUpdateNames($projectName, $topicName)) {
                $this->updateEmptyProjectAndTopicNames(
                    $summaryRequest->projectId,
                    (int) $summaryRequest->topicId,
                    $summaryRequest->generatedTitle,
                    $userId,
                    $organizationCode
                );
                $projectName = empty(trim($projectName)) ? $summaryRequest->generatedTitle : $projectName;
                $topicName = empty(trim($topicName)) ? $summaryRequest->generatedTitle : $topicName;
            }

            // 6. Run recording summary asynchronously
            $this->executeAsyncSummary($summaryRequest, $userAuthorization);

            return [
                'success' => true,
                'task_status' => null,
                'conversation_id' => $conversationId,
                'chat_result' => true,
                'topic_name' => $topicName,
                'project_name' => $projectName,
                'workspace_name' => $workspaceName,
            ];
        } catch (Throwable $e) {
            $this->logger->error('Failed to process ASR summary task', [
                'task_key' => $summaryRequest->taskKey,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'task_status' => null,
                'conversation_id' => null,
                'chat_result' => ['success' => false, 'message_sent' => false, 'error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Handle the ASR summary task asynchronously.
     * @throws Throwable
     */
    public function handleAsrSummary(
        SummaryRequestDTO $summaryRequest,
        string $userId,
        string $organizationCode
    ): void {
        $lockName = sprintf(AsrRedisKeys::SUMMARY_LOCK, $summaryRequest->taskKey);
        $lockOwner = sprintf('%s:%s:%s', $userId, $summaryRequest->taskKey, microtime(true));
        $locked = $this->locker->spinLock($lockName, $lockOwner, AsrConfig::SUMMARY_LOCK_TTL);
        if (! $locked) {
            $this->logger->warning('Failed to acquire summary task lock, skipping this run', [
                'task_key' => $summaryRequest->taskKey,
                'user_id' => $userId,
            ]);
            return;
        }
        try {
            // 1. Prepare task status
            if ($summaryRequest->hasFileId()) {
                $taskStatus = $this->createVirtualTaskStatusFromFileId($summaryRequest, $userId, $organizationCode);
            } else {
                $taskStatus = $this->validationService->validateTaskStatus($summaryRequest->taskKey, $userId);
            }

            // 2. Use topic_id saved in Redis to fetch topic and conversation info
            $topicEntity = $this->validationService->validateTopicOwnership((int) $taskStatus->topicId, $userId);
            $chatTopicId = $topicEntity->getChatTopicId();
            $conversationId = $this->magicChatDomainService->getConversationIdByTopicId($chatTopicId);

            // 3. Continue handling task status
            if (! $summaryRequest->hasFileId()) {
                // 3.1 Idempotency check: if summary is completed, resend chat message only
                if ($taskStatus->isSummaryCompleted()) {
                    $this->logger->info('Summary already completed; skipping repeat processing and resending message only', [
                        'task_key' => $summaryRequest->taskKey,
                        'audio_file_id' => $taskStatus->audioFileId,
                        'status' => $taskStatus->status->value,
                    ]);

                    // Resend chat message only (supports re-summarizing with a different model)
                    $processSummaryTaskDTO = new ProcessSummaryTaskDTO(
                        $taskStatus,
                        $organizationCode,
                        $summaryRequest->projectId,
                        $userId,
                        $taskStatus->topicId,
                        $chatTopicId,
                        $conversationId,
                        $summaryRequest->modelId
                    );
                    $userAuthorization = $this->getUserAuthorizationFromUserId($userId);
                    $this->sendSummaryChatMessage($processSummaryTaskDTO, $userAuthorization);
                    return;
                }

                // 3.2 If recording is not stopped, terminate it first
                if (in_array($taskStatus->recordingStatus, [
                    AsrRecordingStatusEnum::START->value,
                    AsrRecordingStatusEnum::RECORDING->value,
                    AsrRecordingStatusEnum::PAUSED->value,
                ], true)) {
                    $this->logger->info('Summary triggers recording termination', [
                        'task_key' => $summaryRequest->taskKey,
                        'old_status' => $taskStatus->recordingStatus,
                    ]);
                    $taskStatus->recordingStatus = AsrRecordingStatusEnum::STOPPED->value;
                    $taskStatus->isPaused = false;
                    $this->asrTaskDomainService->saveTaskStatus($taskStatus);
                    $this->asrTaskDomainService->deleteTaskHeartbeat($taskStatus->taskKey, $taskStatus->userId);
                }

                $existingWorkspaceFilePath = $taskStatus->filePath;

                try {
                    // Generate new display directory and update taskStatus to ensure sandbox uses the right path
                    //                    $oldDisplayDirectory = $taskStatus->displayDirectory;
                    if (! empty($summaryRequest->generatedTitle)) {
                        $newDisplayDirectory = $this->directoryService->getNewDisplayDirectory(
                            $taskStatus,
                            $summaryRequest->generatedTitle,
                            $this->titleGeneratorService
                        );
                        $taskStatus->displayDirectory = $newDisplayDirectory;
                    }

                    // Merge audio in sandbox (sandbox renames directories without notifying file changes)
                    $this->updateAudioFromSandbox($taskStatus, $organizationCode, $summaryRequest->generatedTitle);
                } catch (Throwable $mergeException) {
                    // Fall back to existing file
                    if (! empty($existingWorkspaceFilePath)) {
                        $this->logger->warning('Sandbox merge failed; reverting to existing workspace file', [
                            'task_key' => $summaryRequest->taskKey,
                            'file_path' => $existingWorkspaceFilePath,
                            'error' => $mergeException->getMessage(),
                        ]);
                        $taskStatus->filePath = $existingWorkspaceFilePath;
                    } else {
                        throw $mergeException;
                    }
                }
            }

            // 4. Send summary message
            $processSummaryTaskDTO = new ProcessSummaryTaskDTO(
                $taskStatus,
                $organizationCode,
                $summaryRequest->projectId,
                $userId,
                $taskStatus->topicId,
                $chatTopicId,
                $conversationId,
                $summaryRequest->modelId
            );

            $userAuthorization = $this->getUserAuthorizationFromUserId($userId);
            $this->sendSummaryChatMessage($processSummaryTaskDTO, $userAuthorization);

            // 5. Mark task completed (idempotent)
            $taskStatus->updateStatus(AsrTaskStatusEnum::COMPLETED);
            $taskStatus->recordingStatus = AsrRecordingStatusEnum::STOPPED->value;

            // 6. Persist task status
            $this->asrTaskDomainService->saveTaskStatus($taskStatus);

            // 7. Clean up streaming transcript files (no longer needed after summary)
            if (! empty($taskStatus->presetTranscriptFileId)) {
                $this->presetFileService->deleteTranscriptFile($taskStatus->presetTranscriptFileId);
            }

            $this->logger->info('Summary task completed', [
                'task_key' => $summaryRequest->taskKey,
                'audio_file_id' => $taskStatus->audioFileId,
                'status' => $taskStatus->status->value,
            ]);
        } finally {
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * Validate project access permissions.
     */
    public function validateProjectAccess(string $projectId, string $userId, string $organizationCode): ProjectEntity
    {
        return $this->validationService->validateProjectAccess($projectId, $userId, $organizationCode);
    }

    /**
     * Fetch task status from Redis.
     */
    public function getTaskStatusFromRedis(string $taskKey, string $userId): AsrTaskStatusDTO
    {
        $taskStatus = $this->asrTaskDomainService->findTaskByKey($taskKey, $userId);
        return $taskStatus ?? new AsrTaskStatusDTO();
    }

    /**
     * Persist task status to Redis.
     */
    public function saveTaskStatusToRedis(AsrTaskStatusDTO $taskStatus, int $ttl = AsrConfig::TASK_STATUS_TTL): void
    {
        $this->asrTaskDomainService->saveTaskStatus($taskStatus, $ttl);
    }

    /**
     * Prepare recording directories.
     */
    public function prepareRecordingDirectories(
        string $organizationCode,
        string $projectId,
        string $userId,
        string $taskKey,
        ?string $generatedTitle = null
    ): array {
        $hiddenDir = $this->directoryService->createHiddenDirectory($organizationCode, $projectId, $userId, $taskKey);
        $displayDir = $this->directoryService->createDisplayDirectory($organizationCode, $projectId, $userId, $generatedTitle);
        return [$hiddenDir, $displayDir];
    }

    /**
     * Get project ID from topic.
     */
    public function getProjectIdFromTopic(int $topicId, string $userId): string
    {
        return $this->validationService->getProjectIdFromTopic($topicId, $userId);
    }

    /**
     * Validate topic and prepare recording directories.
     */
    public function validateTopicAndPrepareDirectories(
        string $topicId,
        string $projectId,
        string $userId,
        string $organizationCode,
        string $taskKey,
        ?string $generatedTitle = null
    ): array {
        // Validate topic ownership and project access
        $this->validationService->validateTopicOwnership((int) $topicId, $userId);
        $this->validationService->validateProjectAccess($projectId, $userId, $organizationCode);

        // Prepare recording directories
        return $this->prepareRecordingDirectories($organizationCode, $projectId, $userId, $taskKey, $generatedTitle);
    }

    /**
     * Handle recording status reports.
     */
    public function handleStatusReport(
        string $taskKey,
        AsrRecordingStatusEnum $status,
        string $modelId,
        string $asrStreamContent,
        ?string $noteContent,
        ?string $noteFileType,
        string $language,
        string $userId,
        string $organizationCode
    ): bool {
        $taskStatus = $this->getTaskStatusFromRedis($taskKey, $userId);

        if ($taskStatus->isEmpty()) {
            ExceptionBuilder::throw(AsrErrorCode::TaskNotExist);
        }

        // Save model_id, ASR content, note content, and language
        $this->updateTaskStatusFromReport($taskStatus, $modelId, $asrStreamContent, $noteContent, $noteFileType, $language);

        // Dispatch based on status
        return match ($status) {
            AsrRecordingStatusEnum::START => $this->handleStartRecording($taskStatus, $userId, $organizationCode),
            AsrRecordingStatusEnum::RECORDING => $this->handleRecordingHeartbeat($taskStatus),
            AsrRecordingStatusEnum::PAUSED => $this->handlePauseRecording($taskStatus),
            AsrRecordingStatusEnum::STOPPED => $this->handleStopRecording($taskStatus),
            AsrRecordingStatusEnum::CANCELED => $this->handleCancelRecording($taskStatus),
        };
    }

    /**
     * Automatically trigger summary (used by heartbeat timeout cron).
     */
    public function autoTriggerSummary(AsrTaskStatusDTO $taskStatus, string $userId, string $organizationCode): void
    {
        $lockName = sprintf(AsrRedisKeys::SUMMARY_LOCK, $taskStatus->taskKey);
        $lockOwner = sprintf('%s:%s:%s', $userId, $taskStatus->taskKey, microtime(true));
        $locked = $this->locker->spinLock($lockName, $lockOwner, AsrConfig::SUMMARY_LOCK_TTL);
        if (! $locked) {
            $this->logger->warning('Failed to acquire auto-summary lock; skipping this run', [
                'task_key' => $taskStatus->taskKey,
                'user_id' => $userId,
            ]);
            return;
        }
        try {
            // Idempotency check: skip when summary already completed
            if ($taskStatus->isSummaryCompleted()) {
                $this->logger->info('Auto-summary already completed; skipping duplicate processing', [
                    'task_key' => $taskStatus->taskKey,
                    'audio_file_id' => $taskStatus->audioFileId,
                    'status' => $taskStatus->status->value,
                ]);
                return;
            }

            if ($taskStatus->serverSummaryRetryCount >= AsrConfig::SERVER_SUMMARY_MAX_RETRY) {
                $this->logger->warning('Auto-summary retry limit reached; skipping this run', [
                    'task_key' => $taskStatus->taskKey,
                    'retry_count' => $taskStatus->serverSummaryRetryCount,
                    'max_retry' => AsrConfig::SERVER_SUMMARY_MAX_RETRY,
                ]);
                return;
            }

            $taskStatus->markServerSummaryAttempt();
            $this->asrTaskDomainService->saveTaskStatus($taskStatus);

            $this->logger->info('Start auto-summary', [
                'task_key' => $taskStatus->taskKey,
                'project_id' => $taskStatus->projectId,
            ]);

            // Generate title
            $fileTitle = $this->titleGeneratorService->generateFromTaskStatus($taskStatus);

            // Generate new display directory path and update taskStatus to ensure sandbox uses correct directory
            //            $oldDisplayDirectory = $taskStatus->displayDirectory;
            if (! empty($fileTitle)) {
                $newDisplayDirectory = $this->directoryService->getNewDisplayDirectory(
                    $taskStatus,
                    $fileTitle,
                    $this->titleGeneratorService
                );
                $taskStatus->displayDirectory = $newDisplayDirectory;
            }

            // Merge audio in sandbox (sandbox renames directories without notifying file changes)
            $this->asrSandboxService->mergeAudioFiles($taskStatus, $fileTitle, $organizationCode);

            // Send chat message
            $this->sendAutoSummaryChatMessage($taskStatus, $userId, $organizationCode);

            $taskStatus->finishServerSummaryAttempt(true);

            // Mark task completed (idempotent)
            $taskStatus->updateStatus(AsrTaskStatusEnum::COMPLETED);
            $taskStatus->recordingStatus = AsrRecordingStatusEnum::STOPPED->value;
            $this->asrTaskDomainService->saveTaskStatus($taskStatus);

            // Clean up streaming transcript files (no longer needed after summary)
            if (! empty($taskStatus->presetTranscriptFileId)) {
                $this->presetFileService->deleteTranscriptFile($taskStatus->presetTranscriptFileId);
            }

            $this->logger->info('Auto-summary completed', [
                'task_key' => $taskStatus->taskKey,
                'audio_file_id' => $taskStatus->audioFileId,
                'status' => $taskStatus->status->value,
            ]);
        } catch (Throwable $e) {
            $taskStatus->finishServerSummaryAttempt(false);
            $this->asrTaskDomainService->saveTaskStatus($taskStatus);

            $this->logger->error('Auto-summary failed', [
                'task_key' => $taskStatus->taskKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * Execute the summary flow asynchronously.
     */
    private function executeAsyncSummary(
        SummaryRequestDTO $summaryRequest,
        DelightfulUserAuthorization $userAuthorization
    ): void {
        $requestId = CoContext::getRequestId();
        // Important: use CoContext::getLanguage() instead of translator->getLocale()
        // Later service calls may change the translator locale, but CoContext language stays fixed
        $language = CoContext::getLanguage();
        Coroutine::create(function () use ($summaryRequest, $userAuthorization, $language, $requestId) {
            // In the coroutine, re-fetch translator instance and set language
            di(TranslatorInterface::class)->setLocale($language);
            CoContext::setLanguage($language);
            CoContext::setRequestId($requestId);

            try {
                $this->handleAsrSummary($summaryRequest, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());
            } catch (Throwable $e) {
                $this->logger->error('ASR summary coroutine execution failed', [
                    'task_key' => $summaryRequest->taskKey,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * Get project and workspace names.
     */
    private function getProjectAndWorkspaceNames(string $projectId): array
    {
        try {
            $projectEntity = $this->projectDomainService->getProjectNotUserId((int) $projectId);
            if ($projectEntity === null) {
                return [null, null];
            }

            $projectName = $projectEntity->getProjectName();
            $workspaceId = $projectEntity->getWorkspaceId();
            $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail($workspaceId);
            $workspaceName = $workspaceEntity?->getName();

            return [$projectName, $workspaceName];
        } catch (Throwable $e) {
            $this->logger->warning('Failed to query project or workspace info', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);
            return [null, null];
        }
    }

    /**
     * Determine whether names need to be updated.
     */
    private function shouldUpdateNames(?string $projectName, ?string $topicName): bool
    {
        return empty(trim($projectName ?? '')) || empty(trim($topicName ?? ''));
    }

    /**
     * Update empty project and topic names.
     */
    private function updateEmptyProjectAndTopicNames(
        string $projectId,
        int $topicId,
        string $generatedTitle,
        string $userId,
        string $organizationCode
    ): void {
        try {
            // Update project name when empty
            $projectEntity = $this->projectDomainService->getProject((int) $projectId, $userId);
            if (empty(trim($projectEntity->getProjectName()))) {
                $projectEntity->setProjectName($generatedTitle);
                $projectEntity->setUpdatedUid($userId);
                $this->projectDomainService->saveProjectEntity($projectEntity);
            }

            // Update topic name when empty
            $topicEntity = $this->superAgentTopicDomainService->getTopicById($topicId);
            if ($topicEntity && empty(trim($topicEntity->getTopicName()))) {
                $dataIsolation = DataIsolation::simpleMake($organizationCode, $userId);
                $this->superAgentTopicDomainService->updateTopic($dataIsolation, $topicId, $generatedTitle);
            }
        } catch (Throwable $e) {
            $this->logger->warning('Failed to update project/topic names', [
                'project_id' => $projectId,
                'topic_id' => $topicId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a virtual task status from an existing file ID.
     */
    private function createVirtualTaskStatusFromFileId(
        SummaryRequestDTO $summaryRequest,
        string $userId,
        string $organizationCode
    ): AsrTaskStatusDTO {
        $fileEntity = $this->taskFileDomainService->getById((int) $summaryRequest->fileId);

        if ($fileEntity === null) {
            ExceptionBuilder::throw(AsrErrorCode::FileNotExist, '', ['fileId' => $summaryRequest->fileId]);
        }

        if ((string) $fileEntity->getProjectId() !== $summaryRequest->projectId) {
            ExceptionBuilder::throw(AsrErrorCode::FileNotBelongToProject, '', ['fileId' => $summaryRequest->fileId]);
        }

        $workspaceRelativePath = AsrAssembler::extractWorkspaceRelativePath($fileEntity->getFileKey());

        return new AsrTaskStatusDTO([
            'task_key' => $summaryRequest->taskKey,
            'user_id' => $userId,
            'organization_code' => $organizationCode,
            'status' => AsrTaskStatusEnum::COMPLETED->value,
            'file_path' => $workspaceRelativePath,
            'audio_file_id' => $summaryRequest->fileId,
            'project_id' => $summaryRequest->projectId,
            'topic_id' => $summaryRequest->topicId,
        ]);
    }

    /**
     * Update audio from the sandbox.
     */
    private function updateAudioFromSandbox(
        AsrTaskStatusDTO $taskStatus,
        string $organizationCode,
        ?string $customTitle = null
    ): void {
        $fileTitle = $this->titleGeneratorService->sanitizeTitle($customTitle ?? '');
        if ($fileTitle === '') {
            $fileTitle = $this->translator->trans('asr.file_names.original_recording');
        }

        $this->asrSandboxService->mergeAudioFiles($taskStatus, $fileTitle, $organizationCode);
    }

    /**
     * Build audio file data from task status.
     */
    private function buildFileDataFromTaskStatus(AsrTaskStatusDTO $taskStatus): AsrFileDataDTO
    {
        $fileId = $taskStatus->audioFileId;
        if (empty($fileId)) {
            ExceptionBuilder::throw(AsrErrorCode::AudioFileIdEmpty);
        }

        $fileEntity = $this->taskFileDomainService->getById((int) $fileId);
        if ($fileEntity === null) {
            ExceptionBuilder::throw(AsrErrorCode::FileNotExist, '', ['fileId' => $fileId]);
        }

        $workspaceRelativePath = AsrAssembler::extractWorkspaceRelativePath($fileEntity->getFileKey());

        return AsrFileDataDTO::fromTaskFileEntity($fileEntity, $workspaceRelativePath);
    }

    /**
     * Build note file data from task status.
     */
    private function buildNoteFileDataFromTaskStatus(AsrTaskStatusDTO $taskStatus): ?AsrFileDataDTO
    {
        $noteFileId = $taskStatus->noteFileId;
        if (empty($noteFileId)) {
            return null;
        }

        $fileEntity = $this->taskFileDomainService->getById((int) $noteFileId);
        if ($fileEntity === null) {
            $this->logger->warning('Note file does not exist', [
                'task_key' => $taskStatus->taskKey,
                'note_file_id' => $noteFileId,
            ]);
            return null;
        }

        $workspaceRelativePath = AsrAssembler::extractWorkspaceRelativePath($fileEntity->getFileKey());

        return AsrFileDataDTO::fromTaskFileEntity($fileEntity, $workspaceRelativePath);
    }

    /**
     * Send summary chat message.
     */
    private function sendSummaryChatMessage(ProcessSummaryTaskDTO $dto, DelightfulUserAuthorization $userAuthorization): void
    {
        try {
            // Build audio file data
            $audioFileData = $this->buildFileDataFromTaskStatus($dto->taskStatus);

            // Build note file data when present
            $noteFileData = $this->buildNoteFileDataFromTaskStatus($dto->taskStatus);

            // Build chat message (with optional note file)
            $chatRequest = $this->chatMessageAssembler->buildSummaryMessage($dto, $audioFileData, $noteFileData);

            // Log message details
            $messageData = $chatRequest->getData()->getMessage()->getDelightfulMessage();

            $this->logger->info('sendSummaryChatMessage ready to send ASR summary chat message', [
                'task_key' => $dto->taskStatus->taskKey,
                'topic_id' => $dto->topicId,
                'conversation_id' => $dto->conversationId,
                'model_id' => $dto->modelId,
                'audio_file_id' => $dto->taskStatus->audioFileId,
                'audio_file_path' => $dto->taskStatus->filePath,
                'note_file_id' => $dto->taskStatus->noteFileId,
                'has_note_file' => $noteFileData !== null,
                'message_content' => $messageData->toArray(),
                'is_queued' => $this->shouldQueueMessage($dto->topicId),
                'language' => CoContext::getLanguage(),
            ]);

            if ($this->shouldQueueMessage($dto->topicId)) {
                $this->queueChatMessage($dto, $chatRequest, $userAuthorization);
            } else {
                $this->magicChatMessageAppService->onChatMessage($chatRequest, $userAuthorization);
            }
        } catch (Throwable $e) {
            $this->logger->error('Failed to send chat message', [
                'task_key' => $dto->taskStatus->taskKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine if the message should be queued.
     */
    private function shouldQueueMessage(string $topicId): bool
    {
        $topicEntity = $this->superAgentTopicDomainService->getTopicById((int) $topicId);
        if ($topicEntity === null) {
            ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND);
        }

        $currentStatus = $topicEntity->getCurrentTaskStatus();
        return $currentStatus !== null && $currentStatus === SuperAgentTaskStatus::RUNNING;
    }

    /**
     * Enqueue the message for processing.
     */
    private function queueChatMessage(ProcessSummaryTaskDTO $dto, ChatRequest $chatRequest, DelightfulUserAuthorization $userAuthorization): void
    {
        $dataIsolation = DataIsolation::create($userAuthorization->getOrganizationCode(), $userAuthorization->getId());
        $topicEntity = $this->superAgentTopicDomainService->getTopicById((int) $dto->topicId);
        if ($topicEntity === null) {
            ExceptionBuilder::throw(AsrErrorCode::TopicNotExist, '', ['topicId' => $dto->topicId]);
        }

        $messageContent = $chatRequest->getData()->getMessage()->getDelightfulMessage()->toArray();
        $this->messageQueueDomainService->createMessage(
            $dataIsolation,
            (int) $dto->projectId,
            $topicEntity->getId(),
            $messageContent,
            ChatMessageType::RichText
        );
    }

    /**
     * Build user authorization from user ID.
     */
    private function getUserAuthorizationFromUserId(string $userId): DelightfulUserAuthorization
    {
        $userEntity = $this->magicUserDomainService->getUserById($userId);
        if ($userEntity === null) {
            ExceptionBuilder::throw(AsrErrorCode::UserNotExist);
        }
        return DelightfulUserAuthorization::fromUserEntity($userEntity);
    }

    /**
     * Update task status based on status report.
     */
    private function updateTaskStatusFromReport(
        AsrTaskStatusDTO $taskStatus,
        string $modelId,
        string $asrStreamContent,
        ?string $noteContent,
        ?string $noteFileType,
        string $language
    ): void {
        if (! empty($modelId)) {
            $taskStatus->modelId = $modelId;
        }

        if (! empty($asrStreamContent)) {
            $taskStatus->asrStreamContent = mb_substr($asrStreamContent, 0, 10000);
        }

        if (! empty($noteContent)) {
            $taskStatus->noteContent = mb_substr($noteContent, 0, 25000);
            $taskStatus->noteFileType = $noteFileType ?? 'md';
        }

        if (! empty($language)) {
            $taskStatus->language = $language;
        }
    }

    /**
     * Handle start recording.
     */
    private function handleStartRecording(AsrTaskStatusDTO $taskStatus, string $userId, string $organizationCode): bool
    {
        // On each start, ensure sandbox exists so audio is not lost if sandbox is reclaimed after long pauses
        try {
            $this->asrSandboxService->startRecordingTask($taskStatus, $userId, $organizationCode);
            $taskStatus->sandboxRetryCount = 0; // reset retries after success
        } catch (Throwable $e) {
            // Log but continue when sandbox fails to start (sandbox may be temporarily unavailable)
            ++$taskStatus->sandboxRetryCount;
            $this->logger->warning('Sandbox task failed to start; will retry automatically later', [
                'task_key' => $taskStatus->taskKey,
                'retry_count' => $taskStatus->sandboxRetryCount,
                'error' => $e->getMessage(),
            ]);
        }
        $taskStatus->sandboxTaskCreated = true; // reset flag after attempt
        // Update status and set heartbeat (atomic)
        $taskStatus->recordingStatus = AsrRecordingStatusEnum::START->value;
        $taskStatus->isPaused = false;
        $this->asrTaskDomainService->saveTaskStatusWithHeartbeat($taskStatus);

        return true;
    }

    /**
     * Handle recording heartbeat.
     */
    private function handleRecordingHeartbeat(AsrTaskStatusDTO $taskStatus): bool
    {
        // Update status and set heartbeat (atomic)
        $taskStatus->recordingStatus = AsrRecordingStatusEnum::RECORDING->value;
        $this->asrTaskDomainService->saveTaskStatusWithHeartbeat($taskStatus);

        return true;
    }

    /**
     * Handle pause recording.
     */
    private function handlePauseRecording(AsrTaskStatusDTO $taskStatus): bool
    {
        // Update status and delete heartbeat (atomic)
        $taskStatus->recordingStatus = AsrRecordingStatusEnum::PAUSED->value;
        $taskStatus->isPaused = true;
        $this->asrTaskDomainService->saveTaskStatusAndDeleteHeartbeat($taskStatus);

        return true;
    }

    /**
     * Handle stop recording.
     */
    private function handleStopRecording(AsrTaskStatusDTO $taskStatus): bool
    {
        // Idempotency check: skip when recording is already stopped
        if ($taskStatus->recordingStatus === AsrRecordingStatusEnum::STOPPED->value) {
            $this->logger->info('Recording already stopped; skipping duplicate handling', [
                'task_key' => $taskStatus->taskKey,
            ]);
            return true;
        }

        // Update status and delete heartbeat (atomic)
        $taskStatus->recordingStatus = AsrRecordingStatusEnum::STOPPED->value;
        $this->asrTaskDomainService->saveTaskStatusAndDeleteHeartbeat($taskStatus);

        return true;
    }

    /**
     * Handle cancel recording.
     */
    private function handleCancelRecording(AsrTaskStatusDTO $taskStatus): bool
    {
        // Idempotency check: skip when already canceled
        if ($taskStatus->recordingStatus === AsrRecordingStatusEnum::CANCELED->value) {
            $this->logger->info('Recording already canceled; skipping duplicate handling', [
                'task_key' => $taskStatus->taskKey,
            ]);
            return true;
        }

        $this->logger->info('Begin handling recording cancellation', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $taskStatus->sandboxId,
        ]);

        // Cancel sandbox task when it exists
        if ($taskStatus->sandboxTaskCreated && ! empty($taskStatus->sandboxId)) {
            try {
                $response = $this->asrSandboxService->cancelRecordingTask($taskStatus);
                $this->logger->info('Sandbox recording task canceled', [
                    'task_key' => $taskStatus->taskKey,
                    'sandbox_id' => $taskStatus->sandboxId,
                    'response_status' => $response->getStatus(),
                ]);
            } catch (Throwable $e) {
                // Sandbox cancellation failure does not block local cleanup
                $this->logger->warning('Sandbox cancellation failed; continue local cleanup', [
                    'task_key' => $taskStatus->taskKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update status to canceled and delete heartbeat
        $taskStatus->recordingStatus = AsrRecordingStatusEnum::CANCELED->value;
        $this->asrTaskDomainService->saveTaskStatusAndDeleteHeartbeat($taskStatus);

        // Prepare DataIsolation object used for directory cleanup
        $dataIsolation = DataIsolation::simpleMake(
            $taskStatus->organizationCode,
            $taskStatus->userId
        );

        // Fetch project info to determine workDir
        $workDir = '';
        $projectOrganizationCode = $taskStatus->organizationCode;
        try {
            $projectEntity = $this->projectDomainService->getProject((int) $taskStatus->projectId, $taskStatus->userId);
            $workDir = $projectEntity->getWorkDir();
            $projectOrganizationCode = $projectEntity->getUserOrganizationCode();
        } catch (Throwable $e) {
            $this->logger->warning('Failed to fetch project info', [
                'task_key' => $taskStatus->taskKey,
                'project_id' => $taskStatus->projectId,
                'error' => $e->getMessage(),
            ]);
        }

        // Clean hidden directory including all descendant files (preset files included)
        if (! empty($taskStatus->tempHiddenDirectoryId) && ! empty($taskStatus->tempHiddenDirectory)) {
            try {
                if (! empty($workDir)) {
                    // Cascade delete directory and all child files using deleteDirectoryFiles
                    $deletedCount = $this->taskFileDomainService->deleteDirectoryFiles(
                        $dataIsolation,
                        $workDir,
                        (int) $taskStatus->projectId,
                        $this->getFullFileKey($taskStatus->tempHiddenDirectory, $workDir, $projectOrganizationCode),
                        $projectOrganizationCode
                    );
                    $this->logger->info('Deleted hidden directory and children successfully', [
                        'task_key' => $taskStatus->taskKey,
                        'hidden_directory_id' => $taskStatus->tempHiddenDirectoryId,
                        'hidden_directory_path' => $taskStatus->tempHiddenDirectory,
                        'deleted_count' => $deletedCount,
                    ]);
                } else {
                    // Fallback: delete directory record only when workDir is unavailable
                    $this->taskFileDomainService->deleteById((int) $taskStatus->tempHiddenDirectoryId);
                    $this->logger->warning('workDir unavailable; deleted hidden directory record only', [
                        'task_key' => $taskStatus->taskKey,
                        'hidden_directory_id' => $taskStatus->tempHiddenDirectoryId,
                    ]);
                }
            } catch (Throwable $e) {
                $this->logger->warning('Failed to delete hidden directory', [
                    'task_key' => $taskStatus->taskKey,
                    'hidden_directory_id' => $taskStatus->tempHiddenDirectoryId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clean display directory including all descendant files (preset files included)
        if (! empty($taskStatus->displayDirectoryId) && ! empty($taskStatus->displayDirectory)) {
            try {
                if (! empty($workDir)) {
                    // Cascade delete directory and all child files using deleteDirectoryFiles
                    $deletedCount = $this->taskFileDomainService->deleteDirectoryFiles(
                        $dataIsolation,
                        $workDir,
                        (int) $taskStatus->projectId,
                        $this->getFullFileKey($taskStatus->displayDirectory, $workDir, $projectOrganizationCode),
                        $projectOrganizationCode
                    );
                    $this->logger->info('Deleted display directory and children successfully', [
                        'task_key' => $taskStatus->taskKey,
                        'display_directory_id' => $taskStatus->displayDirectoryId,
                        'display_directory_path' => $taskStatus->displayDirectory,
                        'deleted_count' => $deletedCount,
                    ]);
                } else {
                    // Fallback: delete directory record only when workDir is unavailable
                    $this->taskFileDomainService->deleteById((int) $taskStatus->displayDirectoryId);
                    $this->logger->warning('workDir unavailable; deleted display directory record only', [
                        'task_key' => $taskStatus->taskKey,
                        'display_directory_id' => $taskStatus->displayDirectoryId,
                    ]);
                }
            } catch (Throwable $e) {
                $this->logger->warning('Failed to delete display directory', [
                    'task_key' => $taskStatus->taskKey,
                    'display_directory_id' => $taskStatus->displayDirectoryId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Delete merged audio file when present and not covered above
        if (! empty($taskStatus->audioFileId)) {
            try {
                $this->taskFileDomainService->deleteById((int) $taskStatus->audioFileId);
                $this->logger->info('Deleted audio file successfully', [
                    'task_key' => $taskStatus->taskKey,
                    'audio_file_id' => $taskStatus->audioFileId,
                ]);
            } catch (Throwable $e) {
                $this->logger->warning('Failed to delete audio file', [
                    'task_key' => $taskStatus->taskKey,
                    'audio_file_id' => $taskStatus->audioFileId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Delete the project when no other files remain
        if (! empty($taskStatus->projectId)) {
            try {
                $this->checkAndDeleteProjectIfEmpty($taskStatus);
            } catch (Throwable $e) {
                $this->logger->warning('Failed to check and delete empty project', [
                    'task_key' => $taskStatus->taskKey,
                    'project_id' => $taskStatus->projectId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Recording cancellation completed', [
            'task_key' => $taskStatus->taskKey,
        ]);

        return true;
    }

    /**
     * Delete project when it contains no files.
     */
    private function checkAndDeleteProjectIfEmpty(AsrTaskStatusDTO $taskStatus): void
    {
        // Fetch all user files in the project (excluding hidden files)
        $files = $this->taskFileDomainService->findUserFilesByProjectId($taskStatus->projectId);

        if (empty($files)) {
            $this->logger->info('No files in project; preparing to delete project', [
                'task_key' => $taskStatus->taskKey,
                'project_id' => $taskStatus->projectId,
            ]);

            // Delete project
            try {
                $this->projectDomainService->deleteProject((int) $taskStatus->projectId, $taskStatus->userId);
                $this->logger->info('Deleted empty project successfully', [
                    'task_key' => $taskStatus->taskKey,
                    'project_id' => $taskStatus->projectId,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to delete empty project', [
                    'task_key' => $taskStatus->taskKey,
                    'project_id' => $taskStatus->projectId,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            $this->logger->info('Project still contains files; skipping deletion', [
                'task_key' => $taskStatus->taskKey,
                'project_id' => $taskStatus->projectId,
                'file_count' => count($files),
            ]);
        }
    }

    /**
     * Build a full file_key.
     *
     * @param string $relativePath Relative path
     * @param string $workDir Work directory
     * @param string $organizationCode Organization code
     * @return string Full file_key
     */
    private function getFullFileKey(string $relativePath, string $workDir, string $organizationCode): string
    {
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($organizationCode);
        return AsrAssembler::buildFileKey($fullPrefix, $workDir, $relativePath);
    }

    /**
     * Send auto-summary chat message.
     */
    private function sendAutoSummaryChatMessage(AsrTaskStatusDTO $taskStatus, string $userId, string $organizationCode): void
    {
        $topicEntity = $this->superAgentTopicDomainService->getTopicById((int) $taskStatus->topicId);
        if ($topicEntity === null) {
            ExceptionBuilder::throw(AsrErrorCode::TopicNotExistSimple);
        }

        $chatTopicId = $topicEntity->getChatTopicId();
        $conversationId = $this->magicChatDomainService->getConversationIdByTopicId($chatTopicId);

        $processSummaryTaskDTO = new ProcessSummaryTaskDTO(
            $taskStatus,
            $organizationCode,
            $taskStatus->projectId,
            $userId,
            $taskStatus->topicId,
            $chatTopicId,
            $conversationId,
            $taskStatus->modelId ?? ''
        );

        $userAuthorization = $this->getUserAuthorizationFromUserId($userId);
        $this->sendSummaryChatMessage($processSummaryTaskDTO, $userAuthorization);
    }
}

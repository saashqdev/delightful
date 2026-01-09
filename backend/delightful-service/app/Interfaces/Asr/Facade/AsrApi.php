<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Asr\Facade;

use App\Application\File\Service\FileAppService;
use App\Application\Speech\Assembler\AsrAssembler;
use App\Application\Speech\DTO\AsrRecordingDirectoryDTO;
use App\Application\Speech\DTO\AsrTaskStatusDTO;
use App\Application\Speech\DTO\NoteDTO;
use App\Application\Speech\DTO\SummaryRequestDTO;
use App\Application\Speech\Enum\AsrRecordingStatusEnum;
use App\Application\Speech\Enum\AsrRecordingTypeEnum;
use App\Application\Speech\Enum\AsrTaskStatusEnum;
use App\Application\Speech\Service\AsrDirectoryService;
use App\Application\Speech\Service\AsrFileAppService;
use App\Application\Speech\Service\AsrPresetFileService;
use App\Application\Speech\Service\AsrTitleGeneratorService;
use App\ErrorCode\AsrErrorCode;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use App\Infrastructure\Util\Asr\Service\ByteDanceSTSService;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Exception;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Translation\trans;

#[Controller]
#[ApiResponse('low_code')]
class AsrApi extends AbstractApi
{
    private LoggerInterface $logger;

    public function __construct(
        protected ByteDanceSTSService $stsService,
        protected FileAppService $fileAppService,
        protected AsrFileAppService $asrFileAppService,
        protected AsrTitleGeneratorService $titleGeneratorService,
        protected AsrDirectoryService $directoryService,
        protected AsrPresetFileService $presetFileService,
        protected LockerInterface $locker,
        LoggerFactory $loggerFactory,
        RequestInterface $request,
    ) {
        $this->logger = $loggerFactory->get('AsrApi');
        parent::__construct($request);
    }

    /**
     * getcurrentuserASR JWT Token
     * GET /api/v1/asr/tokens.
     * @throws Exception
     */
    public function show(RequestInterface $request): array
    {
        $userAuthorization = $this->getAuthorization();
        $delightfulId = $userAuthorization->getDelightfulId();

        $refresh = (bool) $request->input('refresh', false);
        $duration = 60 * 60 * 12; // 12hour

        $tokenData = $this->stsService->getJwtTokenForUser($delightfulId, $duration, $refresh);

        return [
            'token' => $tokenData['jwt_token'],
            'app_id' => $tokenData['app_id'],
            'duration' => $tokenData['duration'],
            'expires_at' => $tokenData['expires_at'],
            'resource_id' => $tokenData['resource_id'],
            'user' => [
                'user_id' => $userAuthorization->getId(),
                'delightful_id' => $userAuthorization->getDelightfulId(),
                'organization_code' => $userAuthorization->getOrganizationCode(),
            ],
        ];
    }

    /**
     * 清exceptcurrentuserASR JWT Tokencache
     * DELETE /api/v1/asr/tokens.
     */
    public function destroy(): array
    {
        $userAuthorization = $this->getAuthorization();
        $delightfulId = $userAuthorization->getDelightfulId();

        $cleared = $this->stsService->clearUserJwtTokenCache($delightfulId);

        return [
            'cleared' => $cleared,
            'message' => $cleared ? trans('asr.api.token.cache_cleared') : trans('asr.api.token.cache_not_exist'),
            'user' => [
                'user_id' => $userAuthorization->getId(),
                'delightful_id' => $userAuthorization->getDelightfulId(),
                'organization_code' => $userAuthorization->getOrganizationCode(),
            ],
        ];
    }

    /**
     * query录音总结status
     * POST /api/v1/asr/summary.
     */
    public function summary(RequestInterface $request): array
    {
        /** @var DelightfulUserAuthorization $userAuthorization */
        $userAuthorization = $this->getAuthorization();
        $userId = $userAuthorization->getId();
        $summaryRequest = $this->validateAndBuildSummaryRequest($request, $userAuthorization);

        // statuscheck：ifnotispass file_id hairup总结，needchecktaskstatus
        if (! $summaryRequest->hasFileId()) {
            $taskStatus = $this->asrFileAppService->getTaskStatusFromRedis($summaryRequest->taskKey, $userId);

            if (! $taskStatus->isEmpty()) {
                // statuscheck 1：task已cancel，notallow总结
                if ($taskStatus->recordingStatus === AsrRecordingStatusEnum::CANCELED->value) {
                    ExceptionBuilder::throw(AsrErrorCode::TaskAlreadyCanceled);
                }

                // statuscheck 2：task已complete（只inthiswithinrecordlog，allow重新总结bymore换model）
                if ($taskStatus->isSummaryCompleted()) {
                    $this->logger->info('task已complete，allowuse新model重新总结', [
                        'task_key' => $summaryRequest->taskKey,
                        'old_model_id' => $taskStatus->modelId,
                        'new_model_id' => $summaryRequest->modelId,
                    ]);
                }
            }
        }

        // applicationlayer已haveminute布typelock，thiswithinno需againaddlock，直接call
        try {
            // handle总结task
            $result = $this->asrFileAppService->processSummaryWithChat($summaryRequest, $userAuthorization);

            if (! $result['success']) {
                return $this->buildSummaryResponse(false, $summaryRequest, $result['error']);
            }

            return $this->buildSummaryResponse(true, $summaryRequest, null, $result);
        } catch (Throwable $e) {
            $this->logger->error('ASR总结handleexception', [
                'task_key' => $summaryRequest->taskKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->buildSummaryResponse(false, $summaryRequest, sprintf('handleexception: %s', $e->getMessage()));
        }
    }

    /**
     * getASR录音fileuploadSTS Token
     * GET /api/v1/asr/upload-tokens.
     */
    public function getUploadToken(RequestInterface $request): array
    {
        $operationId = uniqid('op_', true);

        /** @var DelightfulUserAuthorization $userAuthorization */
        $userAuthorization = $this->getAuthorization();
        $userId = $userAuthorization->getId();
        $organizationCode = $userAuthorization->getOrganizationCode();

        // 1. validateparameter
        /** @var AsrRecordingTypeEnum $recordingType */
        [$taskKey, $topicId, $projectId, $recordingType, $fileName] = $this->validateUploadTokenParams($request, $userId);

        $this->logger->info('getUploadToken starthandle', [
            'operation_id' => $operationId,
            'task_key' => $taskKey,
            'user_id' => $userId,
            'recording_type' => $recordingType->value,
            'needs_preset_files' => $recordingType->needsPresetFiles(),
            'has_file_name' => ! empty($fileName),
        ]);

        // 2. getminute布typelock（防止andhaircreatedirectory）
        $lockName = sprintf('asr:upload_token:lock:%s:%s', $userId, $taskKey);
        $lockOwner = sprintf('%s:%s', $userId, microtime(true));
        $locked = $this->locker->spinLock($lockName, $lockOwner);

        if (! $locked) {
            ExceptionBuilder::throw(AsrErrorCode::SystemBusy);
        }

        try {
            // 3. create .asr_recordings 父directory（所have录音typeallneed）
            try {
                $recordingsDir = $this->directoryService->createRecordingsDirectory($organizationCode, $projectId, $userId);
                $this->logger->info('.asr_recordings 父directorycreateorconfirm存in', [
                    'task_key' => $taskKey,
                    'recording_type' => $recordingType->value,
                    'recordings_dir_id' => $recordingsDir->directoryId,
                    'recordings_dir_path' => $recordingsDir->directoryPath,
                ]);
            } catch (Throwable $e) {
                // .asr_recordings directorycreatefailnot影响主process
                $this->logger->warning('create .asr_recordings 父directoryfail', [
                    'task_key' => $taskKey,
                    'recording_type' => $recordingType->value,
                    'error' => $e->getMessage(),
                ]);
            }

            // 4. create .asr_states directory（所have录音typeallneed）
            try {
                $statesDir = $this->directoryService->createStatesDirectory($organizationCode, $projectId, $userId);
                $this->logger->info('.asr_states directorycreateorconfirm存in', [
                    'task_key' => $taskKey,
                    'recording_type' => $recordingType->value,
                    'states_dir_id' => $statesDir->directoryId,
                    'states_dir_path' => $statesDir->directoryPath,
                ]);
            } catch (Throwable $e) {
                // .asr_states directorycreatefailnot影响主process
                $this->logger->warning('create .asr_states directoryfail', [
                    'task_key' => $taskKey,
                    'recording_type' => $recordingType->value,
                    'error' => $e->getMessage(),
                ]);
            }

            // 5. 预先generatetitle（forincreatedirectoryo clockuse）
            $generatedTitle = null;
            // getcurrentstatusbycheckwhether已存intitle
            $currentTaskStatus = $this->asrFileAppService->getTaskStatusFromRedis($taskKey, $userId);

            if (
                $recordingType === AsrRecordingTypeEnum::FILE_UPLOAD
                && ! empty($fileName)
                && ($currentTaskStatus->isEmpty() || empty($currentTaskStatus->uploadGeneratedTitle))
            ) {
                try {
                    $generatedTitle = $this->titleGeneratorService->generateTitleForFileUpload(
                        $userAuthorization,
                        $fileName,
                        $taskKey
                    );

                    if (! empty($generatedTitle)) {
                        $this->logger->info('file直传titlegeneratesuccess', [
                            'task_key' => $taskKey,
                            'file_name' => $fileName,
                            'generated_title' => $generatedTitle,
                        ]);
                    }
                } catch (Throwable $e) {
                    // titlegeneratefailnot影响主process
                    $this->logger->warning('file直传titlegeneratefail', [
                        'task_key' => $taskKey,
                        'file_name' => $fileName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 6. createorupdatetaskstatus
            $taskStatus = $this->createOrUpdateTaskStatus($taskKey, $topicId, $projectId, $userId, $organizationCode, $generatedTitle);

            // ensure generatedTitle besettingto taskStatus middle
            if (! empty($generatedTitle) && empty($taskStatus->uploadGeneratedTitle)) {
                $taskStatus->uploadGeneratedTitle = $generatedTitle;
            }

            // 6. getSTS Token
            $tokenData = $this->buildStsToken($userAuthorization, $projectId, $userId);

            // 7. createpresetfile（ifalso未create，and录音typeneedpresetfile）
            if (
                empty($taskStatus->presetNoteFileId)
                && ! empty($taskStatus->displayDirectory)
                && ! empty($taskStatus->displayDirectoryId)
                && ! empty($taskStatus->tempHiddenDirectory)
                && ! empty($taskStatus->tempHiddenDirectoryId)
                && $recordingType->needsPresetFiles()
            ) {
                try {
                    $presetFiles = $this->presetFileService->createPresetFiles(
                        $userId,
                        $organizationCode,
                        (int) $projectId,
                        $taskStatus->displayDirectory,
                        (int) $taskStatus->displayDirectoryId,
                        $taskStatus->tempHiddenDirectory,
                        (int) $taskStatus->tempHiddenDirectoryId,
                        $taskKey
                    );

                    // savepresetfileIDandpathtotaskstatus
                    $taskStatus->presetNoteFileId = (string) $presetFiles['note_file']->getFileId();
                    $taskStatus->presetTranscriptFileId = (string) $presetFiles['transcript_file']->getFileId();
                    $taskStatus->presetNoteFilePath = $presetFiles['note_file']->getFileKey();
                    $taskStatus->presetTranscriptFilePath = $presetFiles['transcript_file']->getFileKey();

                    $this->logger->info('presetfilecreatesuccess', [
                        'task_key' => $taskKey,
                        'note_file_id' => $taskStatus->presetNoteFileId,
                        'transcript_file_id' => $taskStatus->presetTranscriptFileId,
                        'note_file_path' => $taskStatus->presetNoteFilePath,
                        'transcript_file_path' => $taskStatus->presetTranscriptFilePath,
                    ]);
                } catch (Throwable $e) {
                    // presetfilecreatefailnot影响主process
                    $this->logger->warning('createpresetfilefail', [
                        'task_key' => $taskKey,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // 8. savetaskstatus
            $this->asrFileAppService->saveTaskStatusToRedis($taskStatus);

            // 9. returnresponse
            return $this->buildUploadTokenResponse($tokenData, $taskStatus, $taskKey);
        } finally {
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * 录音statusup报interface
     * POST /api/v1/asr/status.
     */
    public function reportStatus(RequestInterface $request): array
    {
        $operationId = uniqid('op_', true);

        $userAuthorization = $this->getAuthorization();
        $userId = $userAuthorization->getId();
        $organizationCode = $userAuthorization->getOrganizationCode();

        // getandvalidateparameter
        $taskKey = $request->input('task_key', '');
        $status = $request->input('status', '');
        $modelId = $request->input('model_id', '');
        $asrStreamContent = $request->input('asr_stream_content', '');
        $noteData = $request->input('note');

        // get语type
        $language = CoContext::getLanguage();

        $this->logger->info('reportStatus starthandle', [
            'operation_id' => $operationId,
            'task_key' => $taskKey,
            'status' => $status,
            'user_id' => $userId,
        ]);

        // validateparameter
        if (empty($taskKey)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, trans('asr.exception.task_key_empty'));
        }

        $statusEnum = AsrRecordingStatusEnum::tryFromString($status);
        if ($statusEnum === null) {
            ExceptionBuilder::throw(
                GenericErrorCode::ParameterMissing,
                sprintf('invalidstatus，validvalue：%s', implode(', ', ['start', 'recording', 'paused', 'stopped', 'canceled']))
            );
        }

        // statuscheck：getcurrenttaskstatus
        $taskStatus = $this->asrFileAppService->getTaskStatusFromRedis($taskKey, $userId);
        if (! $taskStatus->isEmpty()) {
            if ($taskStatus->hasServerSummaryLock()) {
                $this->logger->info('reportStatus service端总结conductmiddle，rejectstatusup报', [
                    'task_key' => $taskKey,
                    'user_id' => $userId,
                    'retry_count' => $taskStatus->serverSummaryRetryCount,
                ]);
                ExceptionBuilder::throw(AsrErrorCode::TaskIsSummarizing);
            }

            // statuscheck 1：task已complete，notallow报告status（unlessis canceled）
            if ($statusEnum !== AsrRecordingStatusEnum::CANCELED && $taskStatus->isSummaryCompleted()) {
                ExceptionBuilder::throw(AsrErrorCode::TaskAlreadyCompleted);
            }

            // statuscheck 2：task已cancel，notallowagain报告otherstatus
            if (
                $taskStatus->recordingStatus === AsrRecordingStatusEnum::CANCELED->value
                && $statusEnum !== AsrRecordingStatusEnum::CANCELED
            ) {
                ExceptionBuilder::throw(AsrErrorCode::TaskAlreadyCanceled);
            }

            // statuscheck 3：录音已stopand已merge，notallowagain start/recording（maybeiscore跳timeoutfrom动stop）
            if (
                $taskStatus->recordingStatus === AsrRecordingStatusEnum::STOPPED->value
                && ! empty($taskStatus->audioFileId)
                && in_array($statusEnum, [AsrRecordingStatusEnum::START, AsrRecordingStatusEnum::RECORDING], true)
            ) {
                ExceptionBuilder::throw(AsrErrorCode::TaskAutoStoppedByTimeout);
            }
        }

        // handle note parameter
        $noteContent = null;
        $noteFileType = null;
        if (! empty($noteData) && is_array($noteData)) {
            $noteContent = $noteData['content'] ?? '';
            $noteFileType = $noteData['file_type'] ?? 'md';
        }

        // callapplicationservicehandle
        $success = $this->asrFileAppService->handleStatusReport(
            $taskKey,
            $statusEnum,
            $modelId,
            $asrStreamContent,
            $noteContent,
            $noteFileType,
            $language,
            $userId,
            $organizationCode
        );

        return ['success' => $success];
    }

    /**
     * validateandbuild总结requestDTO.
     */
    private function validateAndBuildSummaryRequest(RequestInterface $request, DelightfulUserAuthorization $userAuthorization): SummaryRequestDTO
    {
        $taskKey = $request->input('task_key', '');
        $projectId = $request->input('project_id', '');
        $topicId = $request->input('topic_id', '');
        $modelId = $request->input('model_id', '');
        $fileId = $request->input('file_id');
        $noteData = $request->input('note');
        $asrStreamContent = $request->input('asr_stream_content', '');

        // limitcontentlength
        if (! empty($asrStreamContent) && mb_strlen($asrStreamContent) > 10000) {
            $asrStreamContent = mb_substr($asrStreamContent, 0, 10000);
        }

        // ifhavefile_idandnothavetask_key，generateone
        if (! empty($fileId) && empty($taskKey)) {
            $taskKey = uniqid('', true);
        }

        // validate必传parameter
        if (empty($taskKey) && empty($fileId)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, trans('asr.api.validation.task_key_required'));
        }

        if (empty($projectId)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, trans('asr.api.validation.project_id_required'));
        }

        if (empty($topicId)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, trans('asr.api.validation.topic_id_required'));
        }

        if (empty($modelId)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, trans('asr.api.validation.model_id_required'));
        }

        // handle笔记
        $note = $this->parseNoteData($noteData);

        // generatetitle：优先from Redis middle复use upload-tokens generatetitle
        $generatedTitle = null;

        // 1. 尝试from Redis middleget已generatetitle（file直传场景）
        if (! empty($taskKey)) {
            $taskStatus = $this->asrFileAppService->getTaskStatusFromRedis($taskKey, $userAuthorization->getId());
            if (! empty($taskStatus->uploadGeneratedTitle) && ! $taskStatus->isEmpty()) {
                $generatedTitle = $taskStatus->uploadGeneratedTitle;
                $this->logger->info('复use upload-tokens generatetitle', [
                    'task_key' => $taskKey,
                    'title' => $generatedTitle,
                ]);
            }
        }

        // 2. ifnothavefrom Redis gettotitle，then重新generate（front端录音or旧逻辑）
        if (empty($generatedTitle)) {
            $generatedTitle = $this->titleGeneratorService->generateTitleForScenario(
                $userAuthorization,
                $asrStreamContent,
                $fileId,
                $note,
                $taskKey
            );
        }

        return new SummaryRequestDTO($taskKey, $projectId, $topicId, $modelId, $fileId, $note, $asrStreamContent ?: null, $generatedTitle);
    }

    /**
     * parse笔记data.
     */
    private function parseNoteData(mixed $noteData): ?NoteDTO
    {
        if (empty($noteData) || ! is_array($noteData)) {
            return null;
        }

        $noteContent = $noteData['content'] ?? '';
        $noteFileType = $noteData['file_type'] ?? 'md';

        if (empty(trim($noteContent))) {
            return null;
        }

        // validatelength
        $contentLength = mb_strlen($noteContent);
        if ($contentLength > 25000) {
            ExceptionBuilder::throw(
                GenericErrorCode::ParameterMissing,
                trans('asr.api.validation.note_content_too_long', ['length' => $contentLength])
            );
        }

        $note = new NoteDTO($noteContent, $noteFileType);

        // validatefiletype
        if (! $note->isValidFileType()) {
            ExceptionBuilder::throw(
                GenericErrorCode::ParameterMissing,
                sprintf('not supportedfiletype: %s，supporttype: txt, md, json', $noteFileType)
            );
        }

        return $note;
    }

    /**
     * build总结response.
     */
    private function buildSummaryResponse(bool $success, SummaryRequestDTO $request, ?string $error = null, ?array $result = null): array
    {
        if (! $success) {
            return [
                'success' => false,
                'error' => $error,
                'task_key' => $request->taskKey,
                'project_id' => $request->projectId,
                'topic_id' => $request->topicId,
                'topic_name' => null,
                'project_name' => null,
                'workspace_name' => null,
            ];
        }

        return [
            'success' => true,
            'task_key' => $request->taskKey,
            'project_id' => $request->projectId,
            'topic_id' => $request->topicId,
            'conversation_id' => $result['conversation_id'] ?? null,
            'topic_name' => $result['topic_name'] ?? null,
            'project_name' => $result['project_name'] ?? null,
            'workspace_name' => $result['workspace_name'] ?? null,
        ];
    }

    /**
     * validateuploadTokenrequestparameter.
     */
    private function validateUploadTokenParams(RequestInterface $request, string $userId): array
    {
        $taskKey = $request->input('task_key', '');
        if (empty($taskKey)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, trans('asr.api.validation.task_key_required'));
        }

        $topicId = $request->input('topic_id', '');
        if (empty($topicId)) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, trans('asr.exception.topic_id_empty'));
        }

        // validate录音typeparameter（optional，defaultfor file_upload）
        $typeString = $request->input('type', '');
        $recordingType = empty($typeString)
            ? AsrRecordingTypeEnum::default()
            : AsrRecordingTypeEnum::fromString($typeString);

        if ($recordingType === null) {
            ExceptionBuilder::throw(
                GenericErrorCode::ParameterValidationFailed,
                trans('asr.api.validation.invalid_recording_type', ['type' => $typeString])
            );
        }

        $projectId = $this->asrFileAppService->getProjectIdFromTopic((int) $topicId, $userId);

        // getfile名（仅in file_upload typeo clockuse）
        $fileName = $request->input('file_name', '');

        return [$taskKey, $topicId, $projectId, $recordingType, $fileName];
    }

    /**
     * createorupdatetaskstatus.
     */
    private function createOrUpdateTaskStatus(
        string $taskKey,
        string $topicId,
        string $projectId,
        string $userId,
        string $organizationCode,
        ?string $generatedTitle = null
    ): AsrTaskStatusDTO {
        $taskStatus = $this->asrFileAppService->getTaskStatusFromRedis($taskKey, $userId);

        if ($taskStatus->isEmpty()) {
            // theonetimecall：create新taskstatus
            return $this->createNewTaskStatus($taskKey, $topicId, $projectId, $userId, $organizationCode, $generatedTitle);
        }

        if ($taskStatus->hasServerSummaryLock()) {
            $this->logger->info('getUploadToken service端总结conductmiddle，rejecthair放upload凭证', [
                'task_key' => $taskKey,
                'user_id' => $userId,
                'retry_count' => $taskStatus->serverSummaryRetryCount,
            ]);
            ExceptionBuilder::throw(AsrErrorCode::TaskIsSummarizing);
        }

        // statuscheck 1：task已complete，notallowupload
        if ($taskStatus->isSummaryCompleted()) {
            ExceptionBuilder::throw(AsrErrorCode::TaskAlreadyCompleted);
        }

        // statuscheck 2：task已cancel，notallowupload
        if ($taskStatus->recordingStatus === AsrRecordingStatusEnum::CANCELED->value) {
            ExceptionBuilder::throw(AsrErrorCode::TaskAlreadyCanceled);
        }

        // statuscheck 3：录音已stop，notallowupload（maybeiscore跳timeoutfrom动stop）
        if (
            $taskStatus->recordingStatus === AsrRecordingStatusEnum::STOPPED->value
            && ! empty($taskStatus->audioFileId)
        ) {
            ExceptionBuilder::throw(AsrErrorCode::TaskAutoStoppedByTimeout);
        }

        // back续call：update必要field
        $this->asrFileAppService->validateProjectAccess($projectId, $userId, $organizationCode);
        $taskStatus->projectId = $projectId;
        $taskStatus->topicId = $topicId;

        $this->logger->info('back续call getUploadToken，use已havedirectory', [
            'task_key' => $taskKey,
            'hidden_directory' => $taskStatus->tempHiddenDirectory,
            'display_directory' => $taskStatus->displayDirectory,
            'recording_status' => $taskStatus->recordingStatus,
        ]);

        return $taskStatus;
    }

    /**
     * create新taskstatus.
     */
    private function createNewTaskStatus(
        string $taskKey,
        string $topicId,
        string $projectId,
        string $userId,
        string $organizationCode,
        ?string $generatedTitle = null
    ): AsrTaskStatusDTO {
        $this->logger->info('theonetimecall getUploadToken，create新directory', [
            'task_key' => $taskKey,
            'project_id' => $projectId,
            'topic_id' => $topicId,
            'generated_title' => $generatedTitle,
        ]);

        $directories = $this->asrFileAppService->validateTopicAndPrepareDirectories(
            $topicId,
            $projectId,
            $userId,
            $organizationCode,
            $taskKey,
            $generatedTitle
        );

        $hiddenDir = $this->findDirectoryByType($directories, true);
        $displayDir = $this->findDirectoryByType($directories, false);

        if ($hiddenDir === null) {
            ExceptionBuilder::throw(AsrErrorCode::HiddenDirectoryNotFound);
        }

        return new AsrTaskStatusDTO([
            'task_key' => $taskKey,
            'user_id' => $userId,
            'organization_code' => $organizationCode,
            'status' => AsrTaskStatusEnum::PROCESSING->value,
            'project_id' => $projectId,
            'topic_id' => $topicId,
            'temp_hidden_directory' => $hiddenDir->directoryPath,
            'display_directory' => $displayDir?->directoryPath,
            'temp_hidden_directory_id' => $hiddenDir->directoryId,
            'display_directory_id' => $displayDir?->directoryId,
            'upload_generated_title' => $generatedTitle,
        ]);
    }

    /**
     * buildSTS Token.
     */
    private function buildStsToken(DelightfulUserAuthorization $userAuthorization, string $projectId, string $userId): array
    {
        $storageType = StorageBucketType::SandBox->value;
        $expires = 60 * 60;

        $workspacePath = $this->directoryService->getWorkspacePath($projectId, $userId);

        $tokenData = $this->fileAppService->getStsTemporaryCredentialV2(
            $userAuthorization->getOrganizationCode(),
            $storageType,
            $workspacePath,
            $expires,
            false
        );

        unset($tokenData['delightful_service_host']);

        if (empty($tokenData['temporary_credential']['dir'])) {
            $this->logger->error(trans('asr.api.token.sts_get_failed'), [
                'workspace_path' => $workspacePath,
                'user_id' => $userId,
            ]);
            ExceptionBuilder::throw(GenericErrorCode::SystemError, trans('asr.api.token.sts_get_failed'));
        }

        return $tokenData;
    }

    /**
     * builduploadTokenresponse.
     */
    private function buildUploadTokenResponse(array $tokenData, AsrTaskStatusDTO $taskStatus, string $taskKey): array
    {
        $directories = $this->buildDirectoriesArray($taskStatus);
        $presetFiles = $this->buildPresetFilesArray($taskStatus);

        $response = [
            'sts_token' => $tokenData,
            'task_key' => $taskKey,
            'expires_in' => 60 * 60,
            'directories' => $directories,
        ];

        // onlywhenpresetfile存ino clock才addtoreturnmiddle
        if (! empty($presetFiles)) {
            $response['preset_files'] = $presetFiles;
        }

        return $response;
    }

    /**
     * builddirectoryarray.
     */
    private function buildDirectoriesArray(AsrTaskStatusDTO $taskStatus): array
    {
        $directories = [];

        if (! empty($taskStatus->tempHiddenDirectory)) {
            $directories['asr_hidden_dir'] = [
                'directory_path' => $taskStatus->tempHiddenDirectory,
                'directory_id' => (string) $taskStatus->tempHiddenDirectoryId,
                'hidden' => true,
                'type' => 'asr_hidden_dir',
            ];
        }

        if (! empty($taskStatus->displayDirectory)) {
            $directories['asr_display_dir'] = [
                'directory_path' => $taskStatus->displayDirectory,
                'directory_id' => (string) $taskStatus->displayDirectoryId,
                'hidden' => false,
                'type' => 'asr_display_dir',
            ];
        }

        return $directories;
    }

    /**
     * buildpresetfilearray.
     */
    private function buildPresetFilesArray(AsrTaskStatusDTO $taskStatus): array
    {
        $presetFiles = [];

        // 笔记file
        if (! empty($taskStatus->presetNoteFileId) && ! empty($taskStatus->presetNoteFilePath)) {
            $relativePath = AsrAssembler::extractWorkspaceRelativePath($taskStatus->presetNoteFilePath);
            $fileName = basename($relativePath);

            $presetFiles['note_file'] = [
                'file_id' => $taskStatus->presetNoteFileId,
                'file_name' => $fileName,
                'file_path' => $relativePath,
            ];
        }

        // stream识别file
        if (! empty($taskStatus->presetTranscriptFileId) && ! empty($taskStatus->presetTranscriptFilePath)) {
            $relativePath = AsrAssembler::extractWorkspaceRelativePath($taskStatus->presetTranscriptFilePath);
            $fileName = basename($relativePath);

            $presetFiles['transcript_file'] = [
                'file_id' => $taskStatus->presetTranscriptFileId,
                'file_name' => $fileName,
                'file_path' => $relativePath,
            ];
        }

        return $presetFiles;
    }

    /**
     * fromdirectoryarraymiddlefindfinger定typedirectory.
     */
    private function findDirectoryByType(array $directories, bool $hidden): ?AsrRecordingDirectoryDTO
    {
        return array_find($directories, static fn ($directory) => $directory->hidden === $hidden);
    }
}

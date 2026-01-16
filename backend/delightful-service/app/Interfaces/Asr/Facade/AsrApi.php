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
     * clearexceptcurrentuserASR JWT Tokencache
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
     * queryrecordingsummarystatus
     * POST /api/v1/asr/summary.
     */
    public function summary(RequestInterface $request): array
    {
        /** @var DelightfulUserAuthorization $userAuthorization */
        $userAuthorization = $this->getAuthorization();
        $userId = $userAuthorization->getId();
        $summaryRequest = $this->validateAndBuildSummaryRequest($request, $userAuthorization);

        // statuscheck:ifnotispass file_id hairupsummary,needchecktaskstatus
        if (! $summaryRequest->hasFileId()) {
            $taskStatus = $this->asrFileAppService->getTaskStatusFromRedis($summaryRequest->taskKey, $userId);

            if (! $taskStatus->isEmpty()) {
                // statuscheck 1:taskalreadycancel,notallowsummary
                if ($taskStatus->recordingStatus === AsrRecordingStatusEnum::CANCELED->value) {
                    ExceptionBuilder::throw(AsrErrorCode::TaskAlreadyCanceled);
                }

                // statuscheck 2:taskalreadycomplete(onlyinthiswithinrecordlog,allowreloadnewsummarybymoreexchangemodel)
                if ($taskStatus->isSummaryCompleted()) {
                    $this->logger->info('taskalreadycomplete,allowusenewmodelreloadnewsummary', [
                        'task_key' => $summaryRequest->taskKey,
                        'old_model_id' => $taskStatus->modelId,
                        'new_model_id' => $summaryRequest->modelId,
                    ]);
                }
            }
        }

        // applicationlayeralreadyhaveminutedistributetypelock,thiswithinnoneedagainaddlock,directlycall
        try {
            // handlesummarytask
            $result = $this->asrFileAppService->processSummaryWithChat($summaryRequest, $userAuthorization);

            if (! $result['success']) {
                return $this->buildSummaryResponse(false, $summaryRequest, $result['error']);
            }

            return $this->buildSummaryResponse(true, $summaryRequest, null, $result);
        } catch (Throwable $e) {
            $this->logger->error('ASRsummaryhandleexception', [
                'task_key' => $summaryRequest->taskKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->buildSummaryResponse(false, $summaryRequest, sprintf('handleexception: %s', $e->getMessage()));
        }
    }

    /**
     * getASRrecordingfileuploadSTS Token
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

        // 2. getminutedistributetypelock(preventandhaircreatedirectory)
        $lockName = sprintf('asr:upload_token:lock:%s:%s', $userId, $taskKey);
        $lockOwner = sprintf('%s:%s', $userId, microtime(true));
        $locked = $this->locker->spinLock($lockName, $lockOwner);

        if (! $locked) {
            ExceptionBuilder::throw(AsrErrorCode::SystemBusy);
        }

        try {
            // 3. create .asr_recordings parentdirectory( haverecordingtypeallneed)
            try {
                $recordingsDir = $this->directoryService->createRecordingsDirectory($organizationCode, $projectId, $userId);
                $this->logger->info('.asr_recordings parentdirectorycreateorconfirmexistsin', [
                    'task_key' => $taskKey,
                    'recording_type' => $recordingType->value,
                    'recordings_dir_id' => $recordingsDir->directoryId,
                    'recordings_dir_path' => $recordingsDir->directoryPath,
                ]);
            } catch (Throwable $e) {
                // .asr_recordings directorycreatefailnotimpactmainprocess
                $this->logger->warning('create .asr_recordings parentdirectoryfail', [
                    'task_key' => $taskKey,
                    'recording_type' => $recordingType->value,
                    'error' => $e->getMessage(),
                ]);
            }

            // 4. create .asr_states directory( haverecordingtypeallneed)
            try {
                $statesDir = $this->directoryService->createStatesDirectory($organizationCode, $projectId, $userId);
                $this->logger->info('.asr_states directorycreateorconfirmexistsin', [
                    'task_key' => $taskKey,
                    'recording_type' => $recordingType->value,
                    'states_dir_id' => $statesDir->directoryId,
                    'states_dir_path' => $statesDir->directoryPath,
                ]);
            } catch (Throwable $e) {
                // .asr_states directorycreatefailnotimpactmainprocess
                $this->logger->warning('create .asr_states directoryfail', [
                    'task_key' => $taskKey,
                    'recording_type' => $recordingType->value,
                    'error' => $e->getMessage(),
                ]);
            }

            // 5. in advancegeneratetitle(forincreatedirectoryo clockuse)
            $generatedTitle = null;
            // getcurrentstatusbycheckwhetheralreadyexistsintitle
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
                        $this->logger->info('filedirect uploadtitlegeneratesuccess', [
                            'task_key' => $taskKey,
                            'file_name' => $fileName,
                            'generated_title' => $generatedTitle,
                        ]);
                    }
                } catch (Throwable $e) {
                    // titlegeneratefailnotimpactmainprocess
                    $this->logger->warning('filedirect uploadtitlegeneratefail', [
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

            // 7. createpresetfile(ifalsonotcreate,andrecordingtypeneedpresetfile)
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
                    // presetfilecreatefailnotimpactmainprocess
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
     * recordingstatusupreportinterface
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

        // getlanguagetype
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
                sprintf('invalidstatus,validvalue:%s', implode(', ', ['start', 'recording', 'paused', 'stopped', 'canceled']))
            );
        }

        // statuscheck:getcurrenttaskstatus
        $taskStatus = $this->asrFileAppService->getTaskStatusFromRedis($taskKey, $userId);
        if (! $taskStatus->isEmpty()) {
            if ($taskStatus->hasServerSummaryLock()) {
                $this->logger->info('reportStatus serviceclientsummaryconductmiddle,rejectstatusupreport', [
                    'task_key' => $taskKey,
                    'user_id' => $userId,
                    'retry_count' => $taskStatus->serverSummaryRetryCount,
                ]);
                ExceptionBuilder::throw(AsrErrorCode::TaskIsSummarizing);
            }

            // statuscheck 1:taskalreadycomplete,notallowreportstatus(unlessis canceled)
            if ($statusEnum !== AsrRecordingStatusEnum::CANCELED && $taskStatus->isSummaryCompleted()) {
                ExceptionBuilder::throw(AsrErrorCode::TaskAlreadyCompleted);
            }

            // statuscheck 2:taskalreadycancel,notallowagainreportotherstatus
            if (
                $taskStatus->recordingStatus === AsrRecordingStatusEnum::CANCELED->value
                && $statusEnum !== AsrRecordingStatusEnum::CANCELED
            ) {
                ExceptionBuilder::throw(AsrErrorCode::TaskAlreadyCanceled);
            }

            // statuscheck 3:recordingalreadystopandalreadymerge,notallowagain start/recording(maybeiscorejumptimeoutfromautostop)
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
     * validateandbuildsummaryrequestDTO.
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

        // ifhavefile_idandnothavetask_key,generateone
        if (! empty($fileId) && empty($taskKey)) {
            $taskKey = uniqid('', true);
        }

        // validaterequired parameterparameter
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

        // handlenote
        $note = $this->parseNoteData($noteData);

        // generatetitle:priorityfrom Redis middleduplicateuse upload-tokens generatetitle
        $generatedTitle = null;

        // 1. tryfrom Redis middlegetalreadygeneratetitle(filedirect uploadscenario)
        if (! empty($taskKey)) {
            $taskStatus = $this->asrFileAppService->getTaskStatusFromRedis($taskKey, $userAuthorization->getId());
            if (! empty($taskStatus->uploadGeneratedTitle) && ! $taskStatus->isEmpty()) {
                $generatedTitle = $taskStatus->uploadGeneratedTitle;
                $this->logger->info('duplicateuse upload-tokens generatetitle', [
                    'task_key' => $taskKey,
                    'title' => $generatedTitle,
                ]);
            }
        }

        // 2. ifnothavefrom Redis gettotitle,thenreloadnewgenerate(frontclientrecordingoroldlogic)
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
     * parsenotedata.
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
                sprintf('not supportedfiletype: %s,supporttype: txt, md, json', $noteFileType)
            );
        }

        return $note;
    }

    /**
     * buildsummaryresponse.
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

        // validaterecordingtypeparameter(optional,defaultfor file_upload)
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

        // getfilename(onlyin file_upload typeo clockuse)
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
            // theonetimecall:createnewtaskstatus
            return $this->createNewTaskStatus($taskKey, $topicId, $projectId, $userId, $organizationCode, $generatedTitle);
        }

        if ($taskStatus->hasServerSummaryLock()) {
            $this->logger->info('getUploadToken serviceclientsummaryconductmiddle,rejecthairputuploadvoucher', [
                'task_key' => $taskKey,
                'user_id' => $userId,
                'retry_count' => $taskStatus->serverSummaryRetryCount,
            ]);
            ExceptionBuilder::throw(AsrErrorCode::TaskIsSummarizing);
        }

        // statuscheck 1:taskalreadycomplete,notallowupload
        if ($taskStatus->isSummaryCompleted()) {
            ExceptionBuilder::throw(AsrErrorCode::TaskAlreadyCompleted);
        }

        // statuscheck 2:taskalreadycancel,notallowupload
        if ($taskStatus->recordingStatus === AsrRecordingStatusEnum::CANCELED->value) {
            ExceptionBuilder::throw(AsrErrorCode::TaskAlreadyCanceled);
        }

        // statuscheck 3:recordingalreadystop,notallowupload(maybeiscorejumptimeoutfromautostop)
        if (
            $taskStatus->recordingStatus === AsrRecordingStatusEnum::STOPPED->value
            && ! empty($taskStatus->audioFileId)
        ) {
            ExceptionBuilder::throw(AsrErrorCode::TaskAutoStoppedByTimeout);
        }

        // backcontinuecall:updaterequiredwantfield
        $this->asrFileAppService->validateProjectAccess($projectId, $userId, $organizationCode);
        $taskStatus->projectId = $projectId;
        $taskStatus->topicId = $topicId;

        $this->logger->info('backcontinuecall getUploadToken,usealreadyhavedirectory', [
            'task_key' => $taskKey,
            'hidden_directory' => $taskStatus->tempHiddenDirectory,
            'display_directory' => $taskStatus->displayDirectory,
            'recording_status' => $taskStatus->recordingStatus,
        ]);

        return $taskStatus;
    }

    /**
     * createnewtaskstatus.
     */
    private function createNewTaskStatus(
        string $taskKey,
        string $topicId,
        string $projectId,
        string $userId,
        string $organizationCode,
        ?string $generatedTitle = null
    ): AsrTaskStatusDTO {
        $this->logger->info('theonetimecall getUploadToken,createnewdirectory', [
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

        // onlywhenpresetfileexistsino clockonlyaddtoreturnmiddle
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

        // notefile
        if (! empty($taskStatus->presetNoteFileId) && ! empty($taskStatus->presetNoteFilePath)) {
            $relativePath = AsrAssembler::extractWorkspaceRelativePath($taskStatus->presetNoteFilePath);
            $fileName = basename($relativePath);

            $presetFiles['note_file'] = [
                'file_id' => $taskStatus->presetNoteFileId,
                'file_name' => $fileName,
                'file_path' => $relativePath,
            ];
        }

        // streamidentifyfile
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
     * fromdirectoryarraymiddlefindfingersettypedirectory.
     */
    private function findDirectoryByType(array $directories, bool $hidden): ?AsrRecordingDirectoryDTO
    {
        return array_find($directories, static fn ($directory) => $directory->hidden === $hidden);
    }
}

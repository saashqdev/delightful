<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Service;

use App\Application\Speech\Assembler\AsrAssembler;
use App\Application\Speech\DTO\AsrSandboxMergeResultDTO;
use App\Application\Speech\DTO\AsrTaskStatusDTO;
use App\Application\Speech\Enum\AsrTaskStatusEnum;
use App\Application\Speech\Enum\SandboxAsrStatusEnum;
use App\Domain\Asr\Constants\AsrConfig;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\ErrorCode\AsrErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\ChatInstruction;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\InitializationMetadataDTO;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskContext;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\BeAgent\Service\AgentDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Constant\WorkspaceStatus;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\AsrRecorderInterface;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrAudioConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrNoteFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrTranscriptFileConfig;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Response\AsrRecorderResponse;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\ResponseCode;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayInterface;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Translation\trans;

/**
 * ASR sandboxservice
 * responsiblesandboxtaskstart,merge,round询andfilerecordcreate.
 */
readonly class AsrSandboxService
{
    private LoggerInterface $logger;

    public function __construct(
        private SandboxGatewayInterface $sandboxGateway,
        private AsrRecorderInterface $asrRecorder,
        private AsrSandboxResponseHandler $responseHandler,
        private ProjectDomainService $projectDomainService,
        private TaskFileDomainService $taskFileDomainService,
        private AgentDomainService $agentDomainService,
        private TopicDomainService $topicDomainService,
        private TaskDomainService $taskDomainService,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get('AsrSandboxService');
    }

    /**
     * startrecordingtask.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $userId userID
     * @param string $organizationCode organizationencoding
     */
    public function startRecordingTask(
        AsrTaskStatusDTO $taskStatus,
        string $userId,
        string $organizationCode
    ): void {
        // generatesandboxID
        $sandboxId = WorkDirectoryUtil::generateUniqueCodeFromSnowflakeId(
            $taskStatus->projectId . '_asr_recording',
            12
        );
        $taskStatus->sandboxId = $sandboxId;

        // settinguserupdown文
        $this->sandboxGateway->setUserContext($userId, $organizationCode);

        // getcompleteworkdirectorypath
        $projectEntity = $this->projectDomainService->getProject((int) $taskStatus->projectId, $userId);
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($organizationCode);
        $fullWorkdir = WorkDirectoryUtil::getFullWorkdir($fullPrefix, $projectEntity->getWorkDir());

        // createsandboxandetc待work区canuse
        $actualSandboxId = $this->ensureSandboxWorkspaceReady(
            $taskStatus,
            $sandboxId,
            $taskStatus->projectId,
            $fullWorkdir,
            $userId,
            $organizationCode
        );

        $this->logger->info('startRecordingTask ASR recording:sandboxalreadythen绪', [
            'task_key' => $taskStatus->taskKey,
            'requested_sandbox_id' => $sandboxId,
            'actual_sandbox_id' => $actualSandboxId,
            'full_workdir' => $fullWorkdir,
        ]);

        // buildfileconfigurationobject(复usepublicmethod)
        $noteFileConfig = $this->buildNoteFileConfig($taskStatus);
        $transcriptFileConfig = $this->buildTranscriptFileConfig($taskStatus);

        $this->logger->info('preparecallsandbox start interface', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $actualSandboxId,
            'temp_hidden_directory' => $taskStatus->tempHiddenDirectory,
            'workspace' => '.workspace',
            'note_file_config' => $noteFileConfig?->toArray(),
            'transcript_file_config' => $transcriptFileConfig?->toArray(),
        ]);

        // callsandboxstarttask
        // notice:sandbox API onlyacceptworkregiontopath (如: .asr_recordings/session_xxx)
        $response = $this->asrRecorder->startTask(
            $actualSandboxId,
            $taskStatus->taskKey,
            $taskStatus->tempHiddenDirectory,  // 如: .asr_recordings/session_xxx
            '.workspace',
            $noteFileConfig,
            $transcriptFileConfig
        );

        if (! $response->isSuccess()) {
            ExceptionBuilder::throw(AsrErrorCode::SandboxTaskCreationFailed, '', ['message' => $response->message]);
        }

        $taskStatus->sandboxTaskCreated = true;

        $this->logger->info('ASR recording:sandboxtaskalreadycreate', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $actualSandboxId,
            'status' => $response->getStatus(),
        ]);
    }

    /**
     * cancelrecordingtask.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @return AsrRecorderResponse responseresult
     */
    public function cancelRecordingTask(AsrTaskStatusDTO $taskStatus): AsrRecorderResponse
    {
        $sandboxId = $taskStatus->sandboxId;

        if (empty($sandboxId)) {
            ExceptionBuilder::throw(AsrErrorCode::SandboxIdNotExist);
        }

        $this->logger->info('ASR recording:preparecancelsandboxtask', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $sandboxId,
        ]);

        // callsandboxcanceltask
        $response = $this->asrRecorder->cancelTask(
            $sandboxId,
            $taskStatus->taskKey
        );

        if (! $response->isSuccess()) {
            ExceptionBuilder::throw(AsrErrorCode::SandboxCancelFailed, '', ['message' => $response->message]);
        }

        $this->logger->info('ASR recording:sandboxtaskalreadycancel', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $sandboxId,
            'status' => $response->getStatus(),
        ]);

        return $response;
    }

    /**
     * mergeaudiofile.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $fileTitle filetitle(not containextension名)
     * @param string $organizationCode organizationencoding
     * @return AsrSandboxMergeResultDTO mergeresult
     */
    public function mergeAudioFiles(
        AsrTaskStatusDTO $taskStatus,
        string $fileTitle,
        string $organizationCode
    ): AsrSandboxMergeResultDTO {
        $this->logger->info('startsandboxaudiohandleprocess', [
            'task_key' => $taskStatus->taskKey,
            'project_id' => $taskStatus->projectId,
            'hidden_directory' => $taskStatus->tempHiddenDirectory,
            'display_directory' => $taskStatus->displayDirectory,
            'sandbox_id' => $taskStatus->sandboxId,
        ]);

        // preparesandboxID
        if (empty($taskStatus->sandboxId)) {
            $sandboxId = WorkDirectoryUtil::generateUniqueCodeFromSnowflakeId(
                $taskStatus->projectId . '_asr_recording',
                12
            );
            $taskStatus->sandboxId = $sandboxId;
        }

        // settinguserupdown文
        $this->sandboxGateway->setUserContext($taskStatus->userId, $organizationCode);

        // getcompleteworkdirectorypath
        $projectEntity = $this->projectDomainService->getProject((int) $taskStatus->projectId, $taskStatus->userId);
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($organizationCode);
        $fullWorkdir = WorkDirectoryUtil::getFullWorkdir($fullPrefix, $projectEntity->getWorkDir());

        $requestedSandboxId = $taskStatus->sandboxId;
        $actualSandboxId = $this->ensureSandboxWorkspaceReady(
            $taskStatus,
            $requestedSandboxId,
            $taskStatus->projectId,
            $fullWorkdir,
            $taskStatus->userId,
            $organizationCode
        );

        // updateactualsandboxID(maybealready经change)
        if ($actualSandboxId !== $requestedSandboxId) {
            $this->logger->warning('sandboxIDhair生change,maybeissandboxrestart', [
                'task_key' => $taskStatus->taskKey,
                'old_sandbox_id' => $requestedSandboxId,
                'new_sandbox_id' => $actualSandboxId,
            ]);
        }

        $this->logger->info('sandboxalreadythen绪,preparecall finish', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $actualSandboxId,
            'full_workdir' => $fullWorkdir,
        ]);

        // callsandbox finish andround询etc待complete(willpassresponsehandle器from动create/updatefilerecord)
        $mergeResult = $this->callSandboxFinishAndWait($taskStatus, $fileTitle);

        $this->logger->info('sandboxreturnfileinfo', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_file_path' => $mergeResult->filePath,
            'audio_file_id' => $taskStatus->audioFileId,
            'note_file_id' => $taskStatus->noteFileId,
        ]);

        // updatetaskstatus(filerecordalreadybyresponsehandle器create)
        $taskStatus->updateStatus(AsrTaskStatusEnum::COMPLETED);

        $this->logger->info('sandboxaudiohandlecomplete', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $taskStatus->sandboxId,
            'file_id' => $taskStatus->audioFileId,
            'file_path' => $taskStatus->filePath,
        ]);

        return $mergeResult;
    }

    /**
     * callsandbox finish andround询etc待complete.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $intelligentTitle 智cantitle(useatrename)
     * @return AsrSandboxMergeResultDTO mergeresult
     */
    private function callSandboxFinishAndWait(
        AsrTaskStatusDTO $taskStatus,
        string $intelligentTitle,
    ): AsrSandboxMergeResultDTO {
        $sandboxId = $taskStatus->sandboxId;

        if (empty($sandboxId)) {
            ExceptionBuilder::throw(AsrErrorCode::SandboxIdNotExist);
        }

        // buildaudioconfigurationobject
        $audioConfig = new AsrAudioConfig(
            sourceDir: $taskStatus->tempHiddenDirectory,  // 如: .asr_recordings/session_xxx
            targetDir: $taskStatus->displayDirectory,     // 如: recordingsummary_20251027_230949
            outputFilename: $intelligentTitle              // 如: behate courage
        );

        // buildnotefileconfigurationobject(needrename)
        $noteFileConfig = $this->buildNoteFileConfig(
            $taskStatus,
            $taskStatus->displayDirectory,
            $intelligentTitle
        );

        // buildstreamidentifyfileconfigurationobject
        $transcriptFileConfig = $this->buildTranscriptFileConfig($taskStatus);

        $this->logger->info('preparecallsandbox finish', [
            'task_key' => $taskStatus->taskKey,
            'intelligent_title' => $intelligentTitle,
            'audio_config' => $audioConfig->toArray(),
            'note_file_config' => $noteFileConfig?->toArray(),
            'transcript_file_config' => $transcriptFileConfig?->toArray(),
        ]);

        // recordstarttime
        $finishStartTime = microtime(true);

        // 首timecall finish
        $response = $this->asrRecorder->finishTask(
            $sandboxId,
            $taskStatus->taskKey,
            '.workspace',
            $audioConfig,
            $noteFileConfig,
            $transcriptFileConfig
        );

        // round询etc待complete(based onpresettimeandsleepbetween隔)
        $timeoutSeconds = AsrConfig::SANDBOX_MERGE_TIMEOUT;
        $pollingInterval = AsrConfig::POLLING_INTERVAL;
        $attempt = 0;
        $lastLogTime = $finishStartTime;
        $logInterval = AsrConfig::SANDBOX_MERGE_LOG_INTERVAL;

        while (true) {
            $elapsedSeconds = (int) (microtime(true) - $finishStartTime);

            if ($elapsedSeconds >= $timeoutSeconds) {
                break;
            }

            ++$attempt;

            $statusString = $response->getStatus();
            $status = SandboxAsrStatusEnum::from($statusString);

            // checkcompletestatusorerrorstatus
            $result = $this->checkAndHandleResponseStatus(
                $response,
                $status,
                $taskStatus,
                $sandboxId,
                $finishStartTime,
                $attempt
            );
            if ($result !== null) {
                return $result;
            }

            // middlebetweenstatus(waiting, running, finalizing):continueround询and按between隔recordenterdegree
            $currentTime = microtime(true);
            $elapsedSeconds = (int) ($currentTime - $finishStartTime);
            if ($attempt % AsrConfig::SANDBOX_MERGE_LOG_FREQUENCY === 0 || ($currentTime - $lastLogTime) >= $logInterval) {
                $remainingSeconds = max(0, $timeoutSeconds - $elapsedSeconds);
                $this->logger->info('etc待sandboxaudiomerge', [
                    'task_key' => $taskStatus->taskKey,
                    'sandbox_id' => $sandboxId,
                    'attempt' => $attempt,
                    'elapsed_seconds' => $elapsedSeconds,
                    'remaining_seconds' => $remainingSeconds,
                    'status' => $status->value ?? $statusString,
                    'status_description' => $status->getDescription(),
                ]);
                $lastLogTime = $currentTime;
            }

            // timenot足,notagain sleep,directlyconductmostbackonetime finishTask
            if (($elapsedSeconds + $pollingInterval) >= $timeoutSeconds) {
                break;
            }

            sleep($pollingInterval);

            // continueround询
            $response = $this->asrRecorder->finishTask(
                $sandboxId,
                $taskStatus->taskKey,
                '.workspace',
                $audioConfig,
                $noteFileConfig,
                $transcriptFileConfig
            );
        }

        // time即willexhausted,conductmostbackonetimecheck
        $statusString = $response->getStatus();
        $status = SandboxAsrStatusEnum::from($statusString);
        $result = $this->checkAndHandleResponseStatus(
            $response,
            $status,
            $taskStatus,
            $sandboxId,
            $finishStartTime,
            $attempt
        );
        if ($result !== null) {
            return $result;
        }

        // timeoutrecord
        $totalElapsedTime = (int) (microtime(true) - $finishStartTime);
        $this->logger->error('sandboxaudiomergetimeout', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $sandboxId,
            'total_attempts' => $attempt,
            'total_elapsed_seconds' => $totalElapsedTime,
            'timeout_seconds' => $timeoutSeconds,
            'last_status' => $status->value ?? $statusString,
        ]);

        ExceptionBuilder::throw(AsrErrorCode::SandboxMergeTimeout);
    }

    /**
     * checkandhandlesandboxresponsestatus.
     *
     * @param AsrRecorderResponse $response sandboxresponse
     * @param SandboxAsrStatusEnum $status statusenum
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $sandboxId sandboxID
     * @param float $finishStartTime starttime
     * @param int $attempt trycount
     * @return null|AsrSandboxMergeResultDTO ifcompletethenreturnresult,nothenreturnnull
     * @throws BusinessException ifiserrorstatusthenthrowexception
     */
    private function checkAndHandleResponseStatus(
        AsrRecorderResponse $response,
        SandboxAsrStatusEnum $status,
        AsrTaskStatusDTO $taskStatus,
        string $sandboxId,
        float $finishStartTime,
        int $attempt
    ): ?AsrSandboxMergeResultDTO {
        // checkwhetherforcompletestatus(contain completed and finished)
        if ($status->isCompleted()) {
            // calculatetotal consumptiono clock
            $finishEndTime = microtime(true);
            $totalElapsedTime = round($finishEndTime - $finishStartTime);

            $this->logger->info('sandboxaudiomergecomplete', [
                'task_key' => $taskStatus->taskKey,
                'sandbox_id' => $sandboxId,
                'attempt' => $attempt,
                'status' => $status->value,
                'file_path' => $response->getFilePath(),
                'total_elapsed_time_seconds' => $totalElapsedTime,
            ]);

            // handlesandboxresponse,updatefileanddirectoryrecord
            $responseData = $response->getData();
            $this->responseHandler->handleFinishResponse(
                $taskStatus,
                $responseData,
            );

            return AsrSandboxMergeResultDTO::fromSandboxResponse([
                'status' => $status->value,
                'file_path' => $response->getFilePath(),
                'duration' => $response->getDuration(),
                'file_size' => $response->getFileSize(),
            ]);
        }

        // checkwhetherforerrorstatus
        if ($status->isError()) {
            ExceptionBuilder::throw(AsrErrorCode::SandboxMergeFailed, '', ['message' => $response->getErrorMessage()]);
        }

        return null;
    }

    /**
     * etc待sandboxstart(canresponseinterface).
     * ASR featurenotneedwork区initialize,onlyneedsandboxcanresponse getWorkspaceStatus interface即can.
     *
     * @param string $sandboxId sandboxID
     * @param string $taskKey taskKey(useatlog)
     * @throws BusinessException whentimeouto clockthrowexception
     */
    private function waitForSandboxStartup(
        string $sandboxId,
        string $taskKey
    ): void {
        $this->logger->info('ASR recording:etc待sandboxstart', [
            'task_key' => $taskKey,
            'sandbox_id' => $sandboxId,
            'timeout_seconds' => AsrConfig::SANDBOX_STARTUP_TIMEOUT,
            'interval_seconds' => AsrConfig::POLLING_INTERVAL,
        ]);

        $startTime = time();
        $endTime = $startTime + AsrConfig::SANDBOX_STARTUP_TIMEOUT;

        while (time() < $endTime) {
            try {
                // trygetwork区status,as long asinterfacecansuccessreturntheninstructionsandboxalreadystart
                $response = $this->agentDomainService->getWorkspaceStatus($sandboxId);
                $status = $response->getDataValue('status');

                $this->logger->info('ASR recording:sandboxalreadystartandcanresponse', [
                    'task_key' => $taskKey,
                    'sandbox_id' => $sandboxId,
                    'status' => $status,
                    'status_description' => WorkspaceStatus::getDescription($status),
                    'elapsed_seconds' => time() - $startTime,
                ]);

                // interfacesuccessreturn,sandboxalreadystart
                return;
            } catch (Throwable $e) {
                // interfacecallfail,instructionsandboxalsonotstart,continueetc待
                $this->logger->debug('ASR recording:sandbox尚notstart,continueetc待', [
                    'task_key' => $taskKey,
                    'sandbox_id' => $sandboxId,
                    'error' => $e->getMessage(),
                    'elapsed_seconds' => time() - $startTime,
                ]);

                // etc待downonetimeround询
                sleep(AsrConfig::POLLING_INTERVAL);
            }
        }

        // timeout
        $this->logger->error('ASR recording:etc待sandboxstarttimeout', [
            'task_key' => $taskKey,
            'sandbox_id' => $sandboxId,
            'timeout_seconds' => AsrConfig::SANDBOX_STARTUP_TIMEOUT,
        ]);

        ExceptionBuilder::throw(
            AsrErrorCode::SandboxTaskCreationFailed,
            '',
            ['message' => 'etc待sandboxstarttimeout(' . AsrConfig::SANDBOX_STARTUP_TIMEOUT . 'second)']
        );
    }

    /**
     * pass AgentDomainService createsandboxandetc待work区then绪.
     */
    private function ensureSandboxWorkspaceReady(
        AsrTaskStatusDTO $taskStatus,
        string $requestedSandboxId,
        ?string $projectId,
        string $fullWorkdir,
        string $userId,
        string $organizationCode
    ): string {
        if ($requestedSandboxId === '') {
            ExceptionBuilder::throw(AsrErrorCode::SandboxIdNotExist);
        }

        $projectIdString = (string) $projectId;
        if ($projectIdString === '') {
            ExceptionBuilder::throw(AsrErrorCode::SandboxTaskCreationFailed, '', ['message' => 'projectIDfornull,no法createsandbox']);
        }

        // trygetwork区status
        $workspaceStatusResponse = null;
        try {
            $workspaceStatusResponse = $this->agentDomainService->getWorkspaceStatus($requestedSandboxId);
        } catch (Throwable $throwable) {
            $this->logger->warning('getsandboxwork区statusfail,sandboxmaybenotstart,willcreatenewsandbox', [
                'task_key' => $taskStatus->taskKey,
                'sandbox_id' => $requestedSandboxId,
                'error' => $throwable->getMessage(),
            ]);
        }

        // ifwork区statusresponse存in,checkwhetherneedinitialize
        if ($workspaceStatusResponse !== null) {
            $responseCode = $workspaceStatusResponse->getCode();
            $workspaceStatus = (int) $workspaceStatusResponse->getDataValue('status');

            // ifresponsesuccess(code 1000)andwork区alreadythen绪,directlyreturn
            if ($responseCode === ResponseCode::SUCCESS && WorkspaceStatus::isReady($workspaceStatus)) {
                $this->logger->info('detecttosandboxwork区alreadythen绪,no需initialize', [
                    'task_key' => $taskStatus->taskKey,
                    'sandbox_id' => $requestedSandboxId,
                    'status' => $workspaceStatus,
                    'status_description' => WorkspaceStatus::getDescription($workspaceStatus),
                ]);

                $taskStatus->sandboxId = $requestedSandboxId;

                return $requestedSandboxId;
            }

            // ifresponsesuccessbutwork区notinitialize,orresponsefail,needinitializework区
            $this->logger->info('detecttosandboxwork区notinitializeorresponseexception,needinitializework区', [
                'task_key' => $taskStatus->taskKey,
                'sandbox_id' => $requestedSandboxId,
                'response_code' => $responseCode,
                'workspace_status' => $workspaceStatus,
                'status_description' => WorkspaceStatus::getDescription($workspaceStatus),
            ]);

            $taskStatus->sandboxId = $requestedSandboxId;
            $this->initializeWorkspace($taskStatus, $requestedSandboxId, $userId, $organizationCode);

            return $requestedSandboxId;
        }

        // work区statusresponsenot存in,sandboxnotstart,needcreatesandbox
        $dataIsolation = DataIsolation::simpleMake($organizationCode, $userId);

        $this->logger->info('preparecall AgentDomainService createsandbox', [
            'task_key' => $taskStatus->taskKey,
            'project_id' => $projectIdString,
            'requested_sandbox_id' => $requestedSandboxId,
            'full_workdir' => $fullWorkdir,
        ]);

        $actualSandboxId = $this->agentDomainService->createSandbox(
            $dataIsolation,
            $projectIdString,
            $requestedSandboxId,
            $fullWorkdir
        );

        $this->logger->info('sandboxcreaterequestcomplete,etc待sandboxstart', [
            'task_key' => $taskStatus->taskKey,
            'requested_sandbox_id' => $requestedSandboxId,
            'actual_sandbox_id' => $actualSandboxId,
        ]);

        // etc待sandboxstart(canresponseinterface)
        $this->waitForSandboxStartup($actualSandboxId, $taskStatus->taskKey);

        $taskStatus->sandboxId = $actualSandboxId;

        // initializework区
        $this->initializeWorkspace($taskStatus, $actualSandboxId, $userId, $organizationCode);

        return $actualSandboxId;
    }

    /**
     * initializework区.
     * 复use AgentDomainService::initializeAgent method,ensureinitializeconfigurationone致.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $actualSandboxId actualsandboxID
     * @param string $userId userID
     * @param string $organizationCode organizationencoding
     */
    private function initializeWorkspace(
        AsrTaskStatusDTO $taskStatus,
        string $actualSandboxId,
        string $userId,
        string $organizationCode
    ): void {
        $this->logger->info('sandboxalreadystart,preparesendinitializemessage', [
            'task_key' => $taskStatus->taskKey,
            'actual_sandbox_id' => $actualSandboxId,
            'topic_id' => $taskStatus->topicId,
        ]);

        // getorcreate Task Entity(useatbuild TaskContext)
        $taskEntity = $this->getOrCreateTaskEntity($taskStatus, $userId, $organizationCode);

        $this->logger->info('getto ASR task Entity', [
            'task_key' => $taskStatus->taskKey,
            'topic_id' => $taskStatus->topicId,
            'task_id' => $taskEntity->getId(),
        ]);

        // create DataIsolation
        $dataIsolation = DataIsolation::simpleMake($organizationCode, $userId);

        // get topic 实body(useatget workspaceId etcinfo)
        $topicEntity = $this->topicDomainService->getTopicById((int) $taskStatus->topicId);
        if (! $topicEntity) {
            ExceptionBuilder::throw(
                AsrErrorCode::SandboxTaskCreationFailed,
                '',
                ['message' => 'topicnot存in']
            );
        }

        // getproject实body(useatgetprojectorganizationencoding)
        $projectEntity = $this->projectDomainService->getProject((int) $taskStatus->projectId, $userId);
        $projectOrganizationCode = $projectEntity->getUserOrganizationCode();

        // certain agentUserId:use topic create者ID,ifnothavethenuse topic userID(reference AgentAppService)
        $agentUserId = $topicEntity->getCreatedUid() ?: $topicEntity->getUserId();

        // build TaskContext(ASR scenariomiddle chatConversationId,chatTopicId usenullstring)
        $taskContext = new TaskContext(
            task: $taskEntity,
            dataIsolation: $dataIsolation,
            chatConversationId: '', // ASR scenarionotneedchatconversationID
            chatTopicId: '', // ASR scenarionotneedchatthemeID
            agentUserId: $agentUserId, // use topic create者IDoruserID
            sandboxId: $actualSandboxId,
            taskId: (string) $taskEntity->getId(),
            instruction: ChatInstruction::Normal,
            agentMode: $topicEntity->getTaskMode() ?: 'general',
            workspaceId: (string) $topicEntity->getWorkspaceId(),
            isFirstTask: false, // ASR scenariousuallynotis首timetask
        );

        // 复use initializeAgent method(willfrom动build message_subscription_config and delightful_service_host)
        // pass inprojectorganizationencoding,useatgetcorrect STS Token
        // ASR scenariosetting skip_init_messages = true,letsandboxnotsendchatmessagepasscome
        $initMetadata = (new InitializationMetadataDTO(skipInitMessages: true));
        $this->agentDomainService->initializeAgent($dataIsolation, $taskContext, null, $projectOrganizationCode, $initMetadata);

        $this->logger->info('sandboxinitializemessagealreadysend,etc待work区initializecomplete', [
            'task_key' => $taskStatus->taskKey,
            'actual_sandbox_id' => $actualSandboxId,
            'task_id' => $taskEntity->getId(),
        ]);

        // etc待work区initializecomplete(includefilesync)
        $this->agentDomainService->waitForWorkspaceReady(
            $actualSandboxId,
            AsrConfig::WORKSPACE_INIT_TIMEOUT,
            AsrConfig::POLLING_INTERVAL
        );

        $this->logger->info('sandboxwork区alreadyinitializecomplete,filealreadysync,canstartuse', [
            'task_key' => $taskStatus->taskKey,
            'actual_sandbox_id' => $actualSandboxId,
            'task_id' => $taskEntity->getId(),
        ]);

        // updatetopicstatusforalreadycomplete(match DDD minutelayer,pass Domain Service 操as)
        $this->topicDomainService->updateTopicStatus(
            (int) $taskStatus->topicId,
            $taskEntity->getId(),
            TaskStatus::FINISHED
        );

        $this->logger->info('topicstatusalreadyupdatefor finished', [
            'task_key' => $taskStatus->taskKey,
            'topic_id' => $taskStatus->topicId,
            'task_id' => $taskEntity->getId(),
        ]);
    }

    /**
     * buildnotefileconfigurationobject.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param null|string $targetDirectory goaldirectory(optional,defaultand源directorysame)
     * @param null|string $intelligentTitle 智cantitle(optional,useatrename)
     */
    private function buildNoteFileConfig(
        AsrTaskStatusDTO $taskStatus,
        ?string $targetDirectory = null,
        ?string $intelligentTitle = null
    ): ?AsrNoteFileConfig {
        if (empty($taskStatus->presetNoteFilePath)) {
            return null;
        }

        $workspaceRelativePath = AsrAssembler::extractWorkspaceRelativePath($taskStatus->presetNoteFilePath);

        // ifnotfinger定goaldirectory,use源path(notrename)
        if ($targetDirectory === null || $intelligentTitle === null) {
            return new AsrNoteFileConfig(
                sourcePath: $workspaceRelativePath,
                targetPath: $workspaceRelativePath
            );
        }

        // needrename:use智cantitleandinternationalizationnoteback缀buildgoalpath
        $fileExtension = pathinfo($workspaceRelativePath, PATHINFO_EXTENSION);
        $noteSuffix = trans('asr.file_names.note_suffix'); // according tolanguagegetinternationalization"note"/"Note"
        $noteFilename = sprintf('%s-%s.%s', $intelligentTitle, $noteSuffix, $fileExtension);

        return new AsrNoteFileConfig(
            sourcePath: $workspaceRelativePath,
            targetPath: rtrim($targetDirectory, '/') . '/' . $noteFilename
        );
    }

    /**
     * buildstreamidentifyfileconfigurationobject.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     */
    private function buildTranscriptFileConfig(AsrTaskStatusDTO $taskStatus): ?AsrTranscriptFileConfig
    {
        if (empty($taskStatus->presetTranscriptFilePath)) {
            return null;
        }

        $transcriptWorkspaceRelativePath = AsrAssembler::extractWorkspaceRelativePath(
            $taskStatus->presetTranscriptFilePath
        );

        return new AsrTranscriptFileConfig(
            sourcePath: $transcriptWorkspaceRelativePath
        );
    }

    /**
     * getorcreate Task Entity(useatbuild TaskContext).
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $userId userID
     * @param string $organizationCode organizationencoding
     * @return TaskEntity Task Entity
     */
    private function getOrCreateTaskEntity(
        AsrTaskStatusDTO $taskStatus,
        string $userId,
        string $organizationCode
    ): TaskEntity {
        // check topicId whether存in
        if (empty($taskStatus->topicId)) {
            $this->logger->error('ASR taskmissing topicId,no法getorcreate task', [
                'task_key' => $taskStatus->taskKey,
                'project_id' => $taskStatus->projectId,
            ]);
            ExceptionBuilder::throw(
                AsrErrorCode::SandboxTaskCreationFailed,
                '',
                ['message' => 'Topic ID fornull,no法createsandboxtask']
            );
        }

        // get topic 实body
        $topicEntity = $this->topicDomainService->getTopicById((int) $taskStatus->topicId);
        if (! $topicEntity) {
            $this->logger->error('ASR taskassociate topic not存in', [
                'task_key' => $taskStatus->taskKey,
                'topic_id' => $taskStatus->topicId,
            ]);
            ExceptionBuilder::throw(
                AsrErrorCode::SandboxTaskCreationFailed,
                '',
                ['message' => 'topicnot存in']
            );
        }

        // check topic whetherhavecurrenttask
        $currentTaskId = $topicEntity->getCurrentTaskId();
        if ($currentTaskId !== null && $currentTaskId > 0) {
            $taskEntity = $this->taskDomainService->getTaskById($currentTaskId);
            if ($taskEntity) {
                $this->logger->info('ASR taskuse topic currenttask Entity', [
                    'task_key' => $taskStatus->taskKey,
                    'topic_id' => $taskStatus->topicId,
                    'current_task_id' => $currentTaskId,
                ]);
                return $taskEntity;
            }
        }

        // topic nothavecurrenttask,for ASR scenariocreateonenewtask
        $this->logger->info('ASR taskassociate topic nothavecurrenttask,preparecreatenewtask', [
            'task_key' => $taskStatus->taskKey,
            'topic_id' => $taskStatus->topicId,
            'project_id' => $taskStatus->projectId,
        ]);

        $dataIsolation = DataIsolation::simpleMake($organizationCode, $userId);

        $taskData = [
            'user_id' => $userId,
            'workspace_id' => $topicEntity->getWorkspaceId(),
            'project_id' => $topicEntity->getProjectId(),
            'topic_id' => $topicEntity->getId(),
            'task_id' => '', // databasewillfrom动generate
            'task_mode' => $topicEntity->getTaskMode() ?: 'general',
            'sandbox_id' => $topicEntity->getSandboxId() ?: '',
            'prompt' => 'ASR Recording Task', // ASR taskidentifier
            'task_status' => 'waiting',
            'work_dir' => $topicEntity->getWorkDir() ?? '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $taskEntity = TaskEntity::fromArray($taskData);

        // createtaskandupdate topic
        $createdTask = $this->taskDomainService->initTopicTask($dataIsolation, $topicEntity, $taskEntity);

        $this->logger->info('for ASR taskcreatenew task', [
            'task_key' => $taskStatus->taskKey,
            'topic_id' => $taskStatus->topicId,
            'created_task_id' => $createdTask->getId(),
        ]);

        return $createdTask;
    }
}

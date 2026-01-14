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
use BeDelightful\BeDelightful\Domain\BeAgent\Entity\TaskEntity;
use BeDelightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\ChatInstruction;
use BeDelightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\InitializationMetadataDTO;
use BeDelightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskContext;
use BeDelightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use BeDelightful\BeDelightful\Domain\BeAgent\Service\AgentDomainService;
use BeDelightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use BeDelightful\BeDelightful\Domain\BeAgent\Service\TaskDomainService;
use BeDelightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use BeDelightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use BeDelightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Constant\WorkspaceStatus;
use BeDelightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\AsrRecorderInterface;
use BeDelightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrAudioConfig;
use BeDelightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrNoteFileConfig;
use BeDelightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Config\AsrTranscriptFileConfig;
use BeDelightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\AsrRecorder\Response\AsrRecorderResponse;
use BeDelightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\ResponseCode;
use BeDelightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayInterface;
use BeDelightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Translation\trans;

/**
 * ASR sandboxservice
 * responsiblesandboxtaskstart,merge,roundqueryandfilerecordcreate.
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

        // settinguserupdowntext
        $this->sandboxGateway->setUserContext($userId, $organizationCode);

        // getcompleteworkdirectorypath
        $projectEntity = $this->projectDomainService->getProject((int) $taskStatus->projectId, $userId);
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($organizationCode);
        $fullWorkdir = WorkDirectoryUtil::getFullWorkdir($fullPrefix, $projectEntity->getWorkDir());

        // createsandboxandetcpendingworkregioncanuse
        $actualSandboxId = $this->ensureSandboxWorkspaceReady(
            $taskStatus,
            $sandboxId,
            $taskStatus->projectId,
            $fullWorkdir,
            $userId,
            $organizationCode
        );

        $this->logger->info('startRecordingTask ASR recording:sandboxalreadythenemotion', [
            'task_key' => $taskStatus->taskKey,
            'requested_sandbox_id' => $sandboxId,
            'actual_sandbox_id' => $actualSandboxId,
            'full_workdir' => $fullWorkdir,
        ]);

        // buildfileconfigurationobject(duplicateusepublicmethod)
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
        // notice:sandbox API onlyacceptworkregiontopath (like: .asr_recordings/session_xxx)
        $response = $this->asrRecorder->startTask(
            $actualSandboxId,
            $taskStatus->taskKey,
            $taskStatus->tempHiddenDirectory,  // like: .asr_recordings/session_xxx
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
     * @param string $fileTitle filetitle(not containextensionname)
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

        // settinguserupdowntext
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

        // updateactualsandboxID(maybealreadyalreadychange)
        if ($actualSandboxId !== $requestedSandboxId) {
            $this->logger->warning('sandboxIDhairgeneratechange,maybeissandboxrestart', [
                'task_key' => $taskStatus->taskKey,
                'old_sandbox_id' => $requestedSandboxId,
                'new_sandbox_id' => $actualSandboxId,
            ]);
        }

        $this->logger->info('sandboxalreadythenemotion,preparecall finish', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_id' => $actualSandboxId,
            'full_workdir' => $fullWorkdir,
        ]);

        // callsandbox finish androundqueryetcpendingcomplete(willpassresponsehandledevicefromautocreate/updatefilerecord)
        $mergeResult = $this->callSandboxFinishAndWait($taskStatus, $fileTitle);

        $this->logger->info('sandboxreturnfileinfo', [
            'task_key' => $taskStatus->taskKey,
            'sandbox_file_path' => $mergeResult->filePath,
            'audio_file_id' => $taskStatus->audioFileId,
            'note_file_id' => $taskStatus->noteFileId,
        ]);

        // updatetaskstatus(filerecordalreadybyresponsehandledevicecreate)
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
     * callsandbox finish androundqueryetcpendingcomplete.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $intelligentTitle intelligencecantitle(useatrename)
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
            sourceDir: $taskStatus->tempHiddenDirectory,  // like: .asr_recordings/session_xxx
            targetDir: $taskStatus->displayDirectory,     // like: recordingsummary_20251027_230949
            outputFilename: $intelligentTitle              // like: behate courage
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

        // firsttimecall finish
        $response = $this->asrRecorder->finishTask(
            $sandboxId,
            $taskStatus->taskKey,
            '.workspace',
            $audioConfig,
            $noteFileConfig,
            $transcriptFileConfig
        );

        // roundqueryetcpendingcomplete(based onpresettimeandsleepbetweenseparator)
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

            // middlebetweenstatus(waiting, running, finalizing):continueroundqueryandbybetweenseparatorrecordenterdegree
            $currentTime = microtime(true);
            $elapsedSeconds = (int) ($currentTime - $finishStartTime);
            if ($attempt % AsrConfig::SANDBOX_MERGE_LOG_FREQUENCY === 0 || ($currentTime - $lastLogTime) >= $logInterval) {
                $remainingSeconds = max(0, $timeoutSeconds - $elapsedSeconds);
                $this->logger->info('etcpendingsandboxaudiomerge', [
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

            // timenotenough,notagain sleep,directlyconductmostbackonetime finishTask
            if (($elapsedSeconds + $pollingInterval) >= $timeoutSeconds) {
                break;
            }

            sleep($pollingInterval);

            // continueroundquery
            $response = $this->asrRecorder->finishTask(
                $sandboxId,
                $taskStatus->taskKey,
                '.workspace',
                $audioConfig,
                $noteFileConfig,
                $transcriptFileConfig
            );
        }

        // timeimmediatelywillexhausted,conductmostbackonetimecheck
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
     * etcpendingsandboxstart(canresponseinterface).
     * ASR featurenotneedworkregioninitialize,onlyneedsandboxcanresponse getWorkspaceStatus interfaceimmediatelycan.
     *
     * @param string $sandboxId sandboxID
     * @param string $taskKey taskKey(useatlog)
     * @throws BusinessException whentimeouto clockthrowexception
     */
    private function waitForSandboxStartup(
        string $sandboxId,
        string $taskKey
    ): void {
        $this->logger->info('ASR recording:etcpendingsandboxstart', [
            'task_key' => $taskKey,
            'sandbox_id' => $sandboxId,
            'timeout_seconds' => AsrConfig::SANDBOX_STARTUP_TIMEOUT,
            'interval_seconds' => AsrConfig::POLLING_INTERVAL,
        ]);

        $startTime = time();
        $endTime = $startTime + AsrConfig::SANDBOX_STARTUP_TIMEOUT;

        while (time() < $endTime) {
            try {
                // trygetworkregionstatus,as long asinterfacecansuccessreturntheninstructionsandboxalreadystart
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
                // interfacecallfail,instructionsandboxalsonotstart,continueetcpending
                $this->logger->debug('ASR recording:sandboxstillnotstart,continueetcpending', [
                    'task_key' => $taskKey,
                    'sandbox_id' => $sandboxId,
                    'error' => $e->getMessage(),
                    'elapsed_seconds' => time() - $startTime,
                ]);

                // etcpendingdownonetimeroundquery
                sleep(AsrConfig::POLLING_INTERVAL);
            }
        }

        // timeout
        $this->logger->error('ASR recording:etcpendingsandboxstarttimeout', [
            'task_key' => $taskKey,
            'sandbox_id' => $sandboxId,
            'timeout_seconds' => AsrConfig::SANDBOX_STARTUP_TIMEOUT,
        ]);

        ExceptionBuilder::throw(
            AsrErrorCode::SandboxTaskCreationFailed,
            '',
            ['message' => 'etcpendingsandboxstarttimeout(' . AsrConfig::SANDBOX_STARTUP_TIMEOUT . 'second)']
        );
    }

    /**
     * pass AgentDomainService createsandboxandetcpendingworkregionthenemotion.
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
            ExceptionBuilder::throw(AsrErrorCode::SandboxTaskCreationFailed, '', ['message' => 'projectIDfornull,nomethodcreatesandbox']);
        }

        // trygetworkregionstatus
        $workspaceStatusResponse = null;
        try {
            $workspaceStatusResponse = $this->agentDomainService->getWorkspaceStatus($requestedSandboxId);
        } catch (Throwable $throwable) {
            $this->logger->warning('getsandboxworkregionstatusfail,sandboxmaybenotstart,willcreatenewsandbox', [
                'task_key' => $taskStatus->taskKey,
                'sandbox_id' => $requestedSandboxId,
                'error' => $throwable->getMessage(),
            ]);
        }

        // ifworkregionstatusresponseexistsin,checkwhetherneedinitialize
        if ($workspaceStatusResponse !== null) {
            $responseCode = $workspaceStatusResponse->getCode();
            $workspaceStatus = (int) $workspaceStatusResponse->getDataValue('status');

            // ifresponsesuccess(code 1000)andworkregionalreadythenemotion,directlyreturn
            if ($responseCode === ResponseCode::SUCCESS && WorkspaceStatus::isReady($workspaceStatus)) {
                $this->logger->info('detecttosandboxworkregionalreadythenemotion,noneedinitialize', [
                    'task_key' => $taskStatus->taskKey,
                    'sandbox_id' => $requestedSandboxId,
                    'status' => $workspaceStatus,
                    'status_description' => WorkspaceStatus::getDescription($workspaceStatus),
                ]);

                $taskStatus->sandboxId = $requestedSandboxId;

                return $requestedSandboxId;
            }

            // ifresponsesuccessbutworkregionnotinitialize,orresponsefail,needinitializeworkregion
            $this->logger->info('detecttosandboxworkregionnotinitializeorresponseexception,needinitializeworkregion', [
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

        // workregionstatusresponsenotexistsin,sandboxnotstart,needcreatesandbox
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

        $this->logger->info('sandboxcreaterequestcomplete,etcpendingsandboxstart', [
            'task_key' => $taskStatus->taskKey,
            'requested_sandbox_id' => $requestedSandboxId,
            'actual_sandbox_id' => $actualSandboxId,
        ]);

        // etcpendingsandboxstart(canresponseinterface)
        $this->waitForSandboxStartup($actualSandboxId, $taskStatus->taskKey);

        $taskStatus->sandboxId = $actualSandboxId;

        // initializeworkregion
        $this->initializeWorkspace($taskStatus, $actualSandboxId, $userId, $organizationCode);

        return $actualSandboxId;
    }

    /**
     * initializeworkregion.
     * duplicateuse AgentDomainService::initializeAgent method,ensureinitializeconfigurationoneto.
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

        // get topic actualbody(useatget workspaceId etcinfo)
        $topicEntity = $this->topicDomainService->getTopicById((int) $taskStatus->topicId);
        if (! $topicEntity) {
            ExceptionBuilder::throw(
                AsrErrorCode::SandboxTaskCreationFailed,
                '',
                ['message' => 'topicnotexistsin']
            );
        }

        // getprojectactualbody(useatgetprojectorganizationencoding)
        $projectEntity = $this->projectDomainService->getProject((int) $taskStatus->projectId, $userId);
        $projectOrganizationCode = $projectEntity->getUserOrganizationCode();

        // certain agentUserId:use topic createpersonID,ifnothavethenuse topic userID(reference AgentAppService)
        $agentUserId = $topicEntity->getCreatedUid() ?: $topicEntity->getUserId();

        // build TaskContext(ASR scenariomiddle chatConversationId,chatTopicId usenullstring)
        $taskContext = new TaskContext(
            task: $taskEntity,
            dataIsolation: $dataIsolation,
            chatConversationId: '', // ASR scenarionotneedchatconversationID
            chatTopicId: '', // ASR scenarionotneedchatthemeID
            agentUserId: $agentUserId, // use topic createpersonIDoruserID
            sandboxId: $actualSandboxId,
            taskId: (string) $taskEntity->getId(),
            instruction: ChatInstruction::Normal,
            agentMode: $topicEntity->getTaskMode() ?: 'general',
            workspaceId: (string) $topicEntity->getWorkspaceId(),
            isFirstTask: false, // ASR scenariousuallynotisfirsttimetask
        );

        // duplicateuse initializeAgent method(willfromautobuild message_subscription_config and delightful_service_host)
        // pass inprojectorganizationencoding,useatgetcorrect STS Token
        // ASR scenariosetting skip_init_messages = true,letsandboxnotsendchatmessagepasscome
        $initMetadata = (new InitializationMetadataDTO(skipInitMessages: true));
        $this->agentDomainService->initializeAgent($dataIsolation, $taskContext, null, $projectOrganizationCode, $initMetadata);

        $this->logger->info('sandboxinitializemessagealreadysend,etcpendingworkregioninitializecomplete', [
            'task_key' => $taskStatus->taskKey,
            'actual_sandbox_id' => $actualSandboxId,
            'task_id' => $taskEntity->getId(),
        ]);

        // etcpendingworkregioninitializecomplete(includefilesync)
        $this->agentDomainService->waitForWorkspaceReady(
            $actualSandboxId,
            AsrConfig::WORKSPACE_INIT_TIMEOUT,
            AsrConfig::POLLING_INTERVAL
        );

        $this->logger->info('sandboxworkregionalreadyinitializecomplete,filealreadysync,canstartuse', [
            'task_key' => $taskStatus->taskKey,
            'actual_sandbox_id' => $actualSandboxId,
            'task_id' => $taskEntity->getId(),
        ]);

        // updatetopicstatusforalreadycomplete(match DDD minutelayer,pass Domain Service operationas)
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
     * @param null|string $targetDirectory goaldirectory(optional,defaultandsourcedirectorysame)
     * @param null|string $intelligentTitle intelligencecantitle(optional,useatrename)
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

        // ifnotfingersetgoaldirectory,usesourcepath(notrename)
        if ($targetDirectory === null || $intelligentTitle === null) {
            return new AsrNoteFileConfig(
                sourcePath: $workspaceRelativePath,
                targetPath: $workspaceRelativePath
            );
        }

        // needrename:useintelligencecantitleandinternationalizationnotebacksuffixbuildgoalpath
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
        // check topicId whetherexistsin
        if (empty($taskStatus->topicId)) {
            $this->logger->error('ASR taskmissing topicId,nomethodgetorcreate task', [
                'task_key' => $taskStatus->taskKey,
                'project_id' => $taskStatus->projectId,
            ]);
            ExceptionBuilder::throw(
                AsrErrorCode::SandboxTaskCreationFailed,
                '',
                ['message' => 'Topic ID fornull,nomethodcreatesandboxtask']
            );
        }

        // get topic actualbody
        $topicEntity = $this->topicDomainService->getTopicById((int) $taskStatus->topicId);
        if (! $topicEntity) {
            $this->logger->error('ASR taskassociate topic notexistsin', [
                'task_key' => $taskStatus->taskKey,
                'topic_id' => $taskStatus->topicId,
            ]);
            ExceptionBuilder::throw(
                AsrErrorCode::SandboxTaskCreationFailed,
                '',
                ['message' => 'topicnotexistsin']
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
            'task_id' => '', // databasewillfromautogenerate
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

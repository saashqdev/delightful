<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Speech\Service;

use App\Application\Speech\Assembler\AsrAssembler;
use App\Application\Speech\DTO\AsrTaskStatusDTO;
use App\Domain\Asr\Constants\AsrConfig;
use App\ErrorCode\AsrErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * ASR sandboxresponsehandleservice
 * responsiblehandlesandbox finish interfaceresponse,updatefileanddirectoryrecord.
 */
readonly class AsrSandboxResponseHandler
{
    public function __construct(
        private AsrPresetFileService $presetFileService,
        private TaskFileDomainService $taskFileDomainService,
        private ProjectDomainService $projectDomainService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * handlesandbox finish response,updatefileanddirectoryrecord.
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param array $sandboxResponse sandboxresponsedata(data departmentminute)
     */
    public function handleFinishResponse(
        AsrTaskStatusDTO $taskStatus,
        array $sandboxResponse,
    ): void {
        $this->logger->info('starthandlesandbox finish response', [
            'task_key' => $taskStatus->taskKey,
            'response_keys' => array_keys($sandboxResponse),
        ]);

        // 1. extractfileinfo
        $audioFile = $sandboxResponse['files']['audio_file'] ?? null;
        $noteFile = $sandboxResponse['files']['note_file'] ?? null;

        if ($audioFile === null) {
            $this->logger->warning('sandboxresponsemiddlenotfindtoaudiofileinfo', [
                'task_key' => $taskStatus->taskKey,
            ]);
            return;
        }

        // 2. checkandhandledirectoryrename(sandboxhavebug,willrenamedirectorybutisnothavenotifyfilechange,nothavechangedatabaserecord)
        $taskStatus->displayDirectory = $this->extractDirectoryPath($audioFile);

        // 3. findaudiofilerecord
        $this->getAudioFileId($taskStatus, $audioFile);

        // 4. handlenotefile
        if ($noteFile !== null) {
            // pass file_key findmostnewnotefile ID(directorymaybeberename)
            $this->getNoteFileId($taskStatus, $noteFile);
        } else {
            // notefilefornullornotexistsin,deletepresetnotefilerecord
            $this->handleEmptyNoteFile($taskStatus);
        }

        $this->logger->info('sandbox finish responsehandlecomplete', [
            'task_key' => $taskStatus->taskKey,
            'audio_file_id' => $taskStatus->audioFileId,
            'note_file_id' => $taskStatus->noteFileId,
            'display_directory' => $taskStatus->displayDirectory,
        ]);
    }

    /**
     * fromfilepathextractdirectorypath.
     *
     * @param array $fileInfo fileinfo
     * @return string directorypath(workregiontopath)
     */
    private function extractDirectoryPath(array $fileInfo): string
    {
        $filePath = $fileInfo['path'] ?? '';
        if (empty($filePath)) {
            return '';
        }

        // fromfilepathextractactualdirectoryname
        return dirname($filePath);
    }

    /**
     * according toresponseaudiofilename/filepath,findtoaudiofile id,useatbackcontinuehairchatmessage.
     * useroundinquiry mechanismetcpendingsandboxsyncfiletodatabase(at mostetcpending 30 second).
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param array $audioFile audiofileinfo
     */
    private function getAudioFileId(
        AsrTaskStatusDTO $taskStatus,
        array $audioFile
    ): void {
        $relativePath = $audioFile['path'] ?? '';

        if (empty($relativePath)) {
            $this->logger->warning('audiofilepathfornull,nomethodqueryfilerecord', [
                'task_key' => $taskStatus->taskKey,
                'audio_file' => $audioFile,
            ]);
            return;
        }

        try {
            $fileEntity = $this->findFileByPathWithPolling(
                $taskStatus,
                $relativePath,
                'audiofile'
            );

            if ($fileEntity !== null) {
                $taskStatus->audioFileId = (string) $fileEntity->getFileId();
                $taskStatus->filePath = $relativePath;
            }
        } catch (Throwable $e) {
            $this->logger->error('queryaudiofilerecordfail', [
                'task_key' => $taskStatus->taskKey,
                'relative_path' => $relativePath,
                'error' => $e->getMessage(),
            ]);

            // ifiswefromselfthrowexception,directlyreloadnewthrow
            if ($e instanceof BusinessException) {
                throw $e;
            }

            ExceptionBuilder::throw(AsrErrorCode::CreateAudioFileFailed, '', ['error' => $e->getMessage()]);
        }
    }

    /**
     * according toresponsenotefilepath,findtonotefile id.
     * useroundinquiry mechanismetcpendingsandboxsyncfiletodatabase(at mostetcpending 30 second).
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param array $noteFile notefileinfo
     */
    private function getNoteFileId(
        AsrTaskStatusDTO $taskStatus,
        array $noteFile
    ): void {
        $relativePath = $noteFile['path'] ?? '';

        if (empty($relativePath)) {
            $this->logger->warning('notefilepathfornull,clearnullnotefileID', [
                'task_key' => $taskStatus->taskKey,
            ]);
            $taskStatus->noteFileId = null;
            $taskStatus->noteFileName = null;
            return;
        }

        try {
            $fileEntity = $this->findFileByPathWithPolling(
                $taskStatus,
                $relativePath,
                'notefile',
                false // notefilequeryfailnotthrowexception
            );

            if ($fileEntity !== null) {
                $taskStatus->noteFileId = (string) $fileEntity->getFileId();
                $taskStatus->noteFileName = $noteFile['filename'] ?? $noteFile['path'] ?? '';

                $this->logger->info('successfindtonotefilerecord', [
                    'task_key' => $taskStatus->taskKey,
                    'note_file_id' => $taskStatus->noteFileId,
                    'note_file_name' => $taskStatus->noteFileName,
                    'old_preset_note_file_id' => $taskStatus->presetNoteFileId,
                ]);
            } else {
                // notfindtothenclearnull,notusepresetID
                $this->logger->warning('notfindtonotefilerecord', [
                    'task_key' => $taskStatus->taskKey,
                    'relative_path' => $relativePath,
                ]);
                $taskStatus->noteFileId = null;
                $taskStatus->noteFileName = null;
            }
        } catch (Throwable $e) {
            // notefilequeryfail,clearnullnotefileinfo
            $this->logger->warning('querynotefilerecordfail', [
                'task_key' => $taskStatus->taskKey,
                'relative_path' => $relativePath,
                'error' => $e->getMessage(),
            ]);
            $taskStatus->noteFileId = null;
            $taskStatus->noteFileName = null;
        }
    }

    /**
     * passfilepathroundqueryqueryfilerecord(commonusemethod).
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     * @param string $relativePath filerelatedtopath
     * @param string $fileTypeName filetypename(useatlog)
     * @param bool $throwOnTimeout timeoutwhetherthrowexception
     * @return null|TaskFileEntity fileactualbody,notfindtoreturnnull
     * @throws Throwable
     */
    private function findFileByPathWithPolling(
        AsrTaskStatusDTO $taskStatus,
        string $relativePath,
        string $fileTypeName,
        bool $throwOnTimeout = true
    ): ?TaskFileEntity {
        // checkrequiredwanttaskstatusfield
        if (empty($taskStatus->projectId) || empty($taskStatus->userId) || empty($taskStatus->organizationCode)) {
            $this->logger->error('taskstatusinfonotcomplete,nomethodqueryfilerecord', [
                'task_key' => $taskStatus->taskKey,
                'file_type' => $fileTypeName,
                'project_id' => $taskStatus->projectId,
                'user_id' => $taskStatus->userId,
                'organization_code' => $taskStatus->organizationCode,
            ]);
            ExceptionBuilder::throw(AsrErrorCode::CreateAudioFileFailed, '', ['error' => 'taskstatusinfonotcomplete']);
        }

        // getprojectinfoandbuild file_key
        $projectEntity = $this->projectDomainService->getProject(
            (int) $taskStatus->projectId,
            $taskStatus->userId
        );
        $workDir = $projectEntity->getWorkDir();
        $fullPrefix = $this->taskFileDomainService->getFullPrefix($taskStatus->organizationCode);
        $fileKey = AsrAssembler::buildFileKey($fullPrefix, $workDir, $relativePath);

        $this->logger->info(sprintf('startroundqueryquery%srecord', $fileTypeName), [
            'task_key' => $taskStatus->taskKey,
            'file_type' => $fileTypeName,
            'relative_path' => $relativePath,
            'file_key' => $fileKey,
            'project_id' => $taskStatus->projectId,
            'max_wait_seconds' => AsrConfig::FILE_RECORD_QUERY_TIMEOUT,
        ]);

        // roundqueryqueryfilerecord
        $timeoutSeconds = AsrConfig::FILE_RECORD_QUERY_TIMEOUT;
        $pollingInterval = AsrConfig::POLLING_INTERVAL;
        $startTime = microtime(true);
        $attempt = 0;

        while (true) {
            ++$attempt;
            $elapsedSeconds = (int) (microtime(true) - $startTime);

            // queryfilerecord
            $existingFile = $this->taskFileDomainService->getByProjectIdAndFileKey(
                (int) $taskStatus->projectId,
                $fileKey
            );

            if ($existingFile !== null) {
                $this->logger->info(sprintf('successfindto%srecord', $fileTypeName), [
                    'task_key' => $taskStatus->taskKey,
                    'file_type' => $fileTypeName,
                    'file_id' => $existingFile->getFileId(),
                    'file_name' => $existingFile->getFileName(),
                    'file_key' => $fileKey,
                    'attempt' => $attempt,
                    'elapsed_seconds' => $elapsedSeconds,
                ]);
                return $existingFile;
            }

            // checkwhethertimeout
            if ($elapsedSeconds >= $timeoutSeconds) {
                break;
            }

            // recordroundqueryenterdegree
            if ($attempt % AsrConfig::FILE_RECORD_QUERY_LOG_FREQUENCY === 0 || $attempt === 1) {
                $remainingSeconds = max(0, $timeoutSeconds - $elapsedSeconds);
                $this->logger->info(sprintf('etcpendingsandboxsync%stodatabase', $fileTypeName), [
                    'task_key' => $taskStatus->taskKey,
                    'file_type' => $fileTypeName,
                    'file_key' => $fileKey,
                    'attempt' => $attempt,
                    'elapsed_seconds' => $elapsedSeconds,
                    'remaining_seconds' => $remainingSeconds,
                ]);
            }

            // etcpendingdownonetimeroundquery
            sleep($pollingInterval);
        }

        // roundquerytimeout,stillnotfindtofilerecord
        $totalElapsedTime = (int) (microtime(true) - $startTime);
        $this->logger->warning(sprintf('roundquerytimeout,notfindto%srecord', $fileTypeName), [
            'task_key' => $taskStatus->taskKey,
            'file_type' => $fileTypeName,
            'file_key' => $fileKey,
            'relative_path' => $relativePath,
            'project_id' => $taskStatus->projectId,
            'total_attempts' => $attempt,
            'total_elapsed_seconds' => $totalElapsedTime,
            'timeout_seconds' => $timeoutSeconds,
        ]);

        if ($throwOnTimeout) {
            // throwexception
            ExceptionBuilder::throw(
                AsrErrorCode::CreateAudioFileFailed,
                '',
                ['error' => sprintf('etcpending %d secondbackstillnotfindto%srecord', $timeoutSeconds, $fileTypeName)]
            );
        }

        return null;
    }

    /**
     * handlenullnotefile(deletepresetnotefilerecord).
     *
     * @param AsrTaskStatusDTO $taskStatus taskstatus
     */
    private function handleEmptyNoteFile(AsrTaskStatusDTO $taskStatus): void
    {
        $noteFileId = $taskStatus->presetNoteFileId;
        if (empty($noteFileId)) {
            $this->logger->debug('presetnotefileIDfornull,noneeddelete', [
                'task_key' => $taskStatus->taskKey,
            ]);
            return;
        }

        $this->logger->info('notefilefornullornotexistsin,deletepresetnotefilerecord', [
            'task_key' => $taskStatus->taskKey,
            'note_file_id' => $noteFileId,
        ]);

        $deleted = $this->presetFileService->deleteNoteFile($noteFileId);
        if ($deleted) {
            // clearnulltaskstatusmiddlenotefilerelatedclosefield
            $taskStatus->presetNoteFileId = null;
            $taskStatus->presetNoteFilePath = null;
            $taskStatus->noteFileId = null;
            $taskStatus->noteFileName = null;

            $this->logger->info('nullnotefilehandlecomplete', [
                'task_key' => $taskStatus->taskKey,
            ]);
        }
    }
}

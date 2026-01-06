    /**
     * Internal helper to create directories (shared logic).
     *
     * @param string $organizationCode Organization code
     * @param string $projectId Project ID
     * @param string $userId User ID
     * @param string $relativePath Relative path
     * @param AsrDirectoryTypeEnum $directoryType Directory type
     * @param bool $isHidden Whether the directory is hidden
     * @param null|string $taskKey Task key
     * @param array $errorContext Error log context
     * @param string $logMessage Error log message
     * @param AsrErrorCode $failedProjectError Project-level error code
     * @param AsrErrorCode $failedError Generic error code
     * @return AsrRecordingDirectoryDTO Directory DTO
     */
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileDomainService;
use Hyperf\Contract\TranslatorInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * ASR directory management service
 * Handles directory creation, lookup, rename, and path conversion.
 */
readonly class AsrDirectoryService
{
    public function __construct(
        private ProjectDomainService $projectDomainService,
        private TaskFileDomainService $taskFileDomainService,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Create a hidden temporary recording directory (stores chunk files).
     * Directory format: .asr_recordings/{task_key}.
     *
     * @param string $organizationCode Organization code
     * @param string $projectId Project ID
     * @param string $userId User ID
     * @param string $taskKey Task key
     * @return AsrRecordingDirectoryDTO Directory DTO
     */
    public function createHiddenDirectory(
        string $organizationCode,
        string $projectId,
        string $userId,
        string $taskKey
    ): AsrRecordingDirectoryDTO {
        $relativePath = AsrPaths::getHiddenDirPath($taskKey);

        return $this->createDirectoryInternal(
            organizationCode: $organizationCode,
            projectId: $projectId,
            userId: $userId,
            relativePath: $relativePath,
            directoryType: AsrDirectoryTypeEnum::ASR_HIDDEN_DIR,
            isHidden: true,
            taskKey: $taskKey,
            errorContext: ['project_id' => $projectId, 'task_key' => $taskKey],
            logMessage: 'Failed to create hidden recording directory',
            failedProjectError: AsrErrorCode::CreateHiddenDirectoryFailedProject,
            failedError: AsrErrorCode::CreateHiddenDirectoryFailedError
        );
    }

    /**
     * Create the hidden .asr_states directory (stores front-end recording status).
     * Directory format: .asr_states.
     *
     * @param string $organizationCode Organization code
     * @param string $projectId Project ID
     * @param string $userId User ID
     * @return AsrRecordingDirectoryDTO Directory DTO
     */
    public function createStatesDirectory(
        string $organizationCode,
        string $projectId,
        string $userId
    ): AsrRecordingDirectoryDTO {
        $relativePath = AsrPaths::getStatesDirPath();

        return $this->createDirectoryInternal(
            organizationCode: $organizationCode,
            projectId: $projectId,
            userId: $userId,
            relativePath: $relativePath,
            directoryType: AsrDirectoryTypeEnum::ASR_STATES_DIR,
            isHidden: true,
            taskKey: null,
            errorContext: ['project_id' => $projectId],
            logMessage: 'Failed to create .asr_states directory',
            failedProjectError: AsrErrorCode::CreateStatesDirectoryFailedProject,
            failedError: AsrErrorCode::CreateStatesDirectoryFailedError
        );
    }

    /**
     * Create the hidden .asr_recordings parent directory (stores all recording task subdirectories).
     * Directory format: .asr_recordings.
     *
     * @param string $organizationCode Organization code
     * @param string $projectId Project ID
     * @param string $userId User ID
     * @return AsrRecordingDirectoryDTO Directory DTO
     */
    public function createRecordingsDirectory(
        string $organizationCode,
        string $projectId,
        string $userId
    ): AsrRecordingDirectoryDTO {
        $relativePath = AsrPaths::getRecordingsDirPath();

        return $this->createDirectoryInternal(
            organizationCode: $organizationCode,
            projectId: $projectId,
            userId: $userId,
            relativePath: $relativePath,
            directoryType: AsrDirectoryTypeEnum::ASR_RECORDINGS_DIR,
            isHidden: true,
            taskKey: null,
            errorContext: ['project_id' => $projectId],
            logMessage: 'Failed to create .asr_recordings directory',
            failedProjectError: AsrErrorCode::CreateStatesDirectoryFailedProject,
            failedError: AsrErrorCode::CreateStatesDirectoryFailedError
        );
    }

    /**
     * Create a visible recording summary directory (stores streaming text and notes).
     * Directory format: Recording_Summary_Ymd_His (localized).
     *
     * @param string $organizationCode Organization code
     * @param string $projectId Project ID
     * @param string $userId User ID
     * @param null|string $generatedTitle Preset title
     * @return AsrRecordingDirectoryDTO Directory DTO
     */
    public function createDisplayDirectory(
        string $organizationCode,
        string $projectId,
        string $userId,
        ?string $generatedTitle = null
    ): AsrRecordingDirectoryDTO {
        $relativePath = $this->generateDirectoryName($generatedTitle);

        return $this->createDirectoryInternal(
            organizationCode: $organizationCode,
            projectId: $projectId,
            userId: $userId,
            relativePath: $relativePath,
            directoryType: AsrDirectoryTypeEnum::ASR_DISPLAY_DIR,
            isHidden: false, // Keep hidden to avoid front-end errors caused by directory changes during ASR operations
            taskKey: null,
            errorContext: ['project_id' => $projectId],
            logMessage: 'Failed to create display recording directory',
            failedProjectError: AsrErrorCode::CreateDisplayDirectoryFailedProject,
            failedError: AsrErrorCode::CreateDisplayDirectoryFailedError
        );
    }

    /**
     * Generate a new display directory name based on the intelligent title.
     * Only produces the new relative path; no rename happens here.
     *
     * @param AsrTaskStatusDTO $taskStatus Task status
     * @param string $intelligentTitle AI-generated title
     * @param AsrTitleGeneratorService $titleGenerator Title sanitizer
     * @return string New relative path (or the original if no rename is needed)
     */
    public function getNewDisplayDirectory(
        mixed $taskStatus,
        string $intelligentTitle,
        AsrTitleGeneratorService $titleGenerator
    ): string {
        // 1. Get the original display directory
        $relativeOldPath = $taskStatus->displayDirectory;

        if (empty($relativeOldPath)) {
            $this->logger->warning('Display directory path is empty; skip generating a new path', [
                'task_key' => $taskStatus->taskKey,
            ]);
            return $relativeOldPath;
        }

        // 2. Extract the timestamp
        $oldDirectoryName = basename($relativeOldPath);
        $timestamp = $this->extractTimestamp($oldDirectoryName, $taskStatus->taskKey);

        // 3. Sanitize and build the new directory name
        $safeTitle = $titleGenerator->sanitizeTitle($intelligentTitle);
        if (empty($safeTitle)) {
            $this->logger->warning('Intelligent title is empty; skip generating a new path', [
                'task_key' => $taskStatus->taskKey,
                'intelligent_title' => $intelligentTitle,
            ]);
            return $relativeOldPath;
        }

        $newDirectoryName = $safeTitle . $timestamp;

        // New workspace-relative path (e.g., Courage_to_be_Disliked_20251027_230949)
        $newRelativePath = $newDirectoryName;

        // If paths are identical, skip renaming
        if ($newRelativePath === $relativeOldPath) {
            $this->logger->info('Old and new directory paths are identical; skip renaming', [
                'task_key' => $taskStatus->taskKey,
                'directory_path' => $newRelativePath,
            ]);
            return $relativeOldPath;
        }

        $this->logger->info('Generated new display directory path', [
            'task_key' => $taskStatus->taskKey,
            'old_relative_path' => $relativeOldPath,
            'new_relative_path' => $newRelativePath,
            'intelligent_title' => $intelligentTitle,
        ]);

        return $newRelativePath;
    }

    /**
     * Get the workspace path for the project.
     *
     * @param string $projectId Project ID
     * @param string $userId User ID
     * @return string Workspace path
     */
    public function getWorkspacePath(string $projectId, string $userId): string
    {
        $projectEntity = $this->projectDomainService->getProject((int) $projectId, $userId);
        return rtrim($projectEntity->getWorkDir(), '/') . '/';
    }

    /**
     * Generate an ASR directory name.
     *
     * @param null|string $generatedTitle Preset title
     * @return string Directory name
     */
    private function generateDirectoryName(?string $generatedTitle = null): string
    {
        $base = $generatedTitle ?: $this->translator->trans('asr.directory.recordings_summary_folder');
        return sprintf('%s_%s', $base, date('Ymd_His'));
    }

    /**
     * Extract the timestamp from a directory name.
     *
     * @param string $directoryName Directory name
     * @param string $taskKey Task key (for logging)
     * @return string Timestamp suffix (format: _20251026_210626)
     */
    private function extractTimestamp(string $directoryName, string $taskKey): string
    {
        if (preg_match('/_(\d{8}_\d{6})$/', $directoryName, $matches)) {
            return '_' . $matches[1];
        }

        // If no timestamp is found, use the current time
        $this->logger->info('Original timestamp missing; use current time', [
            'task_key' => $taskKey,
            'old_directory_name' => $directoryName,
        ]);
        return '_' . date('Ymd_His');
    }

    /**
     * Internal helper to create directories (shared logic).
     *
     * @param string $organizationCode Organization code
     * @param string $projectId Project ID
     * @param string $userId User ID
     * @param string $relativePath Relative path
     * @param AsrDirectoryTypeEnum $directoryType Directory type
     * @param bool $isHidden Whether the directory is hidden
     * @param null|string $taskKey Task key
     * @param array $errorContext Error log context
     * @param string $logMessage Error log message
     * @param AsrErrorCode $failedProjectError Project-level error code
     * @param AsrErrorCode $failedError Generic error code
     * @return AsrRecordingDirectoryDTO Directory DTO
     */
    private function createDirectoryInternal(
        string $organizationCode,
        string $projectId,
        string $userId,
        string $relativePath,
        AsrDirectoryTypeEnum $directoryType,
        bool $isHidden,
        ?string $taskKey,
        array $errorContext,
        string $logMessage,
        AsrErrorCode $failedProjectError,
        AsrErrorCode $failedError
    ): AsrRecordingDirectoryDTO {
        try {
            // 1. Ensure the project workspace root directory exists
            $rootDirectoryId = $this->ensureWorkspaceRootDirectoryExists($organizationCode, $projectId, $userId);

            // 2. Fetch project details
            $projectEntity = $this->projectDomainService->getProject((int) $projectId, $userId);
            $workDir = $projectEntity->getWorkDir();
            $fullPrefix = $this->taskFileDomainService->getFullPrefix($organizationCode);

            // 3. Check whether the directory already exists
            $fileKey = AsrAssembler::buildFileKey($fullPrefix, $workDir, $relativePath);
            $fileKey = rtrim($fileKey, '/') . '/';
            $existingDir = $this->taskFileDomainService->getByProjectIdAndFileKey((int) $projectId, $fileKey);
            if ($existingDir !== null) {
                return new AsrRecordingDirectoryDTO(
                    $relativePath,
                    $existingDir->getFileId(),
                    $isHidden,
                    $directoryType
                );
            }

            // 4. Create the directory entity
            $taskFileEntity = AsrAssembler::createDirectoryEntity(
                $userId,
                $organizationCode,
                (int) $projectId,
                $relativePath,
                $fullPrefix,
                $workDir,
                $rootDirectoryId,
                isHidden: $isHidden,
                taskKey: $taskKey
            );

            // 5. Insert or ignore
            $result = $this->taskFileDomainService->insertOrIgnore($taskFileEntity);
            if ($result !== null) {
                return new AsrRecordingDirectoryDTO(
                    $relativePath,
                    $result->getFileId(),
                    $isHidden,
                    $directoryType
                );
            }

            // 6. If insert was ignored, fetch the existing directory
            $existingDir = $this->taskFileDomainService->getByProjectIdAndFileKey((int) $projectId, $fileKey);
            if ($existingDir !== null) {
                return new AsrRecordingDirectoryDTO(
                    $relativePath,
                    $existingDir->getFileId(),
                    $isHidden,
                    $directoryType
                );
            }

            ExceptionBuilder::throw($failedProjectError, '', ['projectId' => $projectId]);
        } catch (Throwable $e) {
            $this->logger->error($logMessage, array_merge($errorContext, ['error' => $e->getMessage()]));
            ExceptionBuilder::throw($failedError, '', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Ensure the workspace root directory exists.
     *
     * @param string $organizationCode Organization code
     * @param string $projectId Project ID
     * @param string $userId User ID
     * @return int File ID of the project workspace root directory
     */
    private function ensureWorkspaceRootDirectoryExists(string $organizationCode, string $projectId, string $userId): int
    {
        $projectEntity = $this->projectDomainService->getProject((int) $projectId, $userId);
        $workDir = $projectEntity->getWorkDir();

        if (empty($workDir)) {
            ExceptionBuilder::throw(AsrErrorCode::WorkspaceDirectoryEmpty, '', ['projectId' => $projectId]);
        }

        return $this->taskFileDomainService->findOrCreateProjectRootDirectory(
            (int) $projectId,
            $workDir,
            $userId,
            $organizationCode,
            $projectEntity->getUserOrganizationCode()
        );
    }
}

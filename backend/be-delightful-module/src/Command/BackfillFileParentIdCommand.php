<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Command;

use App\Infrastructure\Core\ValueObject\StorageBucketType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ProjectEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\ProjectRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TopicRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskFileModel;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskModel;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;

#[Command]
class BackfillFileParentIdCommand extends HyperfCommand
{
    protected ?string $name = 'be-delightful:backfill-file-parent-id';

    protected LoggerInterface $logger;

    public function __construct(
        protected ProjectRepositoryInterface $projectRepository,
        protected TopicRepositoryInterface $topicRepository,
        protected TaskFileDomainService $taskFileDomainService,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get('backfill-file-parent-id');
        parent::__construct();
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Backfill parent_id for existing files in delightful_be_agent_task_files table');
        $this->addArgument('project_id', InputArgument::OPTIONAL, 'Optional project ID to process only one project');
        $this->addArgument('organization_code', InputArgument::OPTIONAL, 'Optional organization code to process projects by organization');
    }

    public function handle()
    {
        $this->line('🚀 Starting backfill process for file parent_id...');
        $this->logger->info('Starting backfill process for file parent_id.');

        $projectId = $this->input->getArgument('project_id');
        $organizationCode = $this->input->getArgument('organization_code');

        // Initialize result tracking
        $startTime = date('Y-m-d H:i:s');
        $resultLog = [
            'start_time' => $startTime,
            'success_projects' => [],
            'failed_projects' => [],
            'skipped_projects' => [],
            'total_processed_files' => 0,
            'total_errors' => 0,
        ];

        try {
            // Get projects based on input parameter
            $projects = $this->getProjectsToProcess($projectId, $organizationCode);

            if (empty($projects)) {
                $this->error('❌ No projects found to process.');
                $this->writeResultsToFile($resultLog, 'No projects found');
                return;
            }

            $this->line(sprintf('📊 Found %d project(s) to process.', count($projects)));

            // Process each project
            foreach ($projects as $project) {
                $projectResult = $this->processProject($project);

                // Record result
                if ($projectResult['status'] === 'success') {
                    $resultLog['success_projects'][] = $projectResult;
                    $resultLog['total_processed_files'] += $projectResult['processed_files'];
                    $resultLog['total_errors'] += $projectResult['errors'];
                } elseif ($projectResult['status'] === 'failed') {
                    $resultLog['failed_projects'][] = $projectResult;
                } else {
                    $resultLog['skipped_projects'][] = $projectResult;
                }
            }

            $resultLog['end_time'] = date('Y-m-d H:i:s');
            $this->writeResultsToFile($resultLog, 'Completed successfully');

            $this->line('✅ Backfill process completed successfully!');
            $this->logger->info('Backfill process completed successfully.');
        } catch (Throwable $e) {
            $resultLog['end_time'] = date('Y-m-d H:i:s');
            $resultLog['error'] = $e->getMessage();
            $this->writeResultsToFile($resultLog, 'Process failed');

            $this->error(sprintf('❌ Backfill process failed: %s', $e->getMessage()));
            $this->logger->error(sprintf('Backfill process failed: %s', $e->getMessage()), [
                'exception' => $e,
                'project_id' => $projectId,
                'organization_code' => $organizationCode,
            ]);
        }
    }

    /**
     * Process file path based on storage type, convert old format path to new format
     * Simplified version: directly replace old prefix with new prefix, then add corresponding path segments.
     *
     * @param string $type Storage type (workspace or other)
     * @param string $fileKey Original file path
     * @param string $prefix New prefix, e.g.: DT001/588417216353927169
     * @param string $oldPrefix Old prefix, e.g.: DT001/588417216353927169/2c17c6393771ee3048ae34d6b380c5ec/BE_DELIGHTFUL/usi_xxx
     * @param bool $isDirectory Whether it is a directory
     * @return string Converted file path
     */
    public function handleFileKeyByType($type, $fileKey, $prefix, $oldPrefix, bool $isDirectory = false): string
    {
        $storageTypeValue = $type instanceof StorageType ? $type->value : $type;

        // Check if old prefix is included, return original path if not included
        if (strpos($fileKey, $oldPrefix . '/') !== 0) {
            return $fileKey;
        }

        // Remove old prefix, get relative path part
        $relativePath = substr($fileKey, strlen($oldPrefix . '/'));

        // Normalize relative path first, remove double slashes
        $relativePath = preg_replace('#/+#', '/', $relativePath);
        $relativePath = trim($relativePath, '/');

        if ($storageTypeValue == 'workspace') {
            // workspace type: add /workspace
            // Source: DT001/588417216353927169/2c17c6393771ee3048ae34d6b380c5ec/BE_DELIGHTFUL/usi_xxx/project_804590875311198209/new_file.php
            // Or: DT001/588417216353927169/2c17c6393771ee3048ae34d6b380c5ec/BE_DELIGHTFUL/usi_xxx/topic_804590875311198209/new_file.php
            // Target: DT001/588417216353927169/project_804590875311198209/workspace/new_file.php

            // Find the part starting with project_ or topic_
            $pathParts = explode('/', $relativePath);
            for ($i = 0; $i < count($pathParts); ++$i) {
                if (strpos($pathParts[$i], 'project_') === 0 || strpos($pathParts[$i], 'topic_') === 0) {
                    $entityName = $pathParts[$i];

                    // If topic_, need to convert to project_ format
                    if (strpos($entityName, 'topic_') === 0) {
                        $entityName = str_replace('topic_', 'project_', $entityName);
                    }

                    // Check if already contains workspace
                    if ($i + 1 < count($pathParts) && $pathParts[$i + 1] === 'workspace') {
                        // Already has workspace, preserve path after workspace
                        $remainingParts = array_slice($pathParts, $i + 2);
                        $finalPath = empty($remainingParts) ? '' : implode('/', $remainingParts);
                        return $this->normalizePath($prefix . '/' . $entityName . '/workspace/' . $finalPath, $isDirectory);
                    }
                    // Need to add workspace
                    $remainingParts = array_slice($pathParts, $i + 1);
                    $finalPath = empty($remainingParts) ? '' : implode('/', $remainingParts);
                    return $this->normalizePath($prefix . '/' . $entityName . '/workspace/' . $finalPath, $isDirectory);
                }
            }
        } else {
            // Non-workspace type: add /runtime/message
            // Source: DT001/588417216353927169/2c17c6393771ee3048ae34d6b380c5ec/BE_DELIGHTFUL/usi_xxx/project_808853145743884288/task_xxx/.chat/file.md
            // Or: DT001/588417216353927169/2c17c6393771ee3048ae34d6b380c5ec/BE_DELIGHTFUL/usi_xxx/topic_808853145743884288/task_xxx/.chat/file.md
            // Target: DT001/588417216353927169/project_808853145743884288/runtime/message/task_xxx/.chat/file.md

            // Find the part starting with project_ or topic_
            $pathParts = explode('/', $relativePath);
            for ($i = 0; $i < count($pathParts); ++$i) {
                if (strpos($pathParts[$i], 'project_') === 0 || strpos($pathParts[$i], 'topic_') === 0) {
                    $entityName = $pathParts[$i];

                    // If topic_, need to convert to project_ format
                    if (strpos($entityName, 'topic_') === 0) {
                        $entityName = str_replace('topic_', 'project_', $entityName);
                    }

                    $remainingParts = array_slice($pathParts, $i + 1);
                    $finalPath = empty($remainingParts) ? '' : implode('/', $remainingParts);

                    // Handle empty path, avoid double slashes
                    return $this->normalizePath($prefix . '/' . $entityName . '/runtime/message/' . $finalPath, $isDirectory);
                }
            }
        }

        // If cannot find project_ part, return original path
        return $fileKey;
    }

    /**
     * Get projects to process based on input parameter.
     *
     * @param null|string $projectId Optional project ID
     * @param null|string $organizationCode Optional organization code
     * @return ProjectEntity[] Array of project entities
     */
    private function getProjectsToProcess(?string $projectId, ?string $organizationCode): array
    {
        // Check if project_id is provided and not empty
        if ($projectId !== null && trim($projectId) !== '' && $projectId !== '-') {
            // Process single project
            $this->line(sprintf('🎯 Processing single project with ID: %s', $projectId));
            $project = $this->projectRepository->findById((int) $projectId);

            if ($project === null) {
                $this->error(sprintf('❌ Project with ID %s not found.', $projectId));
                return [];
            }

            return [$project];
        }

        // Prepare conditions for project filtering
        $conditions = [];
        if ($organizationCode !== null) {
            $conditions['user_organization_code'] = $organizationCode;
            $this->line(sprintf('🏢 Processing projects for organization: %s', $organizationCode));
        } else {
            $this->line('🌐 Processing all projects...');
        }

        // Process projects using pagination to avoid memory issues
        $allProjects = [];
        $page = 1;
        $pageSize = 100;

        do {
            $result = $this->projectRepository->getProjectsByConditions(
                conditions: $conditions,
                page: $page,
                pageSize: $pageSize,
                orderBy: 'id',
                orderDirection: 'asc'
            );

            if (empty($result['list'])) {
                break;
            }

            $projects = $result['list'] ?? [];
            $allProjects = array_merge($allProjects, $projects);

            $this->line(sprintf('📄 Loaded page %d with %d projects', $page, count($projects)));

            ++$page;
        } while (count($projects) === $pageSize);

        return $allProjects;
    }

    /**
     * Process a single project.
     *
     * @param ProjectEntity $project Project entity
     * @return array Processing result
     */
    private function processProject(ProjectEntity $project): array
    {
        $this->line(sprintf('🔄 Processing project ID: %d, Name: %s', $project->getId(), $project->getProjectName()));
        $this->logger->info(sprintf('Processing project ID: %d, Name: %s', $project->getId(), $project->getProjectName()));

        $projectResult = [
            'project_id' => $project->getId(),
            'project_name' => $project->getProjectName(),
            'status' => 'success',
            'processed_files' => 0,
            'errors' => 0,
            'cache_hits' => 0,
            'message' => '',
            'start_time' => date('Y-m-d H:i:s'),
        ];

        if (empty($project->getWorkDir())) {
            $this->warn(sprintf('⚠️  Project ID %d has empty work_dir, skipping...', $project->getId()));
            $this->logger->warning(sprintf('Project ID %d has empty work_dir, skipping', $project->getId()));

            $projectResult['status'] = 'skipped';
            $projectResult['message'] = 'Empty work_dir';
            $projectResult['end_time'] = date('Y-m-d H:i:s');
            return $projectResult;
        }

        // 🎯 Step 1: Update work_dir (must be before processing files, as subsequent processing depends on new work_dir)
        $updatedProject = $this->updateWorkDirectories($project);
        if ($updatedProject === null) {
            $this->error(sprintf('❌ Failed to update work_dir for project %d, skipping...', $project->getId()));

            $projectResult['status'] = 'failed';
            $projectResult['message'] = 'Failed to update work_dir';
            $projectResult['end_time'] = date('Y-m-d H:i:s');
            return $projectResult;
        }

        $processedCount = 0;
        $errorCount = 0;
        $cacheHitCount = 0;

        // Core optimization: maintain cache mapping of directory paths and parent_id
        $directoryPathCache = [];

        $md5Key = md5(StorageBucketType::Private->value);
        $prefix = $this->taskFileDomainService->getFullPrefix($updatedProject->getUserOrganizationCode());
        $oldPrefix = $prefix . $md5Key . '/BE_DELIGHTFUL/' . $updatedProject->getUserId();

        // Process files in chunks to avoid memory issues
        // 🔄 Support repeated execution: only process files that need processing
        TaskFileModel::query()
            ->where('project_id', $updatedProject->getId())
            // ->where('is_directory', false)
            ->where(function ($query) use ($oldPrefix) {
                // Only process files that need conversion: files containing old prefix or files with empty parent_id
                $query->where('file_key', 'like', $oldPrefix . '/%')
                    ->orWhereNull('parent_id');
            })
            ->chunkById(100, function ($files) use ($updatedProject, $prefix, $oldPrefix, &$processedCount, &$errorCount, &$cacheHitCount, &$directoryPathCache) {
                foreach ($files as $file) {
                    try {
                        // Process path based on type, convert old format to new format
                        $storageTypeValue = $file['storage_type'] instanceof StorageType ? $file['storage_type']->value : $file['storage_type'];
                        $isDirectory = $file['is_directory'] == 1;
                        $newFileKey = $this->handleFileKeyByType($storageTypeValue, $file['file_key'], $prefix, $oldPrefix, $isDirectory);

                        $this->logger->info(sprintf('Processing file ID: %d, File key: %s', $file->file_id, $newFileKey));

                        // If path changed, update file_key
                        if ($newFileKey !== $file['file_key']) {
                            $this->logger->info(sprintf('File key converted: %s -> %s', $file['file_key'], $newFileKey));
                            $file->file_key = $newFileKey;
                        }

                        $parentId = 0; // Initialize parentId

                        if ($file['storage_type'] == StorageType::WORKSPACE && $file['is_directory'] == 0) {
                            $parentId = $this->getFileParentIdWithCache($file, $updatedProject, $directoryPathCache, $cacheHitCount);
                            if ($parentId > 0) {
                                $file->parent_id = $parentId;
                            }
                        }

                        $file->updated_at = date('Y-m-d H:i:s');
                        $file->save();

                        $this->logger->info(sprintf('Updated file ID: %d with parent_id: %d', $file->file_id, $parentId));

                        ++$processedCount;

                        if ($processedCount % 50 === 0) {
                            $this->line(sprintf('  📈 Processed %d files... (Cache hits: %d)', $processedCount, $cacheHitCount));
                        }
                    } catch (Throwable $e) {
                        ++$errorCount;
                        $this->warn(sprintf('  ⚠️  Failed to process file ID: %d, Error: %s', $file->file_id, $e->getMessage()));
                        $this->logger->error(sprintf('Failed to process file ID: %d, Error: %s', $file->file_id, $e->getMessage()), [
                            'file_id' => $file->file_id,
                            'file_key' => $file->file_key,
                            'project_id' => $updatedProject->getId(),
                            'exception' => $e,
                        ]);
                    }
                }
            });

        $this->line(sprintf(
            '✅ Project %d completed. Processed: %d files, Errors: %d, Cache hits: %d (%.1f%%)',
            $updatedProject->getId(),
            $processedCount,
            $errorCount,
            $cacheHitCount,
            $processedCount > 0 ? ($cacheHitCount / $processedCount * 100) : 0
        ));
        $this->logger->info(sprintf(
            'Project %d completed. Processed: %d files, Errors: %d, Cache hits: %d',
            $updatedProject->getId(),
            $processedCount,
            $errorCount,
            $cacheHitCount
        ));

        // Update and return result
        $projectResult['processed_files'] = $processedCount;
        $projectResult['errors'] = $errorCount;
        $projectResult['cache_hits'] = $cacheHitCount;
        $projectResult['end_time'] = date('Y-m-d H:i:s');

        if ($errorCount > 0) {
            $projectResult['status'] = 'success_with_errors';
            $projectResult['message'] = sprintf('Completed with %d errors', $errorCount);
        } else {
            $projectResult['message'] = sprintf('Successfully processed %d files', $processedCount);
        }

        return $projectResult;
    }

    /**
     * Get file's parent_id, use cache first, call domain service if cache miss
     *
     * @param mixed $file File model
     * @param ProjectEntity $project Project entity
     * @param array $directoryPathCache Directory path cache [dirPath => parentId]
     * @param int $cacheHitCount Cache hit count (pass by reference)
     * @return int parent_id
     */
    private function getFileParentIdWithCache($file, ProjectEntity $project, array &$directoryPathCache, int &$cacheHitCount): int
    {
        $this->logger->info(sprintf('Processing file ID: %d, File Key: %s', $file->file_id, $file->file_key));

        // Extract file's directory path (remove filename)
        $directoryPath = dirname($file->file_key);

        // Normalize path, avoid "." and empty path issues
        if ($directoryPath === '.' || $directoryPath === '' || $directoryPath === '/') {
            $directoryPath = '/'; // Root directory unified as '/'
        }

        // Create cache key: project ID + directory path
        $cacheKey = $project->getId() . ':' . $directoryPath;

        // Check cache first
        if (isset($directoryPathCache[$cacheKey])) {
            $parentId = $directoryPathCache[$cacheKey];
            ++$cacheHitCount;

            $this->logger->info(sprintf(
                'Cache hit for directory "%s" -> parent_id: %d (file: %d)',
                $directoryPath,
                $parentId,
                $file->file_id
            ));

            return $parentId;
        }

        // Cache miss, call domain service to get parent_id
        $this->logger->info(sprintf(
            'Cache miss for directory "%s", calling domain service (file: %d)',
            $directoryPath,
            $file->file_id
        ));

        $parentId = $this->taskFileDomainService->findOrCreateDirectoryAndGetParentId(
            projectId: $project->getId(),
            userId: $file->user_id,
            organizationCode: $file->organization_code,
            projectOrganizationCode: $project->getUserOrganizationCode(),
            fullFileKey: $file->file_key,
            workDir: $project->getWorkDir(),
        );

        // Store result in cache
        if ($parentId > 0) {
            $directoryPathCache[$cacheKey] = $parentId;
            $this->logger->info(sprintf(
                'Cached directory "%s" -> parent_id: %d (file: %d)',
                $directoryPath,
                $parentId,
                $file->file_id
            ));
        }

        return $parentId;
    }

    /**
     * Normalize path, remove unnecessary slashes.
     *
     * @param string $path Original path
     * @param bool $isDirectory Whether it is a directory (directories need to keep trailing slash)
     * @return string Normalized path
     */
    private function normalizePath(string $path, bool $isDirectory = false): string
    {
        // Remove multiple consecutive slashes, but keep the slash at path start
        $normalized = preg_replace('#/+#', '/', $path);

        // For directories, keep trailing slash; for files, remove trailing slash (unless it's root directory)
        if (! $isDirectory && strlen($normalized) > 1) {
            $normalized = rtrim($normalized, '/');
        } elseif ($isDirectory && ! str_ends_with($normalized, '/') && $normalized !== '/') {
            // Ensure directory ends with slash
            $normalized .= '/';
        }

        return $normalized;
    }

    /**
     * Convert work_dir path format (simplified version)
     * Convert /BE_DELIGHTFUL/usi_xxx/project_xxx/workspace to /project_xxx/workspace.
     *
     * @param string $workDir Original work_dir path
     * @param string $oldPrefix Old prefix, e.g.: BE_DELIGHTFUL/usi_xxx
     * @return string Converted path
     */
    private function convertWorkDir(string $workDir, string $oldPrefix): string
    {
        // Standardize path, ensure starts with /
        $workDir = '/' . ltrim($workDir, '/');
        $searchPrefix = '/' . trim($oldPrefix, '/') . '/';

        // Check if contains old prefix
        if (strpos($workDir, $searchPrefix) !== false) {
            // Remove old prefix part
            $convertedPath = str_replace($searchPrefix, '/', $workDir);

            // 🔄 Replace path starting with topic_ to project_
            $convertedPath = preg_replace('#/topic_(\d+)#', '/project_$1', $convertedPath);

            // Check if need to add workspace
            if (! str_ends_with($convertedPath, '/workspace')) {
                $convertedPath = rtrim($convertedPath, '/') . '/workspace';
            }

            return $convertedPath;
        }

        // Does not match conversion pattern, check if need to add workspace
        if (! str_ends_with($workDir, '/workspace')) {
            $workDir = rtrim($workDir, '/') . '/workspace';
        }

        return $workDir;
    }

    /**
     * Write execution results to file.
     *
     * @param array $resultLog Execution result log
     * @param string $status Execution status
     */
    private function writeResultsToFile(array $resultLog, string $status): void
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backfill_results_{$timestamp}.json";
            $filepath = BASE_PATH . "/storage/logs/{$filename}";

            // Ensure logs directory exists
            $logDir = dirname($filepath);
            if (! is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            // Prepare summary
            $summary = [
                'status' => $status,
                'execution_time' => $resultLog['start_time'] . ' - ' . ($resultLog['end_time'] ?? 'In Progress'),
                'summary' => [
                    'total_projects' => count($resultLog['success_projects']) + count($resultLog['failed_projects']) + count($resultLog['skipped_projects']),
                    'successful_projects' => count($resultLog['success_projects']),
                    'failed_projects' => count($resultLog['failed_projects']),
                    'skipped_projects' => count($resultLog['skipped_projects']),
                    'total_processed_files' => $resultLog['total_processed_files'],
                    'total_errors' => $resultLog['total_errors'],
                ],
                'details' => $resultLog,
            ];

            // Write to file
            file_put_contents($filepath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->line(sprintf('📝 Results written to: %s', $filepath));
            $this->logger->info(sprintf('Results written to file: %s', $filepath));
        } catch (Throwable $e) {
            $this->warn(sprintf('⚠️  Failed to write results to file: %s', $e->getMessage()));
            $this->logger->warning(sprintf('Failed to write results to file: %s', $e->getMessage()));
        }
    }

    /**
     * Update work_dir for projects, files table, topics table, and tasks table.
     *
     * @param ProjectEntity $project Project entity
     * @return null|ProjectEntity Updated project entity, null if update failed
     */
    private function updateWorkDirectories(ProjectEntity $project): ?ProjectEntity
    {
        $this->line(sprintf('🔄 Updating work_dir for project %d...', $project->getId()));
        $this->logger->info(sprintf('Starting work_dir update for project %d', $project->getId()));

        try {
            $originalWorkDir = $project->getWorkDir();
            $oldWorkDirPrefix = 'BE_DELIGHTFUL/' . $project->getUserId();
            $convertedWorkDir = $this->convertWorkDir($originalWorkDir, $oldWorkDirPrefix);

            // Record conversion result
            if ($originalWorkDir !== $convertedWorkDir) {
                $this->line(sprintf('  📝 work_dir converted: %s -> %s', $originalWorkDir, $convertedWorkDir));
                $this->logger->info(sprintf('work_dir converted: %s -> %s', $originalWorkDir, $convertedWorkDir));

                // 1. Update project table work_dir
                $this->projectRepository->updateProjectByCondition(
                    ['id' => $project->getId()],
                    ['work_dir' => $convertedWorkDir, 'updated_at' => date('Y-m-d H:i:s')]
                );

                // 2. Update topics table work_dir
                $this->topicRepository->updateTopicByCondition(
                    ['project_id' => $project->getId()],
                    ['work_dir' => $convertedWorkDir, 'updated_at' => date('Y-m-d H:i:s')]
                );

                // 3. Update tasks table work_dir
                TaskModel::query()
                    ->where('project_id', $project->getId())
                    ->update([
                        'work_dir' => $convertedWorkDir,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                $this->line(sprintf('  ✅ Updated work_dir in project, topics, and tasks tables'));
                $this->logger->info(sprintf('Updated work_dir for project %d and its topics and tasks', $project->getId()));

                // Create updated project entity
                $updatedProject = clone $project;
                $updatedProject->setWorkDir($convertedWorkDir);
                return $updatedProject;
            }
            $this->line(sprintf('  ✅ work_dir already in correct format: %s', $originalWorkDir));
            $this->logger->info(sprintf('work_dir already in correct format for project %d: %s', $project->getId(), $originalWorkDir));
            return $project; // No need to update, return original project
        } catch (Throwable $e) {
            $this->warn(sprintf('  ⚠️  Failed to update work_dir for project %d: %s', $project->getId(), $e->getMessage()));
            $this->logger->error(sprintf('Failed to update work_dir for project %d: %s', $project->getId(), $e->getMessage()), [
                'project_id' => $project->getId(),
                'exception' => $e,
            ]);
            return null; // Update failed, return null
        }
    }
}

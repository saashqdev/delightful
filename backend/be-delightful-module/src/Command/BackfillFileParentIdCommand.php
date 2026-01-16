<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Command;

use App\Infrastructure\Core\ValueObject\StorageBucketType;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\ProjectRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TopicRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TaskFileModel;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TaskModel;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileDomainService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;
#[Command]

class Return fillFileParentIdCommand extends HyperfCommand 
{
 protected ?string $name = 'super-magic:backfill-file-parent-id'; 
    protected LoggerInterface $logger; 
    public function __construct( 
    protected ProjectRepositoryInterface $projectRepository, 
    protected TopicRepositoryInterface $topicRepository, 
    protected TaskFileDomainService $taskFileDomainService, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get('backfill-file-parent-id'); parent::__construct(); 
}
 
    public function configure() 
{
 parent::configure(); $this->setDescription('Return fill parent_id for existing files in magic_super_agent_task_files table'); $this->addArgument('project_id', InputArgument::OPTIONAL, 'Optional project ID to process only one project'); $this->addArgument('organization_code', InputArgument::OPTIONAL, 'Optional organization code to process projects by organization'); 
}
 
    public function handle() 
{
 $this->line('🚀 Starting backfill process for file parent_id...'); $this->logger->info('Starting backfill process for file parent_id.'); $projectId = $this->input->getArgument('project_id'); $organizationCode = $this->input->getArgument('organization_code'); // Initialize result tracking $startTime = date('Y-m-d H:i:s'); $resultLog = [ 'start_time' => $startTime, 'success_projects' => [], 'failed_projects' => [], 'skipped_projects' => [], 'total_processed_files' => 0, 'total_errors' => 0, ]; try 
{
 // Get projects based on input parameter $projects = $this->getProjectsToprocess ($projectId, $organizationCode); if (empty($projects)) 
{
 $this->error('❌ No projects found to process.'); $this->writeResultsToFile($resultLog, 'No projects found'); return; 
}
 $this->line(sprintf('📊 Found %d project(s) to process.', count($projects))); // process each project foreach ($projects as $project) 
{
 $projectResult = $this->processProject($project); // record result if ($projectResult['status'] === 'success') 
{
 $resultLog['success_projects'][] = $projectResult; $resultLog['total_processed_files'] += $projectResult['processed_files']; $resultLog['total_errors'] += $projectResult['errors']; 
}
 elseif ($projectResult['status'] === 'failed') 
{
 $resultLog['failed_projects'][] = $projectResult; 
}
 else 
{
 $resultLog['skipped_projects'][] = $projectResult; 
}
 
}
 $resultLog['end_time'] = date('Y-m-d H:i:s'); $this->writeResultsToFile($resultLog, 'complete d successfully'); $this->line('✅ Return fill process completed successfully!'); $this->logger->info('Return fill process completed successfully.'); 
}
 catch (Throwable $e) 
{
 $resultLog['end_time'] = date('Y-m-d H:i:s'); $resultLog['error'] = $e->getMessage(); $this->writeResultsToFile($resultLog, 'process failed'); $this->error(sprintf('❌ Return fill process failed: %s', $e->getMessage())); $this->logger->error(sprintf('Return fill process failed: %s', $e->getMessage()), [ 'exception' => $e, 'project_id' => $projectId, 'organization_code' => $organizationCode, ]); 
}
 
}
 /** * According toTypeprocess FilePathOldFormatPathConvert toNewFormat * directly Newprefix ReplaceOldprefix ThenAddPath. * * @param string $type Type (workspace or ) * @param string $fileKey original FilePath * @param string $prefix Newprefix DT001/588417216353927169 * @param string $oldPrefix Oldprefix DT001/588417216353927169/2c17c6393771ee3048ae34d6b380c5ec/SUPER_MAGIC/usi_xxx * @param bool $isDirectory whether as Directory * @return string ConvertFilePath */ 
    public function handleFileKeyByType($type, $fileKey, $prefix, $oldPrefix, bool $isDirectory = false): string 
{
 $storageTypeValue = $type instanceof StorageType ? $type->value : $type; // check whether including Oldprefix IfNot containReturn Path if (strpos($fileKey, $oldPrefix . '/') !== 0) 
{
 return $fileKey; 
}
 // RemoveOldprefix GetRelativePathPartial $relativePath = substr($fileKey, strlen($oldPrefix . '/')); // NormalizeRelativePathRemove $relativePath = preg_replace('#/+#', '/', $relativePath); $relativePath = trim($relativePath, '/'); if ($storageTypeValue == 'workspace') 
{
 // workspace TypeAdd /workspace // DT001/588417216353927169/2c17c6393771ee3048ae34d6b380c5ec/SUPER_MAGIC/usi_xxx/project_804590875311198209/NewFile.php // or DT001/588417216353927169/2c17c6393771ee3048ae34d6b380c5ec/SUPER_MAGIC/usi_xxx/topic_804590875311198209/NewFile.php // TargetDT001/588417216353927169/project_804590875311198209/workspace/NewFile.php // project_ or topic_ Partial $pathParts = explode('/', $relativePath); for ($i = 0; $i < count($pathParts); ++$i) 
{
 if (strpos($pathParts[$i], 'project_') === 0 || strpos($pathParts[$i], 'topic_') === 0) 
{
 $entityName = $pathParts[$i]; // Ifyes topic_need Convert to project_ Format if (strpos($entityName, 'topic_') === 0) 
{
 $entityName = str_replace('topic_', 'project_', $entityName); 
}
 // check whether already including workspace if ($i + 1 < count($pathParts) && $pathParts[$i + 1] === 'workspace') 
{
 // already Have workspace workspace after Path $remainingParts = array_slice($pathParts, $i + 2); $finalPath = empty($remainingParts) ? '' : implode('/', $remainingParts); return $this->normalizePath($prefix . '/' . $entityName . '/workspace/' . $finalPath, $isDirectory); 
}
 // need Add workspace $remainingParts = array_slice($pathParts, $i + 1); $finalPath = empty($remainingParts) ? '' : implode('/', $remainingParts); return $this->normalizePath($prefix . '/' . $entityName . '/workspace/' . $finalPath, $isDirectory); 
}
 
}
 
}
 else 
{
 // workspace TypeAdd /runtime/message // DT001/588417216353927169/2c17c6393771ee3048ae34d6b380c5ec/SUPER_MAGIC/usi_xxx/project_808853145743884288/task_xxx/.chat/file.md // or DT001/588417216353927169/2c17c6393771ee3048ae34d6b380c5ec/SUPER_MAGIC/usi_xxx/topic_808853145743884288/task_xxx/.chat/file.md // TargetDT001/588417216353927169/project_808853145743884288/runtime/message/task_xxx/.chat/file.md // project_ or topic_ Partial $pathParts = explode('/', $relativePath); for ($i = 0; $i < count($pathParts); ++$i) 
{
 if (strpos($pathParts[$i], 'project_') === 0 || strpos($pathParts[$i], 'topic_') === 0) 
{
 $entityName = $pathParts[$i]; // Ifyes topic_need Convert to project_ Format if (strpos($entityName, 'topic_') === 0) 
{
 $entityName = str_replace('topic_', 'project_', $entityName); 
}
 $remainingParts = array_slice($pathParts, $i + 1); $finalPath = empty($remainingParts) ? '' : implode('/', $remainingParts); // process EmptyPath return $this->normalizePath($prefix . '/' . $entityName . '/runtime/message/' . $finalPath, $isDirectory); 
}
 
}
 
}
 // If project_ PartialReturn Path return $fileKey; 
}
 /** * Get projects to process based on input parameter. * * @param null|string $projectId Optional project ID * @param null|string $organizationCode Optional organization code * @return ProjectEntity[] Array of project entities */ 
    private function getProjectsToprocess (?string $projectId, ?string $organizationCode): array 
{
 // check if project_id is provided and not empty if ($projectId !== null && trim($projectId) !== '' && $projectId !== '-') 
{
 // process single project $this->line(sprintf('🎯 process ing single project with ID: %s', $projectId)); $project = $this->projectRepository->findById((int) $projectId); if ($project === null) 
{
 $this->error(sprintf('❌ Project with ID %s not found.', $projectId)); return []; 
}
 return [$project]; 
}
 // Prepare conditions for project filtering $conditions = []; if ($organizationCode !== null) 
{
 $conditions['user_organization_code'] = $organizationCode; $this->line(sprintf('🏢 process ing projects for organization: %s', $organizationCode)); 
}
 else 
{
 $this->line('🌐 process ing all projects...'); 
}
 // process projects using pagination to avoid memory issues $allProjects = []; $page = 1; $pageSize = 100; do 
{
 $result = $this->projectRepository->getProjectsByConditions( conditions: $conditions, page: $page, pageSize: $pageSize, orderBy: 'id', orderDirection: 'asc' ); if (empty($result['list'])) 
{
 break; 
}
 $projects = $result['list'] ?? []; $allProjects = array_merge($allProjects, $projects); $this->line(sprintf('📄 Loaded page %d with %d projects', $page, count($projects))); ++$page; 
}
 while (count($projects) === $pageSize); return $allProjects; 
}
 /** * process a single project. * * @param ProjectEntity $project Item * @return array process Result */ 
    private function processProject(ProjectEntity $project): array 
{
 $this->line(sprintf('🔄 process ing project ID: %d, Name: %s', $project->getId(), $project->getProjectName())); $this->logger->info(sprintf('process ing project ID: %d, Name: %s', $project->getId(), $project->getProjectName())); $projectResult = [ 'project_id' => $project->getId(), 'project_name' => $project->getProjectName(), 'status' => 'success', 'processed_files' => 0, 'errors' => 0, 'cache_hits' => 0, 'message' => '', 'start_time' => date('Y-m-d H:i:s'), ]; if (empty($project->getWorkDir())) 
{
 $this->warn(sprintf('⚠️ Project ID %d has empty work_dir, skipping...', $project->getId())); $this->logger->warning(sprintf('Project ID %d has empty work_dir, skipping', $project->getId())); $projectResult['status'] = 'skipped'; $projectResult['message'] = 'Empty work_dir'; $projectResult['end_time'] = date('Y-m-d H:i:s'); return $projectResult; 
}
 // 🎯 FirstUpdate work_dirAtprocess Filebefore as process DependencyNew work_dir $updatedProject = $this->updateWorkDirectories($project); if ($updatedProject === null) 
{
 $this->error(sprintf('❌ Failed to update work_dir for project %d, skipping...', $project->getId())); $projectResult['status'] = 'failed'; $projectResult['message'] = 'Failed to update work_dir'; $projectResult['end_time'] = date('Y-m-d H:i:s'); return $projectResult; 
}
 $processedCount = 0; $errorCount = 0; $cacheHitCount = 0; // Coreoptimize DirectoryPath parent_id Map $directoryPathCache = []; $md5Key = md5(StorageBucketType::Private->value); $prefix = $this->taskFileDomainService->getFullPrefix($updatedProject->getuser OrganizationCode()); $oldPrefix = $prefix . $md5Key . '/SUPER_MAGIC/' . $updatedProject->getuser Id(); // process files in chunks to avoid memory issues // 🔄 SupportDuplicateexecute process need process File TaskFileModel::query() ->where('project_id', $updatedProject->getId()) // ->where('is_directory', false) ->where(function ($query) use ($oldPrefix) 
{
 // process need ConvertFileincluding Oldprefix File or parent_id EmptyFile $query->where('file_key', 'like', $oldPrefix . '/%') ->orWhereNull('parent_id');

}
) ->chunkById(100, function ($files) use ($updatedProject, $prefix, $oldPrefix, &$processedCount, &$errorCount, &$cacheHitCount, &$directoryPathCache) 
{
 foreach ($files as $file) 
{
 try 
{
 // According toTypeprocess PathOldFormatConvert toNewFormat $storageTypeValue = $file['storage_type'] instanceof StorageType ? $file['storage_type']->value : $file['storage_type'];
$isDirectory = $file['is_directory'] == 1; $newFileKey = $this->handleFileKeyByType($storageTypeValue, $file['file_key'], $prefix, $oldPrefix, $isDirectory); $this->logger->info(sprintf('process ing file ID: %d, File key: %s', $file->file_id, $newFileKey)); // IfPathoccurred ed Update file_key if ($newFileKey !== $file['file_key']) 
{
 $this->logger->info(sprintf('File key converted: %s -> %s', $file['file_key'], $newFileKey)); $file->file_key = $newFileKey; 
}
 $parentId = 0; // Initialize parentId if ($file['storage_type'] == StorageType::WORKSPACE && $file['is_directory'] == 0) 
{
 $parentId = $this->getFileParentIdWithCache($file, $updatedProject, $directoryPathCache, $cacheHitCount); if ($parentId > 0) 
{
 $file->parent_id = $parentId; 
}
 
}
 $file->updated_at = date('Y-m-d H:i:s'); $file->save(); $this->logger->info(sprintf('Updated file ID: %d with parent_id: %d', $file->file_id, $parentId)); ++$processedCount; if ($processedCount % 50 === 0) 
{
 $this->line(sprintf(' 📈 process ed %d files... (Cache hits: %d)', $processedCount, $cacheHitCount)); 
}
 
}
 catch (Throwable $e) 
{
 ++$errorCount; $this->warn(sprintf(' ⚠️ Failed to process file ID: %d, Error: %s', $file->file_id, $e->getMessage())); $this->logger->error(sprintf('Failed to process file ID: %d, Error: %s', $file->file_id, $e->getMessage()), [ 'file_id' => $file->file_id, 'file_key' => $file->file_key, 'project_id' => $updatedProject->getId(), 'exception' => $e, ]); 
}
 
}
 
}
); $this->line(sprintf( '✅ Project %d completed. process ed: %d files, Errors: %d, Cache hits: %d (%.1f%%)', $updatedProject->getId(), $processedCount, $errorCount, $cacheHitCount, $processedCount > 0 ? ($cacheHitCount / $processedCount * 100) : 0 )); $this->logger->info(sprintf( 'Project %d completed. process ed: %d files, Errors: %d, Cache hits: %d', $updatedProject->getId(), $processedCount, $errorCount, $cacheHitCount )); // Update and return result $projectResult['processed_files'] = $processedCount; $projectResult['errors'] = $errorCount; $projectResult['cache_hits'] = $cacheHitCount; $projectResult['end_time'] = date('Y-m-d H:i:s'); if ($errorCount > 0) 
{
 $projectResult['status'] = 'success_with_errors'; $projectResult['message'] = sprintf('complete d with %d errors', $errorCount); 
}
 else 
{
 $projectResult['message'] = sprintf('Successfully processed %d files', $processedCount); 
}
 return $projectResult; 
}
 /** * GetFile parent_idUsingin call Service * * @param mixed $file FileModel * @param ProjectEntity $project Item * @param array $directoryPathCache DirectoryPath [dirPath => parentId] * @param int $cacheHitCount in CountReferencePass * @return int parent_id */ 
    private function getFileParentIdWithCache($file, ProjectEntity $project, array &$directoryPathCache, int &$cacheHitCount): int 
{
 $this->logger->info(sprintf('process ing file ID: %d, File Key: %s', $file->file_id, $file->file_key)); // FileDirectoryPathFile $directoryPath = dirname($file->file_key); // NormalizePath . EmptyPath if ($directoryPath === '.' || $directoryPath === '' || $directoryPath === '/') 
{
 $directoryPath = '/'; // Root directory unified as '/' 
}
 // CreateKeyProject ID + DirectoryPath $cacheKey = $project->getId() . ':' . $directoryPath; // check if (isset($directoryPathCache[$cacheKey])) 
{
 $parentId = $directoryPathCache[$cacheKey]; ++$cacheHitCount; $this->logger->info(sprintf( 'Cache hit for directory %s -> parent_id: %d (file: %d)', $directoryPath, $parentId, $file->file_id )); return $parentId; 
}
 // in call ServiceGet parent_id $this->logger->info(sprintf( 'Cache miss for directory %s , calling domain service (file: %d)', $directoryPath, $file->file_id )); $parentId = $this->taskFileDomainService->findOrCreateDirectoryAndGetParentId( projectId: $project->getId(), userId: $file->user_id, organizationCode: $file->organization_code, projectOrganizationCode: $project->getuser OrganizationCode(), fullFileKey: $file->file_key, workDir: $project->getWorkDir(), ); // Result if ($parentId > 0) 
{
 $directoryPathCache[$cacheKey] = $parentId; $this->logger->info(sprintf( 'Cached directory %s -> parent_id: %d (file: %d)', $directoryPath, $parentId, $file->file_id )); 
}
 return $parentId; 
}
 /** * NormalizePathRemove. * * @param string $path original Path * @param bool $isDirectory whether as DirectoryDirectoryneed  * @return string NormalizePath */ 
    private function normalizePath(string $path, bool $isDirectory = false): string 
{
 // RemoveMultiplePath $normalized = preg_replace('#/+#', '/', $path); // ForDirectoryForFileRemoveyes Directory if (! $isDirectory && strlen($normalized) > 1) 
{
 $normalized = rtrim($normalized, '/'); 
}
 elseif ($isDirectory && ! str_ends_with($normalized, '/') && $normalized !== '/') 
{
 // EnsureDirectory $normalized .= '/'; 
}
 return $normalized; 
}
 /** * Convert work_dir PathFormat * /SUPER_MAGIC/usi_xxx/project_xxx/workspace Convert to /project_xxx/workspace. * * @param string $workDir original work_dir Path * @param string $oldPrefix Oldprefix SUPER_MAGIC/usi_xxx * @return string ConvertPath */ 
    private function convertWorkDir(string $workDir, string $oldPrefix): string 
{
 // StandardizePathEnsure / $workDir = '/' . ltrim($workDir, '/'); $searchPrefix = '/' . trim($oldPrefix, '/') . '/'; // check whether including Oldprefix if (strpos($workDir, $searchPrefix) !== false) 
{
 // RemoveOldprefix Partial $convertedPath = str_replace($searchPrefix, '/', $workDir); // 🔄 topic_ PathReplaceas project_ $convertedPath = preg_replace('#/topic_(\d+)#', '/project_$1', $convertedPath); // check whether need workspace if (! str_ends_with($convertedPath, '/workspace')) 
{
 $convertedPath = rtrim($convertedPath, '/') . '/workspace'; 
}
 return $convertedPath; 
}
 // MatchConvertSchemacheck whether need workspace if (! str_ends_with($workDir, '/workspace')) 
{
 $workDir = rtrim($workDir, '/') . '/workspace'; 
}
 return $workDir; 
}
 /** * execute ResultFile. * * @param array $resultLog execute ResultLog * @param string $status execute Status */ 
    private function writeResultsToFile(array $resultLog, string $status): void 
{
 try 
{
 $timestamp = date('Y-m-d_H-i-s'); $filename = backfill_results_
{
$timestamp
}
.json ; $filepath = BASE_PATH . /storage/logs/
{
$filename
}
 ; // Ensure logs directory exists $logDir = dirname($filepath); if (! is_dir($logDir)) 
{
 mkdir($logDir, 0755, true); 
}
 // Prepare summary $summary = [ 'status' => $status, 'execution_time' => $resultLog['start_time'] . ' - ' . ($resultLog['end_time'] ?? 'In Progress'), 'summary' => [ 'total_projects' => count($resultLog['success_projects']) + count($resultLog['failed_projects']) + count($resultLog['skipped_projects']), 'successful_projects' => count($resultLog['success_projects']), 'failed_projects' => count($resultLog['failed_projects']), 'skipped_projects' => count($resultLog['skipped_projects']), 'total_processed_files' => $resultLog['total_processed_files'], 'total_errors' => $resultLog['total_errors'], ], 'details' => $resultLog, ]; // Write to file file_put_contents($filepath, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); $this->line(sprintf('📝 Results written to: %s', $filepath)); $this->logger->info(sprintf('Results written to file: %s', $filepath)); 
}
 catch (Throwable $e) 
{
 $this->warn(sprintf('⚠️ Failed to write results to file: %s', $e->getMessage())); $this->logger->warning(sprintf('Failed to write results to file: %s', $e->getMessage())); 
}
 
}
 /** * UpdateItemFiletable topic table Tasktable work_dir. * * @param ProjectEntity $project Item * @return null|ProjectEntity UpdateItemFailedReturn null */ 
    private function updateWorkDirectories(ProjectEntity $project): ?ProjectEntity 
{
 $this->line(sprintf('🔄 Updating work_dir for project %d...', $project->getId())); $this->logger->info(sprintf('Starting work_dir update for project %d', $project->getId())); try 
{
 $originalWorkDir = $project->getWorkDir(); $oldWorkDirPrefix = 'SUPER_MAGIC/' . $project->getuser Id(); $convertedWorkDir = $this->convertWorkDir($originalWorkDir, $oldWorkDirPrefix); // record ConvertResult if ($originalWorkDir !== $convertedWorkDir) 
{
 $this->line(sprintf(' 📝 work_dir converted: %s -> %s', $originalWorkDir, $convertedWorkDir)); $this->logger->info(sprintf('work_dir converted: %s -> %s', $originalWorkDir, $convertedWorkDir)); // 1. UpdateItemtable work_dir $this->projectRepository->updateProjectByCondition( ['id' => $project->getId()], ['work_dir' => $convertedWorkDir, 'updated_at' => date('Y-m-d H:i:s')] ); // 2. Updatetopic table work_dir $this->topicRepository->updateTopicByCondition( ['project_id' => $project->getId()], ['work_dir' => $convertedWorkDir, 'updated_at' => date('Y-m-d H:i:s')] ); // 3. UpdateTasktable work_dir TaskModel::query() ->where('project_id', $project->getId()) ->update([ 'work_dir' => $convertedWorkDir, 'updated_at' => date('Y-m-d H:i:s'), ]); $this->line(sprintf(' ✅ Updated work_dir in project, topics, and tasks tables')); $this->logger->info(sprintf('Updated work_dir for project %d and its topics and tasks', $project->getId())); // CreateUpdateItem $updatedProject = clone $project; $updatedProject->setWorkDir($convertedWorkDir); return $updatedProject; 
}
 $this->line(sprintf(' ✅ work_dir already in correct format: %s', $originalWorkDir)); $this->logger->info(sprintf('work_dir already in correct format for project %d: %s', $project->getId(), $originalWorkDir)); return $project; // UpdateReturn Item 
}
 catch (Throwable $e) 
{
 $this->warn(sprintf(' ⚠️ Failed to update work_dir for project %d: %s', $project->getId(), $e->getMessage())); $this->logger->error(sprintf('Failed to update work_dir for project %d: %s', $project->getId(), $e->getMessage()), [ 'project_id' => $project->getId(), 'exception' => $e, ]); return null; // Update failedReturn null 
}
 
}
 
}
 

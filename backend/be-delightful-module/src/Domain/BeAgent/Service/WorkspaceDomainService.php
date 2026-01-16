<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use Delightful\BeDelightful\Domain\SuperAgent\Constant\AgentConstant;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\WorkspaceArchiveStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\WorkspaceCreationParams;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\WorkspaceStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\WorkspaceEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\WorkspaceVersionEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskFileRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TopicRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\WorkspaceRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\WorkspaceVersionRepositoryInterface;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\SandboxGatewayInterface;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class WorkspaceDomainService 
{
 
    public function __construct( 
    protected WorkspaceRepositoryInterface $workspaceRepository, 
    protected TopicRepositoryInterface $topicRepository, 
    protected TaskFileRepositoryInterface $taskFileRepository, 
    protected TaskRepositoryInterface $taskRepository, 
    protected TaskDomainService $taskDomainService, 
    protected WorkspaceVersionRepositoryInterface $workspaceVersionRepository, 
    protected SandboxGatewayInterface $gateway, 
    protected LoggerInterface $logger, ) 
{
 
}
 /** * Create workspace only (without topic creation). * * @param DataIsolation $dataIsolation Data isolation object * @param string $chatConversationId Chat conversation ID * @param string $workspaceName Workspace name * @return WorkspaceEntity Created workspace entity */ 
    public function createWorkspace(DataIsolation $dataIsolation, string $chatConversationId, string $workspaceName): WorkspaceEntity 
{
 // Get current user info from DataIsolation $currentuser Id = $dataIsolation->getcurrent user Id(); $organizationCode = $dataIsolation->getcurrent OrganizationCode(); // Create workspace entity $currentTime = date('Y-m-d H:i:s'); $workspaceEntity = new WorkspaceEntity(); $workspaceEntity->setuser Id($currentuser Id); $workspaceEntity->setuser OrganizationCode($organizationCode); $workspaceEntity->setChatConversationId($chatConversationId); $workspaceEntity->setName($workspaceName); $workspaceEntity->setArchiveStatus(WorkspaceArchiveStatus::NotArchived); $workspaceEntity->setWorkspaceStatus(WorkspaceStatus::Normal); $workspaceEntity->setCreatedUid($currentuser Id); $workspaceEntity->setUpdatedUid($currentuser Id); $workspaceEntity->setCreatedAt($currentTime); $workspaceEntity->setUpdatedAt($currentTime); // Save workspace using repository return $this->workspaceRepository->createWorkspace($workspaceEntity); 
}
 /** * Createworkspace . DefaultInitializetopic (DEPRECATED - use createWorkspace + TopicDomainService::createTopic) * FollowDDDServiceprocess . * @return array including workspace topic entity Array ['workspace' => WorkspaceEntity, 'topic' => TopicEntity|null] * @deprecated Use createWorkspace() and TopicDomainService::createTopic() separately */ 
    public function createWorkspaceWithTopic(DataIsolation $dataIsolation, WorkspaceCreationParams $creationParams): array 
{
 // FromDataIsolationGetcurrent user IDas creator ID $currentuser Id = $dataIsolation->getcurrent user Id();
$organizationCode = $dataIsolation->getcurrent OrganizationCode(); // Createworkspace $currentTime = date('Y-m-d H:i:s'); $workspaceEntity = new WorkspaceEntity(); $workspaceEntity->setuser Id($currentuser Id); // Usingcurrent user ID $workspaceEntity->setuser OrganizationCode($dataIsolation->getcurrent OrganizationCode()); $workspaceEntity->setChatConversationId($creationParams->getChatConversationId()); $workspaceEntity->setName($creationParams->getWorkspaceName()); $workspaceEntity->setArchiveStatus(WorkspaceArchiveStatus::NotArchived); // Default not archived $workspaceEntity->setWorkspaceStatus(WorkspaceStatus::Normal); // DefaultStatusNormal $workspaceEntity->setCreatedUid($currentuser Id); // FromDataIsolationGet $workspaceEntity->setUpdatedUid($currentuser Id); // Updater same as creator when creating $workspaceEntity->setCreatedAt($currentTime); $workspaceEntity->setUpdatedAt($currentTime); // UsingTransactionworkspace topic MeanwhileCreateSuccess $topicEntity = null; // call Saveworkspace $savedWorkspaceEntity = $this->workspaceRepository->createWorkspace($workspaceEntity); // Createtopic if ($savedWorkspaceEntity->getId() && ! empty($creationParams->getChatConversationTopicId())) 
{
 // Createtopic $topicEntity = new TopicEntity(); $topicEntity->setuser Id($currentuser Id); $topicEntity->setuser OrganizationCode($organizationCode); $topicEntity->setWorkspaceId($savedWorkspaceEntity->getId()); $topicEntity->setChatTopicId($creationParams->getChatConversationTopicId()); $topicEntity->setChatConversationId($creationParams->getChatConversationId()); $topicEntity->setSandboxId(''); // Initially empty $topicEntity->setWorkDir(''); // Initially empty $topicEntity->setcurrent TaskId(0); $topicEntity->setTopicName($creationParams->getTopicName()); $topicEntity->setcurrent TaskStatus(TaskStatus::WAITING); // DefaultStatusWaiting $topicEntity->setCreatedUid($currentuser Id); // Set creator user ID $topicEntity->setUpdatedUid($currentuser Id); // Set Updateuser ID // Using topicRepository Savetopic $savedTopicEntity = $this->topicRepository->createTopic($topicEntity); if ($savedTopicEntity->getId()) 
{
 // Set workspace current topic IDas NewCreatetopic ID $savedWorkspaceEntity->setcurrent TopicId($savedTopicEntity->getId()); // Updateworkspace $this->workspaceRepository->save($savedWorkspaceEntity); // Updateworking directory $topicEntity->setWorkDir($this->generateWorkDir($currentuser Id, $savedTopicEntity->getId())); $this->topicRepository->updateTopic($topicEntity); 
}
 $topicEntity = $savedTopicEntity; 
}
 $result = $savedWorkspaceEntity; return [ 'workspace' => $result, 'topic' => $topicEntity, ]; 
}
 /** * Updateworkspace . * FollowDDDServiceprocess . * @param DataIsolation $dataIsolation DataObject * @param int $workspaceId workspace ID * @param string $workspaceName workspace Name * @return bool whether UpdateSuccess */ 
    public function updateWorkspace(DataIsolation $dataIsolation, int $workspaceId, string $workspaceName = ''): bool 
{
 // Getworkspace $workspaceEntity = $this->workspaceRepository->getWorkspaceById($workspaceId); if (! $workspaceEntity) 
{
 throw new RuntimeException('Workspace not found'); 
}
 if ($workspaceEntity->getuser Id() !== $dataIsolation->getcurrent user Id()) 
{
 throw new RuntimeException('You are not allowed to update this workspace'); 
}
 // IfHaveworkspace NameUpdateName if (! empty($workspaceName)) 
{
 $workspaceEntity->setName($workspaceName); $workspaceEntity->setUpdatedAt(date('Y-m-d H:i:s')); $workspaceEntity->setUpdatedUid($dataIsolation->getcurrent user Id()); // Set Updateuser ID 
}
 // Using save MethodSave $this->workspaceRepository->save($workspaceEntity); return true; 
}
 /** * Getworkspace Details. */ 
    public function getWorkspaceDetail(int $workspaceId): ?WorkspaceEntity 
{
 return $this->workspaceRepository->getWorkspaceById($workspaceId); 
}
 /** * /workspace . */ 
    public function archiveWorkspace(RequestContext $requestContext, int $workspaceId, bool $isArchived): bool 
{
 $archiveStatus = $isArchived ? WorkspaceArchiveStatus::Archived : WorkspaceArchiveStatus::NotArchived; return $this->workspaceRepository->updateWorkspaceArchivedStatus($workspaceId, $archiveStatus->value); 
}
 /** * delete workspace delete . * * @param DataIsolation $dataIsolation DataObject * @param int $workspaceId workspace ID * @return bool whether delete Success * @throws RuntimeException Ifworkspace does not existThrowException */ 
    public function deleteWorkspace(DataIsolation $dataIsolation, int $workspaceId): bool 
{
 // Getworkspace $workspaceEntity = $this->workspaceRepository->getWorkspaceById($workspaceId); if (! $workspaceEntity) 
{
 // UsingExceptionBuilderThrow not found TypeError ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_NOT_FOUND, 'workspace.workspace_not_found'); 
}
 // IfIs notworkspace Cannotdelete if ($workspaceEntity->getuser Id() !== $dataIsolation->getcurrent user Id()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_ACCESS_DENIED, 'workspace.access_denied'); 
}
 // Set Deletion time $workspaceEntity->setdelete dAt(date('Y-m-d H:i:s')); $workspaceEntity->setUpdatedUid($dataIsolation->getcurrent user Id()); $workspaceEntity->setUpdatedAt(date('Y-m-d H:i:s')); // SaveUpdate $this->workspaceRepository->save($workspaceEntity); return true; 
}
 /** * Set current topic . */ 
    public function setcurrent Topic(RequestContext $requestContext, int $workspaceId, string $topicId): bool 
{
 return $this->workspaceRepository->updateWorkspacecurrent Topic($workspaceId, $topicId); 
}
 /** * According toConditionGetworkspace list . */ 
    public function getWorkspacesByConditions( array $conditions, int $page, int $pageSize, string $orderBy, string $orderDirection, DataIsolation $dataIsolation ): array 
{
 // ApplyData $conditions = $this->applyDataIsolation($conditions, $dataIsolation); // call GetData return $this->workspaceRepository->getWorkspacesByConditions( $conditions, $page, $pageSize, $orderBy, $orderDirection ); 
}
 /** * Getworkspace under topic list . * @param array $workspaceIds workspace IDArray * @param DataIsolation $dataIsolation DataObject * @param bool $needPagination whether need Paging * @param int $pageSize Per pageQuantity * @param int $page Page number * @param string $orderBy SortField * @param string $orderDirection Sort * @return array topic list */ 
    public function getWorkspaceTopics( array $workspaceIds, DataIsolation $dataIsolation, bool $needPagination = true, int $pageSize = 20, int $page = 1, string $orderBy = 'id', string $orderDirection = 'desc' ): array 
{
 $conditions = [ 'workspace_id' => $workspaceIds, 'user_id' => $dataIsolation->getcurrent user Id(), ]; return $this->topicRepository->getTopicsByConditions( $conditions, $needPagination, $pageSize, $page, $orderBy, $orderDirection ); 
}
 /** * GetTasklist . * * @param int $taskId TaskID * @param DataIsolation $dataIsolation DataObject * @param int $page Page number * @param int $pageSize Per pageQuantity * @return array list Total */ 
    public function getTaskAttachments(int $taskId, DataIsolation $dataIsolation, int $page = 1, int $pageSize = 20): array 
{
 // call TaskFileRepositoryGetFilelist return $this->taskFileRepository->getByTaskId($taskId, $page, $pageSize); // directly Return Objectlist application layer process URLGet 
}
 /** * Createtopic . * * @param DataIsolation $dataIsolation DataObject * @param int $workspaceId workspace ID * @param string $chatTopicId Session topic IDAttopic_idFieldin * @param string $topicName topic Name * @return TopicEntity Createtopic * @throws Exception IfCreation failed */ 
    public function createTopic(DataIsolation $dataIsolation, int $workspaceId, string $chatTopicId, string $topicName): TopicEntity 
{
 // Getcurrent user ID $userId = $dataIsolation->getcurrent user Id(); $organizationCode = $dataIsolation->getcurrent OrganizationCode(); // Getworkspace Detailscheck workspace whether Exist $workspaceEntity = $this->workspaceRepository->getWorkspaceById($workspaceId); if (! $workspaceEntity) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'workspace.not_found'); 
}
 // check workspace whether Archived if ($workspaceEntity->getArchiveStatus() === WorkspaceArchiveStatus::Archived) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'workspace.archived'); 
}
 // GetSessionID $chatConversationId = $workspaceEntity->getChatConversationId(); if (empty($chatConversationId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::SystemError, 'workspace.conversation_id_not_found'); 
}
 // Iftopic IDEmptyThrowException if (empty($chatTopicId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic.id_required'); 
}
 // Createtopic $topicEntity = new TopicEntity(); $topicEntity->setuser Id($userId); $topicEntity->setuser OrganizationCode($organizationCode); $topicEntity->setWorkspaceId($workspaceId); $topicEntity->setChatTopicId($chatTopicId); $topicEntity->setChatConversationId($chatConversationId); $topicEntity->setTopicName($topicName); $topicEntity->setSandboxId(''); // Initially empty $topicEntity->setWorkDir(''); // Initially empty $topicEntity->setcurrent TaskId(0); $topicEntity->setcurrent TaskStatus(TaskStatus::WAITING); // DefaultStatusWaiting $topicEntity->setCreatedUid($userId); // Set creator user ID $topicEntity->setUpdatedUid($userId); // Set Updateuser ID // Savetopic $topicEntity = $this->topicRepository->createTopic($topicEntity); // Updateworkspace if ($topicEntity->getId()) 
{
 $topicEntity->setWorkDir($this->generateWorkDir($userId, $topicEntity->getId())); $this->topicRepository->updateTopic($topicEntity); 
}
 return $topicEntity; 
}
 /** * ThroughIDGettopic . * * @param int $id topic ID(primary key ) * @return null|TopicEntity topic */ 
    public function getTopicById(int $id): ?TopicEntity 
{
 return $this->topicRepository->getTopicById($id); 
}
 /** * BatchGettopic . * @return TopicEntity[] */ 
    public function getTopicsByIds(array $topicIds): array 
{
 if (empty($topicIds)) 
{
 return []; 
}
 return $this->topicRepository->getTopicsByIds($topicIds); 
}
 /** * Update topic project association. * * @param int $topicId Topic ID * @param int $projectId Project ID * @return bool whether the update was successful * @throws Exception If the update fails */ 
    public function updateTopicProject(int $topicId, int $projectId): bool 
{
 // Get topic entity by ID $topicEntity = $this->topicRepository->getTopicById($topicId); if (! $topicEntity) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'topic.not_found'); 
}
 // Update project association $topicEntity->setProjectId($projectId); // Save update return $this->topicRepository->updateTopic($topicEntity); 
}
 
    public function getTopicBySandboxId(string $sandboxId): ?TopicEntity 
{
 $topics = $this->topicRepository->getTopicsByConditions(['sandbox_id' => $sandboxId], true, 1, 1); if (! isset($topics['list']) || empty($topics['list'])) 
{
 return null; 
}
 return $topics['list'][0]; 
}
 /** * Saveworkspace * directly Saveworkspace not needed Duplicatequery . * @param WorkspaceEntity $workspaceEntity workspace * @return WorkspaceEntity Saveworkspace */ 
    public function saveWorkspaceEntity(WorkspaceEntity $workspaceEntity): WorkspaceEntity 
{
 return $this->workspaceRepository->save($workspaceEntity); 
}
 /** * Getworkspace topic list . * * @param array|int $workspaceIds workspace IDor IDArray * @param string $userId user ID * @return array topic list workspace IDas Key */ 
    public function getWorkspaceTopicsByWorkspaceIds(array|int $workspaceIds, string $userId): array 
{
 if (! is_array($workspaceIds)) 
{
 $workspaceIds = [$workspaceIds]; 
}
 // IfDon't haveworkspace IDdirectly Return EmptyArray if (empty($workspaceIds)) 
{
 return []; 
}
 // query Condition $conditions = [ 'workspace_id' => $workspaceIds, 'user_id' => $userId, ]; // GetAllComply withConditiontopic $result = $this->topicRepository->getTopicsByConditions( $conditions, false, // PagingGetAll 100, 1, 'id', 'asc' ); // Newworkspace ID Group $topics = []; foreach ($result['list'] as $topic) 
{
 $workspaceId = $topic->getWorkspaceId(); if (! isset($topics[$workspaceId])) 
{
 $topics[$workspaceId] = []; 
}
 $topics[$workspaceId][] = $topic; 
}
 return $topics; 
}
 
    public function getuser Topics(string $userId): array 
{
 // whether need Organization code $topics = $this->topicRepository->getTopicsByConditions( ['user_id' => $userId], false, // PagingGetAll 100, 1, 'id', 'asc' ); if (empty($topics['list'])) 
{
 return []; 
}
 return $topics['list']; 
}
 
    public function getTopiclist (int $page, int $pageSize): array 
{
 // whether need Organization code // PagingGetAll $topics = $this->topicRepository->getTopicsByConditions([], true, $pageSize, $page); if (empty($topics['list'])) 
{
 return []; 
}
 return $topics['list']; 
}
 /** * According toTaskStatusGetworkspace topic list . * * @param array|int $workspaceIds workspace IDor IDArray * @param string $userId user ID * @param null|TaskStatus $taskStatus TaskStatusIfas nullReturn AllStatus * @return array topic list workspace IDas Key */ 
    public function getWorkspaceTopicsByTaskStatus(array|int $workspaceIds, string $userId, ?TaskStatus $taskStatus = null): array 
{
 // GetAlltopic $allTopics = $this->getWorkspaceTopicsByWorkspaceIds($workspaceIds, $userId); // Ifnot needed FilterTaskStatusdirectly Return Alltopic if ($taskStatus === null) 
{
 return $allTopics; 
}
 // According toTaskStatusFiltertopic $filteredTopics = []; foreach ($allTopics as $workspaceId => $topics) 
{
 $filteredTopiclist = []; foreach ($topics as $topic) 
{
 // Iftopic current TaskStatusspecified StatusMatchor topic Don't haveTaskStatusand specified yes WaitingStatus if (($topic->getcurrent TaskStatus() === $taskStatus) || ($topic->getcurrent TaskStatus() === null && $taskStatus === TaskStatus::WAITING)) 
{
 $filteredTopiclist [] = $topic; 
}
 
}
 if (! empty($filteredTopiclist )) 
{
 $filteredTopics[$workspaceId] = $filteredTopiclist ; 
}
 
}
 return $filteredTopics; 
}
 /** * delete topic delete . * * @param DataIsolation $dataIsolation DataObject * @param int $id topic ID(primary key ) * @return bool whether delete Success * @throws Exception IfDeletion failedor TaskStatusas Running */ 
    public function deleteTopic(DataIsolation $dataIsolation, int $id): bool 
{
 // Getcurrent user ID $userId = $dataIsolation->getcurrent user Id(); // Throughprimary key IDGettopic $topicEntity = $this->topicRepository->getTopicById($id); if (! $topicEntity) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'topic.not_found'); 
}
 // check user permission check topic whether belongs to current user  if ($topicEntity->getuser Id() !== $userId) 
{
 ExceptionBuilder::throw(GenericErrorCode::AccessDenied, 'topic.access_denied'); 
}
 // check TaskStatusIfyes RunningAllowdelete if ($topicEntity->getcurrent TaskStatus() === TaskStatus::RUNNING) 
{
 // agent SendStop $taskEntity = $this->taskRepository->getTaskById($topicEntity->getcurrent TaskId()); if (! empty($taskEntity)) 
{
 $this->taskDomainService->handleInterruptInstruction($dataIsolation, $taskEntity); 
}
 
}
 // Getworkspace Detailscheck workspace whether Exist $workspaceEntity = $this->workspaceRepository->getWorkspaceById($topicEntity->getWorkspaceId()); if (! $workspaceEntity) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'workspace.not_found'); 
}
 // check workspace whether Archived if ($workspaceEntity->getArchiveStatus() === WorkspaceArchiveStatus::Archived) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'workspace.archived'); 
}
 // delete topic under AllTaskcall Batchdelete Method $this->taskRepository->deleteTasksByTopicId($id); // Set Deletion time $topicEntity->setdelete dAt(date('Y-m-d H:i:s')); // Set Updateuser ID $topicEntity->setUpdatedUid($userId); // SaveUpdate return $this->topicRepository->updateTopic($topicEntity); 
}
 /** * GetTaskDetails. * * @param int $taskId TaskID * @return null|TaskEntity Task */ 
    public function getTaskById(int $taskId): ?TaskEntity 
{
 return $this->taskRepository->getTaskById($taskId); 
}
 /** * Gettopic AssociationTasklist . * * @param int $topicId topic ID * @param int $page Page number * @param int $pageSize Per pageQuantity * @param null|DataIsolation $dataIsolation DataObject * @return array
{
list: TaskEntity[], total: int
}
 Tasklist Total */ 
    public function getTasksByTopicId(int $topicId, int $page = 1, int $pageSize = 10, ?DataIsolation $dataIsolation = null): array 
{
 return $this->taskRepository->getTasksByTopicId($topicId, $page, $pageSize); 
}
 /** * Throughtopic IDCollectionGetworkspace info . * * @param array $topicIds topic IDCollection * @return array topic IDas Keyworkspace info as ValueAssociationArray */ 
    public function getWorkspaceinfo ByTopicIds(array $topicIds): array 
{
 if (empty($topicIds)) 
{
 return []; 
}
 return $this->topicRepository->getWorkspaceinfo ByTopicIds($topicIds); 
}
 
    public function updateTopicSandboxConfig(DataIsolation $dataIsolation, int $topicId, array $sandboxConfig): bool 
{
 $topicEntity = $this->topicRepository->getTopicById($topicId); if (! $topicEntity) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'topic.not_found'); 
}
 $topicEntity->setSandboxConfig(json_encode($sandboxConfig)); return $this->topicRepository->updateTopic($topicEntity); 
}
 /** * GetAllworkspace OrganizationCodelist . * * @return array OrganizationCodelist */ 
    public function getUniqueOrganizationCodes(): array 
{
 return $this->workspaceRepository->getUniqueOrganizationCodes(); 
}
 /** * Create a new workspace version record. */ 
    public function createWorkspaceVersion(WorkspaceVersionEntity $versionEntity): void 
{
 $this->workspaceVersionRepository->create($versionEntity); 
}
 /** * Get workspace version by commit hash, topic ID and folder. * * @param string $commitHash The commit hash * @param int $projectId The project ID * @param string $folder The folder path * @return null|WorkspaceVersionEntity The workspace version entity or null if not found */ 
    public function getWorkspaceVersionByCommitAndProjectId(string $commitHash, int $projectId, string $folder = ''): ?WorkspaceVersionEntity 
{
 // Get all versions for the topic return $this->workspaceVersionRepository->findByCommitHashAndProjectId($commitHash, $projectId, $folder); 
}
 /** * Get workspace version by commit hash, topic ID and folder. * * @param int $projectId The project ID * @param string $folder The folder path * @return null|WorkspaceVersionEntity The workspace version entity or null if not found */ 
    public function getWorkspaceVersionByProjectId(int $projectId, string $folder = ''): ?WorkspaceVersionEntity 
{
 // Get all versions for the topic return $this->workspaceVersionRepository->findByProjectId($projectId, $folder); 
}
 
    public function getLatestVersionByProjectId(int $projectId): ?WorkspaceVersionEntity 
{
 return $this->workspaceVersionRepository->getLatestVersionByProjectId($projectId); 
}
 /** * According tocommit_hash project_id Gettag. */ 
    public function getTagByCommitHashAndProjectId(string $commitHash, int $projectId): int 
{
 return $this->workspaceVersionRepository->getTagByCommitHashAndProjectId($commitHash, $projectId); 
}
 /** * BatchGetworkspace NameMap. * * @param array $workspaceIds workspace IDArray * @return array ['workspace_id' => 'workspace_name'] KeyValuePair */ 
    public function getWorkspaceNamesBatch(array $workspaceIds): array 
{
 if (empty($workspaceIds)) 
{
 return []; 
}
 return $this->workspaceRepository->getWorkspaceNamesBatch($workspaceIds); 
}
 /** * Throughcommit hash topic id GetVersionAccording todir Filelist Filterresult. */ 
    public function filterResultByGitVersion(array $result, int $projectId, string $organizationCode, string $workDir = ''): array 
{
 $dir = '.workspace'; $workspaceVersion = $this->getWorkspaceVersionByProjectId($projectId, $dir); if (empty($workspaceVersion)) 
{
 return $result; 
}
 if (empty($workspaceVersion->getDir())) 
{
 return $result; 
}
 # resultupdatedAt IfupdatedAt Less thanworkspaceVersion updated_at Attemporary Array $fileResult = []; foreach ($result['list'] as $item) 
{
 if ($item['updated_at'] >= $workspaceVersion->getUpdatedAt()) 
{
 $fileResult[] = $item; 
}
 
}
 $dir = json_decode($workspaceVersion->getDir(), true); # dir yes Array$dir, Determinewhether yes FileIfDon't haveFileNoteyes DirectoryFilterDirectory # dir =[ generated_images , generated_images\/cute-cartoon-cat.jpg , generated_images\/handdrawn-cute-cat.jpg , generated_images\/abstract-modern-generic.jpg , generated_images\/minimalist-cat-icon.jpg , generated_images\/realistic-elegant-cat.jpg , generated_images\/oilpainting-elegant-cat.jpg , generated_images\/anime-cute-cat.jpg , generated_images\/cute-cartoon-dog.jpg , generated_images\/universal-minimal-logo-3.jpg , generated_images\/universal-minimal-logo.jpg , generated_images\/universal-minimal-logo-2.jpg , generated_images\/realistic-cat-photo.jpg , generated_images\/minimal-tech-logo.jpg , logs , logs\/agentlang.log ] $dir = array_filter($dir, function ($item) 
{
 if (strpos($item, '.') === false) 
{
 return false; 
}
 return true; 
}
); $gitVersionResult = []; foreach ($result['list'] as $item) 
{
 foreach ($dir as $dirItem) 
{
 $fileKey = WorkDirectoryUtil::getRelativeFilePath($item['file_key'], $workDir); // PathAllPathStandardizeas SystemDefault $fileKey = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fileKey); $dirItem = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirItem); $dirItem = '/' . $dirItem; // as FullMatch if ($dirItem == $fileKey) 
{
 $gitVersionResult[] = $item; 
}
 
}
 
}
 $newResult = array_merge($fileResult, $gitVersionResult); # PairtempResultRowdeduplication $result['list'] = array_unique($newResult, SORT_REGULAR); $result['total'] = count($result['list']); return $result; 
}
 
    public function diffFilelist AndVersionFile(array $result, int $projectId, string $taskId, string $sandboxId, string $organizationCode = ''): bool 
{
 $dir = '.workspace'; $workspaceVersion = $this->getWorkspaceVersionByProjectId($projectId, $dir); if (empty($workspaceVersion)) 
{
 return false; 
}
 if (empty($workspaceVersion->getDir())) 
{
 return false; 
}
 $dir = json_decode($workspaceVersion->getDir(), true); # dir yes Array$dir, Determinewhether yes FileIfDon't haveFileNoteyes DirectoryFilterDirectory # dir =[ generated_images , generated_images\/cute-cartoon-cat.jpg , generated_images\/handdrawn-cute-cat.jpg , generated_images\/abstract-modern-generic.jpg , generated_images\/minimalist-cat-icon.jpg , generated_images\/realistic-elegant-cat.jpg , generated_images\/oilpainting-elegant-cat.jpg , generated_images\/anime-cute-cat.jpg , generated_images\/cute-cartoon-dog.jpg , generated_images\/universal-minimal-logo-3.jpg , generated_images\/universal-minimal-logo.jpg , generated_images\/universal-minimal-logo-2.jpg , generated_images\/realistic-cat-photo.jpg , generated_images\/minimal-tech-logo.jpg , logs , logs\/agentlang.log ] $dir = array_filter($dir, function ($item) 
{
 if (strpos($item, '.') === false) 
{
 return false; 
}
 return true; 
}
); # $result If$result file_key At$dir in  dirin Saveyes file_key in Partialneed UsingStringMatchIfExistAttemporary Array $gitVersionNotExistResult = []; $fileKeys = []; foreach ($result['list'] as $item) 
{
 # Find the project_id pattern in the file_key and extract everything after it $projectPattern = 'project_' . $projectId; $pos = strpos($item['file_key'], $projectPattern); if ($pos !== false) 
{
 # Get the position after the project_id and the following slash $startPos = $pos + strlen($projectPattern) + 1; // +1 for the slash $fileKeys[] = substr($item['file_key'], $startPos); 
}
 else 
{
 # Fallback: if project_id pattern not found, keep original logic $fileKeys[] = substr($item['file_key'], strlen((string) $projectId) + 1); 
}
 
}
 foreach ($dir as $dirItem) 
{
 if (! in_array($dirItem, $fileKeys)) 
{
 $gitVersionNotExistResult[] = $dirItem; 
}
 
}
 if (empty($gitVersionNotExistResult)) 
{
 return false; 
}
 # PairgitVersionNotExistResult Rowdeduplication $gitVersionNotExistResult = array_unique($gitVersionNotExistResult); # NewSort $gitVersionNotExistResult = array_values($gitVersionNotExistResult); # gitVersionNotExistResult EmptyNoteHaveFileUpdateyes Don't havetrigger suer-magicFile uploadneed call suer-magic api Rowonce File upload if (! empty($gitVersionNotExistResult)) 
{
 try 
{
 # Viewsandbox whether $sandboxStatus = $this->gateway->getSandboxStatus($sandboxId); if ($sandboxStatus->isRunning()) 
{
 $gatewayResult = $this->gateway->uploadFile($sandboxId, $gitVersionNotExistResult, (string) $projectId, $organizationCode, $taskId); if ($gatewayResult->isSuccess()) 
{
 return true; 
}
 
}
 else 
{
 return false; 
}
 
}
 catch (Throwable $e) 
{
 $this->logger->error('[Sandbox][Domain] uploadFile failed', ['error' => $e->getMessage()]); 
}
 
}
 return false; 
}
 /** * ApplyDataquery Condition. */ 
    private function applyDataIsolation(array $conditions, DataIsolation $dataIsolation): array 
{
 // user id OrganizationCode $conditions['user_id'] = $dataIsolation->getcurrent user Id(); $conditions['user_organization_code'] = $dataIsolation->getcurrent OrganizationCode(); return $conditions; 
}
 /** * Generate working directory . */ 
    private function generateWorkDir(string $userId, int $topicId): string 
{
 return sprintf('/%s/%s/topic_%d', AgentConstant::SUPER_MAGIC_CODE, $userId, $topicId); 
}
 
}
 

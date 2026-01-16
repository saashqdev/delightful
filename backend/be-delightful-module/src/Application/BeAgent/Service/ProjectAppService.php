<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\LongTermMemory\Service\LongTermMemoryDomainService;
use App\Domain\Provider\Service\ModelFilter\PackageFilterInterface;
use App\Infrastructure\Core\Exception\EventException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use Dtyq\AsyncEvent\AsyncEventUtil;
use Delightful\BeDelightful\Application\Chat\Service\ChatAppService;
use Delightful\BeDelightful\Application\SuperAgent\Event\Publish\ProjectForkPublisher;
use Delightful\BeDelightful\Application\SuperAgent\Event\Publish\StopRunningTaskPublisher;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Constant\AgentConstant;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectForkEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskFileEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\delete DataType;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MemberRole;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ForkProjectStartEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ProjectCreatedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\Projectdelete dEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ProjectForkEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\ProjectUpdatedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\StopRunningTaskEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectMemberDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\WorkspaceDomainService;
use Delightful\BeDelightful\ErrorCode\ShareErrorCode;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\AccessTokenUtil;
use Delightful\BeDelightful\Infrastructure\Utils\FileMetadataUtil;
use Delightful\BeDelightful\Infrastructure\Utils\FileTreeUtil;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\ForkProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetProjectAttachmentsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetProjectAttachmentsV2RequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetProjectlist RequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\MoveProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpdateProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\ForkProjectResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\ForkStatusResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\ProjectItemDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\Projectlist ResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\TaskFileItemDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\TopicItemDTO;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use function Hyperf\Translation\trans;
/** * ItemApplyService */

class ProjectAppService extends AbstractAppService 
{
 
    protected LoggerInterface $logger; 
    public function __construct( 
    private readonly WorkspaceDomainService $workspaceDomainService, 
    private readonly ProjectDomainService $projectDomainService, 
    private readonly ProjectMemberDomainService $projectMemberDomainService, 
    private readonly TopicDomainService $topicDomainService, 
    private readonly TaskDomainService $taskDomainService, 
    private readonly TaskFileDomainService $taskFileDomainService, 
    private readonly ChatAppService $chatAppService, 
    private readonly ResourceShareDomainService $resourceShareDomainService, 
    private readonly LongTermMemoryDomainService $longTermMemoryDomainService, 
    private readonly Producer $producer, 
    private readonly EventDispatcherInterface $eventDispatcher, 
    private readonly PackageFilterInterface $packageFilterService, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(self::class); 
}
 /** * CreateItem. */ 
    public function createProject(RequestContext $requestContext, CreateProjectRequestDTO $requestDTO): array 
{
 $this->logger->info('StartInitializeuser Item'); // Get user authorization information $userAuthorization = $requestContext->getuser Authorization(); // Create data isolation object $dataIsolation = $this->createDataIsolation($userAuthorization); // check topic whether Exist $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail($requestDTO->getWorkspaceId()); if (empty($workspaceEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_NOT_FOUND, 'workspace.workspace_not_found'); 
}
 // Ifspecified ed working directory need Fromworking directory Itemid $projectId = ''; $fullPrefix = $this->taskFileDomainService->getFullPrefix($dataIsolation->getcurrent OrganizationCode()); if (! empty($requestDTO->getWorkDir()) && WorkDirectoryUtil::isValidWorkDirectory($fullPrefix, $requestDTO->getWorkDir())) 
{
 $projectId = WorkDirectoryUtil::extractProjectIdFromAbsolutePath($requestDTO->getWorkDir()); 
}
 Db::beginTransaction(); try 
{
 // CreateDefault project $this->logger->info('CreateDefault project'); $projectEntity = $this->projectDomainService->createProject( $workspaceEntity->getId(), $requestDTO->getProjectName(), $dataIsolation->getcurrent user Id(), $dataIsolation->getcurrent OrganizationCode(), $projectId, '', $requestDTO->getProjectMode() ?: null ); $this->logger->info(sprintf('CreateDefault project, projectId=%s', $projectEntity->getId())); // GetItemDirectory $workDir = WorkDirectoryUtil::getWorkDir($dataIsolation->getcurrent user Id(), $projectEntity->getId()); // Initialize Magic Chat Conversation [$chatConversationId, $chatConversationTopicId] = $this->chatAppService->initMagicChatConversation($dataIsolation); // CreateSession // Step 4: Create default topic $this->logger->info('StartCreateDefault topic'); $topicEntity = $this->topicDomainService->createTopic( $dataIsolation, $workspaceEntity->getId(), $projectEntity->getId(), $chatConversationId, $chatConversationTopicId, '', $workDir ); $this->logger->info(sprintf('CreateDefault topicSuccess, topicId=%s', $topicEntity->getId())); // Set workspace info $workspaceEntity->setcurrent TopicId($topicEntity->getId()); $workspaceEntity->setcurrent ProjectId($projectEntity->getId()); $this->workspaceDomainService->saveWorkspaceEntity($workspaceEntity); $this->logger->info(sprintf('workspace %sSet current topic %s', $workspaceEntity->getId(), $topicEntity->getId())); // Set Iteminfo $projectEntity->setcurrent TopicId($topicEntity->getId()); $projectEntity->setWorkspaceId($workspaceEntity->getId()); $projectEntity->setWorkDir($workDir); $this->projectDomainService->saveProjectEntity($projectEntity); $this->logger->info(sprintf('Item%sSet current topic %s', $projectEntity->getId(), $topicEntity->getId())); // IfEmptyand yes UnboundStatusSave InitializeDirectory if ($requestDTO->getFiles()) 
{
 $this->taskFileDomainService->bindProjectFiles( $dataIsolation, $projectEntity, $requestDTO->getFiles(), $projectEntity->getWorkDir() ); 
}
 else 
{
 // IfDon't haveInitializeItemDirectoryFile $this->taskFileDomainService->findOrCreateProjectRootDirectory( projectId: $projectEntity->getId(), workDir: $projectEntity->getWorkDir(), userId: $dataIsolation->getcurrent user Id(), organizationCode: $dataIsolation->getcurrent OrganizationCode(), projectOrganizationCode: $projectEntity->getuser OrganizationCode(), ); 
}
 // InitializeItemMemberSet $this->projectMemberDomainService->initializeProjectMemberAndSet ( $dataIsolation->getcurrent user Id(), $projectEntity->getId(), $workspaceEntity->getId(), $dataIsolation->getcurrent OrganizationCode() ); Db::commit(); // PublishedItemCreateEvent $userAuthorization = $requestContext->getuser Authorization(); $projectCreatedEvent = new ProjectCreatedEvent($projectEntity, $userAuthorization); $this->eventDispatcher->dispatch($projectCreatedEvent); return ['project' => ProjectItemDTO::fromEntity($projectEntity)->toArray(), 'topic' => TopicItemDTO::fromEntity($topicEntity)->toArray()]; 
}
 catch (Throwable $e) 
{
 Db::rollReturn (); $this->logger->error('Create Project Failed, err: ' . $e->getMessage(), ['request' => $requestDTO->toArray()]); ExceptionBuilder::throw(SuperAgentErrorCode::CREATE_PROJECT_FAILED, 'project.create_project_failed'); 
}
 
}
 /** * UpdateItem. */ 
    public function updateProject(RequestContext $requestContext, UpdateProjectRequestDTO $requestDTO): array 
{
 // Get user authorization information $userAuthorization = $requestContext->getuser Authorization(); // Create data isolation object $dataIsolation = $this->createDataIsolation($userAuthorization); // GetIteminfo $projectEntity = $this->projectDomainService->getProject((int) $requestDTO->getId(), $dataIsolation->getcurrent user Id()); if (! is_null($requestDTO->getProjectName())) 
{
 $projectEntity->setProjectName($requestDTO->getProjectName()); 
}
 if (! is_null($requestDTO->getProjectDescription())) 
{
 $projectEntity->setProjectDescription($requestDTO->getProjectDescription()); 
}
 if (! is_null($requestDTO->getWorkspaceId())) 
{
 // check topic whether Exist $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail($requestDTO->getWorkspaceId()); if (empty($workspaceEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_NOT_FOUND, 'workspace.workspace_not_found'); 
}
 $projectEntity->setWorkspaceId($requestDTO->getWorkspaceId()); 
}
 if (! is_null($requestDTO->getIsCollaborationEnabled())) 
{
 $projectEntity->setIsCollaborationEnabled($requestDTO->getIsCollaborationEnabled()); 
}
 if (! is_null($requestDTO->getDefaultJoinpermission ())) 
{
 $projectEntity->setDefaultJoinpermission (MemberRole::validatepermission Level($requestDTO->getDefaultJoinpermission ())); 
}
 $this->projectDomainService->saveProjectEntity($projectEntity); // PublishedItemUpdatedEvent $userAuthorization = $requestContext->getuser Authorization(); $projectUpdatedEvent = new ProjectUpdatedEvent($projectEntity, $userAuthorization); $this->eventDispatcher->dispatch($projectUpdatedEvent); return ProjectItemDTO::fromEntity($projectEntity)->toArray(); 
}
 /** * delete Item. */ #[Transactional] 
    public function deleteProject(RequestContext $requestContext, int $projectId): bool 
{
 // Get user authorization information $userAuthorization = $requestContext->getuser Authorization(); // Create data isolation object $dataIsolation = $this->createDataIsolation($userAuthorization); // GetItemfor EventPublished $projectEntity = $this->projectDomainService->getProject($projectId, $dataIsolation->getcurrent user Id()); $result = Db::transaction(function () use ($projectId, $dataIsolation) 
{
 // delete Item $result = $this->projectDomainService->deleteProject($projectId, $dataIsolation->getcurrent user Id());
// delete Itemcollaboration Relationship $this->projectMemberDomainService->deleteByProjectId($projectId); return $result; 
}
); if ($result) 
{
 // delete Itemrelated long-term memory $this->longTermMemoryDomainService->deleteMemoriesByProjectIds( $dataIsolation->getcurrent OrganizationCode(), AgentConstant::SUPER_MAGIC_CODE, // app_id Fixedas super-magic $dataIsolation->getcurrent user Id(), [(string) $projectId] ); // PublishedItemdelete dEvent $projectdelete dEvent = new Projectdelete dEvent($projectEntity, $userAuthorization); $this->eventDispatcher->dispatch($projectdelete dEvent); $this->topicDomainService->deleteTopicsByProjectId($dataIsolation, $projectId); $event = new StopRunningTaskEvent( delete DataType::PROJECT, $projectId, $dataIsolation->getcurrent user Id(), $dataIsolation->getcurrent OrganizationCode(), 'Itemdelete ' ); $publisher = new StopRunningTaskPublisher($event); $this->producer->produce($publisher); $this->logger->info(sprintf( 'delivery StopTaskMessageProject ID: %d, EventID: %s', $projectId, $event->getEventId() )); 
}
 return $result; 
}
 /** * GetItemDetails. */ 
    public function getProjectinfo (RequestContext $requestContext, int $projectId): ProjectEntity 
{
 $userAuthorization = $requestContext->getuser Authorization(); $project = $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); // Ifcurrent Organizationplan ProhibitItemcollaboration if (! $this->packageFilterService->isPaidSubscription($project->getuser OrganizationCode())) 
{
 $project->setIsCollaborationEnabled(false); 
}
 return $project; 
}
 /** * GetItemDetails. */ 
    public function getProject(RequestContext $requestContext, int $projectId): ProjectEntity 
{
 $userAuthorization = $requestContext->getuser Authorization(); return $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); 
}
 /** * GetItemDetails. */ 
    public function getProjectNotuser Id(int $projectId): ?ProjectEntity 
{
 return $this->projectDomainService->getProjectNotuser Id($projectId); 
}
 
    public function getProjectForkCount(int $projectId): int 
{
 return $this->projectDomainService->getProjectForkCount($projectId); 
}
 /** * GetItemlist Paging. */ 
    public function getProjectlist (RequestContext $requestContext, GetProjectlist RequestDTO $requestDTO): array 
{
 // Get user authorization information $userAuthorization = $requestContext->getuser Authorization(); // Create data isolation object $dataIsolation = $this->createDataIsolation($userAuthorization); $conditions = []; $conditions['user_id'] = $dataIsolation->getcurrent user Id(); $conditions['user_organization_code'] = $dataIsolation->getcurrent OrganizationCode(); if ($requestDTO->getWorkspaceId()) 
{
 $conditions['workspace_id'] = $requestDTO->getWorkspaceId(); 
}
 // Add project name fuzzy search condition if (! empty($requestDTO->getProjectName())) 
{
 $conditions['project_name_like'] = $requestDTO->getProjectName(); 
}
 $result = $this->projectDomainService->getProjectsByConditions( $conditions, $requestDTO->getPage(), $requestDTO->getPageSize(), 'updated_at', 'desc' ); // AllProject IDworkspace ID $projectIds = array_unique(array_map(fn ($project) => $project->getId(), $result['list'] ?? [])); $workspaceIds = array_unique(array_map(fn ($project) => $project->getWorkspaceId(), $result['list'] ?? [])); // BatchGetItemStatus $projectStatusMap = $this->topicDomainService->calculateProjectStatusBatch($projectIds, $dataIsolation->getcurrent user Id()); // BatchGetworkspace Name $workspaceNameMap = $this->workspaceDomainService->getWorkspaceNamesBatch($workspaceIds); // BatchGetItemMemberQuantityDeterminewhether Existcollaboration Member $projectMemberCounts = $this->projectMemberDomainService->getProjectMembersCounts($projectIds); $projectIdsWithMember = array_keys(array_filter($projectMemberCounts, fn ($count) => $count > 0)); // CreateResponseDTOItemStatusMapworkspace NameMap $listResponseDTO = Projectlist ResponseDTO::fromResult($result, $workspaceNameMap, $projectIdsWithMember, $projectStatusMap); return $listResponseDTO->toArray(); 
}
 /** * GetItemunder topic list . */ 
    public function getProjectTopics(RequestContext $requestContext, int $projectId, int $page = 1, int $pageSize = 10): array 
{
 // Get user authorization information $userAuthorization = $requestContext->getuser Authorization(); // Create data isolation object $dataIsolation = $this->createDataIsolation($userAuthorization); // Validate Itempermission $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); // Throughtopic ServiceGetItemunder topic list $result = $this->topicDomainService->getProjectTopicsWithPagination( $projectId, $dataIsolation->getcurrent user Id(), $page, $pageSize ); // Convert to TopicItemDTO $topicDTOs = []; foreach ($result['list'] as $topic) 
{
 $topicDTOs[] = TopicItemDTO::fromEntity($topic)->toArray(); 
}
 return [ 'total' => $result['total'], 'list' => $topicDTOs, ]; 
}
 
    public function checkFilelist Update(RequestContext $requestContext, int $projectId, DataIsolation $dataIsolation): array 
{
 // $userAuthorization = $requestContext->getuser Authorization(); // $projectEntity = $this->projectDomainService->getProject($projectId, $userAuthorization->getId()); // ThroughServiceGettopic list // $result = $this->taskDomainService->getTaskAttachmentsByTopicId( // (int) $projectEntity->getcurrent TopicId(), // $dataIsolation, // 1, // 2000 // ); // // $lastUpdatedAt = $this->taskFileDomainService->getLatestUpdatedByProjectId($projectId); // $topicEntity = $this->topicDomainService->getTopicById($projectEntity->getcurrent TopicId()); // $taskEntity = $this->taskDomainService->getTaskBySandboxId($topicEntity->getSandboxId()); // # #Detectgit version database filestable whether Match // $result = $this->workspaceDomainService->diffFilelist AndVersionFile($result, $projectId, $dataIsolation->getcurrent OrganizationCode(), (string) $taskEntity->getId(), $topicEntity->getSandboxId()); // if ($result) 
{
 // $lastUpdatedAt = date('Y-m-d H:i:s'); // 
}
 $lastUpdatedAt = $this->taskFileDomainService->getLatestUpdatedByProjectId($projectId); return [ 'last_updated_at' => $lastUpdatedAt, ]; 
}
 /** * GetItemlist Loginuser Schema. */ 
    public function getProjectAttachments(RequestContext $requestContext, GetProjectAttachmentsRequestDTO $requestDTO): array 
{
 $userAuthorization = $requestContext->getuser Authorization(); // Validate ItemExistAll $projectEntity = $this->getAccessibleProject((int) $requestDTO->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); // CreateBased onuser Data $dataIsolation = $this->createDataIsolation($userAuthorization); // Getlist workDirfor RelativePathCalculate  return $this->getProjectAttachmentlist ($dataIsolation, $requestDTO, $projectEntity->getWorkDir() ?? ''); 
}
 /** * GetItemlist V2Loginuser SchemaReturn TreeStructureSupportTimeFilter. */ 
    public function getProjectAttachmentsV2(RequestContext $requestContext, GetProjectAttachmentsV2RequestDTO $requestDTO): array 
{
 $userAuthorization = $requestContext->getuser Authorization(); // Validate ItemExistAll $projectEntity = $this->getAccessibleProject((int) $requestDTO->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); // CreateBased onuser Data $dataIsolation = $this->createDataIsolation($userAuthorization); // Getlist Return TreeStructureUsing storage_type Filter return $this->getProjectAttachmentlist V2($dataIsolation, $requestDTO, $projectEntity->getWorkDir() ?? ''); 
}
 /** * GetItemlist . */ 
    public function getProjectAttachmentsFromAudit(RequestContext $requestContext, GetProjectAttachmentsRequestDTO $requestDTO): array 
{
 $userAuthorization = $requestContext->getuser Authorization(); $projectEntity = $this->projectDomainService->getProjectNotuser Id((int) $requestDTO->getProjectId()); // CreateBased onuser Data $dataIsolation = $this->createDataIsolation($userAuthorization); return $this->getProjectAttachmentlist ($dataIsolation, $requestDTO, $projectEntity->getWorkDir() ?? ''); 
}
 /** * ThroughAccess tokenGetItemlist . */ 
    public function getProjectAttachmentsByAccessToken(GetProjectAttachmentsRequestDTO $requestDto): array 
{
 $token = $requestDto->getToken(); // FromGetData if (! AccessTokenUtil::validate($token)) 
{
 ExceptionBuilder::throw(ShareErrorCode::PARAMETER_CHECK_FAILURE, 'share.parameter_check_failure'); 
}
 $shareId = AccessTokenUtil::getShareId($token); $shareEntity = $this->resourceShareDomainService->getValidShareById($shareId); if (! $shareEntity) 
{
 ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.resource_not_found'); 
}
 // Frontendcurrent Sharetopic also GetItemlist Interfaceneed CompatibleShareTypeyes topic Otherwisedirectly process ResourceType::Project $projectId = ''; $workDir = ''; switch ($shareEntity->getResourceType()) 
{
 case ResourceType::Topic->value: $topicEntity = $this->topicDomainService->getTopicWithdelete d((int) $shareEntity->getResourceId()); if (empty($topicEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 $projectId = (string) $topicEntity->getProjectId(); $workDir = $topicEntity->getWorkDir(); break; case ResourceType::Project->value: $projectEntity = $this->projectDomainService->getProjectNotuser Id((int) $shareEntity->getResourceId()); if (empty($projectEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_NOT_FOUND, 'project.project_not_found'); 
}
 $projectId = (string) $projectEntity->getId(); $workDir = $projectEntity->getWorkDir(); break; default: ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported'); 
}
 $requestDto->setProjectId($projectId); $organizationCode = AccessTokenUtil::getOrganizationCode($token); // CreateDataIsolation $dataIsolation = DataIsolation::simpleMake($organizationCode, ''); // TokenSchemanot needed workDirprocess EmptyString return $this->getProjectAttachmentlist ($dataIsolation, $requestDto, $workDir); 
}
 /** * ThroughAccess tokenGetItemlist V2Return TreeStructure. */ 
    public function getProjectAttachmentsByAccessTokenV2(GetProjectAttachmentsV2RequestDTO $requestDto): array 
{
 $token = $requestDto->getToken(); // FromGetData if (! AccessTokenUtil::validate($token)) 
{
 ExceptionBuilder::throw(ShareErrorCode::PARAMETER_CHECK_FAILURE, 'share.parameter_check_failure'); 
}
 $shareId = AccessTokenUtil::getShareId($token); $shareEntity = $this->resourceShareDomainService->getValidShareById($shareId); if (! $shareEntity) 
{
 ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.resource_not_found'); 
}
 // Frontendcurrent Sharetopic also GetItemlist Interfaceneed CompatibleShareTypeyes topic Otherwisedirectly process ResourceType::Project $projectId = ''; $workDir = ''; switch ($shareEntity->getResourceType()) 
{
 case ResourceType::Topic->value: $topicEntity = $this->topicDomainService->getTopicWithdelete d((int) $shareEntity->getResourceId()); if (empty($topicEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 $projectId = (string) $topicEntity->getProjectId(); $workDir = $topicEntity->getWorkDir(); break; case ResourceType::Project->value: $projectEntity = $this->projectDomainService->getProjectNotuser Id((int) $shareEntity->getResourceId()); if (empty($projectEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_NOT_FOUND, 'project.project_not_found'); 
}
 $projectId = (string) $projectEntity->getId(); $workDir = $projectEntity->getWorkDir(); break; default: ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported'); 
}
 $requestDto->setProjectId($projectId); $organizationCode = AccessTokenUtil::getOrganizationCode($token); // CreateDataIsolation $dataIsolation = DataIsolation::simpleMake($organizationCode, ''); // TokenSchemanot needed workDirprocess EmptyStringV2 Return TreeStructure return $this->getProjectAttachmentlist V2($dataIsolation, $requestDto, $workDir); 
}
 
    public function getCloudFiles(RequestContext $requestContext, int $projectId): array 
{
 $userAuthorization = $requestContext->getuser Authorization(); // Create data isolation object $dataIsolation = $this->createDataIsolation($userAuthorization); $projectEntity = $this->getAccessibleProject($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode()); return $this->taskFileDomainService->getProjectFilesFromCloudStorage($dataIsolation->getcurrent OrganizationCode(), $projectEntity->getWorkDir()); 
}
 
    public function getProjectRoleByuser Id(int $projectId, string $userId): string 
{
 $projectMemberEntity = $this->projectMemberDomainService->getMemberByProjectAnduser ($projectId, $userId); return $projectMemberEntity ? $projectMemberEntity->getRoleValue() : ''; 
}
 
    public function hasProjectMember(int $projectId): bool 
{
 $projectIdMapMemberCounts = $this->projectMemberDomainService->getProjectMembersCounts([$projectId]); return (bool) ($projectIdMapMemberCounts[$projectId] ?? 0) > 0; 
}
 /** * Fork project. */ 
    public function forkProject(RequestContext $requestContext, ForkProjectRequestDTO $requestDTO): array 
{
 $this->logger->info('Starting project fork process'); // check resource is allow fork $resourceShareEntity = $this->resourceShareDomainService->getShareByResourceId($requestDTO->sourceProjectId); if (empty($resourceShareEntity) || $resourceShareEntity->getResourceType() != ResourceType::Project->value) 
{
 ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, trans('share.resource_not_found')); 
}
 // Get user authorization information $userAuthorization = $requestContext->getuser Authorization(); // Create data isolation object $dataIsolation = $this->createDataIsolation($userAuthorization); // Validate target workspace exists $workspaceEntity = $this->workspaceDomainService->getWorkspaceDetail($requestDTO->getTargetWorkspaceId()); if (empty($workspaceEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_NOT_FOUND, trans('workspace.workspace_not_found')); 
}
 // Validate target workspace belongs to user if ($workspaceEntity->getuser Id() !== $userAuthorization->getId()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_ACCESS_DENIED, trans('workspace.workspace_access_denied')); 
}
 Db::beginTransaction(); try 
{
 // trigger fork start check event AsyncEventUtil::dispatch(new ForkProjectStartEvent( $dataIsolation->getcurrent OrganizationCode(), $dataIsolation->getcurrent user Id() )); $this->logger->info(sprintf( 'Dispatched fork project start event, organization: %s, user: %s', $dataIsolation->getcurrent OrganizationCode(), $dataIsolation->getcurrent user Id() )); // Create fork record and project /** * @var ProjectEntity $forkProjectEntity * @var ProjectForkEntity $forkProjectrecord Entity */ [$forkProjectEntity, $forkProjectrecord Entity] = $this->projectDomainService->forkProject( $requestDTO->getSourceProjectId(), $requestDTO->getTargetWorkspaceId(), $requestDTO->getTargetProjectName(), $dataIsolation->getcurrent user Id(), $dataIsolation->getcurrent OrganizationCode() ); $this->logger->info(sprintf( 'Created fork record, fork project ID: %d, fork record ID: %d', $forkProjectEntity->getId(), $forkProjectrecord Entity->getId() )); $this->logger->info(sprintf('CreateDefault project, projectId=%s', $forkProjectEntity->getId())); $workDir = WorkDirectoryUtil::getWorkDir($dataIsolation->getcurrent user Id(), $forkProjectEntity->getId()); // Initialize Magic Chat Conversation [$chatConversationId, $chatConversationTopicId] = $this->chatAppService->initMagicChatConversation($dataIsolation); // Step 4: Create default topic $this->logger->info('StartCreateDefault topic'); $topicEntity = $this->topicDomainService->createTopic( $dataIsolation, $workspaceEntity->getId(), $forkProjectEntity->getId(), $chatConversationId, $chatConversationTopicId, '', $workDir ); $this->logger->info(sprintf('CreateDefault topicSuccess, topicId=%s', $topicEntity->getId())); // Set workspace info $workspaceEntity->setcurrent TopicId($topicEntity->getId()); $workspaceEntity->setcurrent ProjectId($forkProjectEntity->getId()); $this->workspaceDomainService->saveWorkspaceEntity($workspaceEntity); $this->logger->info(sprintf('workspace %sSet current topic %s', $workspaceEntity->getId(), $topicEntity->getId())); $forkProjectEntity->setcurrent TopicId($topicEntity->getId()); $forkProjectEntity->setWorkspaceId($workspaceEntity->getId()); $forkProjectEntity->setWorkDir($workDir); $this->projectDomainService->saveProjectEntity($forkProjectEntity); $this->logger->info(sprintf('Item%sSet current topic %s', $forkProjectEntity->getId(), $topicEntity->getId())); // Publish fork event for file migration $event = new ProjectForkEvent( $requestDTO->getSourceProjectId(), $forkProjectEntity->getId(), $dataIsolation->getcurrent user Id(), $dataIsolation->getcurrent OrganizationCode(), $forkProjectrecord Entity->getId() ); $publisher = new ProjectForkPublisher($event); $this->producer->produce($publisher); $this->logger->info(sprintf( 'Published fork event, event ID: %s', $event->getEventId() )); Db::commit(); return ForkProjectResponseDTO::fromEntity($forkProjectrecord Entity)->toArray(); 
}
 catch (EventException $e) 
{
 Db::rollReturn (); ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_FORK_ACCESS_DENIED, $e->getMessage()); 
}
 catch (Throwable $e) 
{
 Db::rollReturn (); $this->logger->error('Fork project failed, error: ' . $e->getMessage(), ['request' => $requestDTO->toArray()]); throw $e; 
}
 
}
 /** * check fork project status. */ 
    public function checkForkProjectStatus(RequestContext $requestContext, int $projectId): array 
{
 // Get user authorization information $userAuthorization = $requestContext->getuser Authorization(); // Find fork record by fork project ID $projectFork = $this->projectDomainService->findByForkProjectId($projectId); if (! $projectFork) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_NOT_FOUND, trans('project.project_not_found')); 
}
 // check if user has access to this fork if ($projectFork->getuser Id() !== $userAuthorization->getId()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_ACCESS_DENIED, trans('project.project_access_denied')); 
}
 return ForkStatusResponseDTO::fromEntity($projectFork)->toArray(); 
}
 /** * Migrate project file (called by subscriber). */ 
    public function migrateProjectFile(ProjectForkEvent $event): void 
{
 $this->logger->info(sprintf( 'Starting file migration for fork record ID: %d', $event->getForkrecord Id() )); try 
{
 // Call file domain service to handle file migration $dataIsolation = DataIsolation::simpleMake($event->getOrganizationCode(), $event->getuser Id()); $sourceProjectEntity = $this->projectDomainService->getProjectNotuser Id($event->getSourceProjectId()); $forkProjectEntity = $this->projectDomainService->getProjectNotuser Id($event->getForkProjectId()); $forkProjectrecord Entity = $this->projectDomainService->getForkProjectrecord ById($event->getForkrecord Id()); $this->taskFileDomainService->migrateProjectFile($dataIsolation, $sourceProjectEntity, $forkProjectEntity, $forkProjectrecord Entity); $this->logger->info(sprintf( 'File migration batch completed for fork record ID: %d', $event->getForkrecord Id() )); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'File migration failed for fork record ID: %d, error: %s', $event->getForkrecord Id(), $e->getMessage() )); throw $e; 
}
 
}
 /** * Move project to another workspace. */ 
    public function moveProject(RequestContext $requestContext, MoveProjectRequestDTO $requestDTO): array 
{
 $this->logger->info('Starting project move process'); // Get user authorization information $userAuthorization = $requestContext->getuser Authorization(); // Create data isolation object $dataIsolation = $this->createDataIsolation($userAuthorization); // Validate target workspace exists and belongs to user $targetWorkspaceEntity = $this->workspaceDomainService->getWorkspaceDetail($requestDTO->getTargetWorkspaceId()); if (empty($targetWorkspaceEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_NOT_FOUND, trans('workspace.workspace_not_found')); 
}
 if ($targetWorkspaceEntity->getuser Id() !== $userAuthorization->getId()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::WORKSPACE_ACCESS_DENIED, trans('workspace.workspace_access_denied')); 
}
 // Validate source project exists and belongs to user (only project owner can move) $sourceProjectEntity = $this->projectDomainService->getProject( $requestDTO->getSourceProjectId(), $dataIsolation->getcurrent user Id() ); // Call domain service to handle the move $movedProjectEntity = $this->projectDomainService->moveProject( $requestDTO->getSourceProjectId(), $requestDTO->getTargetWorkspaceId(), $userAuthorization->getId() ); $this->logger->info(sprintf( 'Project moved successfully, project ID: %d, from workspace: %d to workspace: %d', $movedProjectEntity->getId(), $sourceProjectEntity->getWorkspaceId(), $requestDTO->getTargetWorkspaceId() )); return [ 'project_id' => (string) $movedProjectEntity->getId(), ]; 
}
 /** * GetItemlist Core. */ 
    public function getProjectAttachmentlist (DataIsolation $dataIsolation, GetProjectAttachmentsRequestDTO $requestDTO, string $workDir = ''): array 
{
 // ThroughTaskServiceGetItemunder list $result = $this->taskDomainService->getTaskAttachmentsByProjectId( (int) $requestDTO->getProjectId(), $dataIsolation, $requestDTO->getPage(), $requestDTO->getPageSize(), $requestDTO->getFileType(), StorageType::WORKSPACE->value, ); // process File URL $list = []; $fileKeys = []; // list UsingTaskFileItemDTOprocess foreach ($result['list'] as $entity) 
{
 /** * @var TaskFileEntity $entity */ // CreateDTO $dto = new TaskFileItemDTO(); $dto->fileId = (string) $entity->getFileId(); $dto->taskId = (string) $entity->getTaskId(); $dto->fileType = $entity->getFileType(); $dto->fileName = $entity->getFileName(); $dto->fileExtension = $entity->getFileExtension(); $dto->fileKey = $entity->getFileKey(); $dto->fileSize = $entity->getFileSize(); $dto->isHidden = $entity->getIsHidden(); $dto->updatedAt = $entity->getUpdatedAt(); $dto->topicId = (string) $entity->getTopicId(); $dto->relativeFilePath = WorkDirectoryUtil::getRelativeFilePath($entity->getFileKey(), $workDir); $dto->isDirectory = $entity->getIsDirectory(); $dto->metadata = FileMetadataUtil::getMetadataObject($entity->getMetadata()); // Add project_id Field $dto->projectId = (string) $entity->getProjectId(); // Set SortField $dto->sort = $entity->getSort(); $dto->fileUrl = ''; $dto->parentId = (string) $entity->getParentId(); $dto->source = $entity->getSource(); // Add file_url Field $fileKey = $entity->getFileKey(); // Determinefile keywhether DuplicateIfDuplicateSkip // IfDirectoryalso Skip if (in_array($fileKey, $fileKeys) || empty($entity->getParentId())) 
{
 continue; 
}
 $fileKeys[] = $fileKey; $list[] = $dto->toArray(); 
}
 // BuildTreeStructureLoginuser SchemaHave $tree = FileTreeUtil::assembleFilesTreeByParentId($list); if ($result['total'] > 3000) 
{
 $this->logger->error(sprintf('Project attachment list is too large, project ID: %d, total: %d', $requestDTO->getProjectId(), $result['total'])); 
}
 return [ 'total' => $result['total'], 'list' => $list, 'tree' => $tree, ]; 
}
 /** * GetItemlist Core V2Return TreeStructureSupportDatabaseLevelUpdate timeFilter. */ 
    public function getProjectAttachmentlist V2(DataIsolation $dataIsolation, GetProjectAttachmentsV2RequestDTO $requestDTO, string $workDir = ''): array 
{
 // ThroughTaskServiceGetItemunder list UsingDatabase level time filter $result = $this->taskDomainService->getTaskAttachmentsByProjectId( (int) $requestDTO->getProjectId(), $dataIsolation, $requestDTO->getPage(), $requestDTO->getPageSize(), $requestDTO->getFileType(), StorageType::WORKSPACE->value, // V2 FixedUsing workspace Type $requestDTO->getUpdatedAfter() // Database level time filter ); // process File URL $list = []; $fileKeys = []; // list UsingTaskFileItemDTOprocess foreach ($result['list'] as $entity) 
{
 /** * @var TaskFileEntity $entity */ // CreateDTO $dto = new TaskFileItemDTO(); $dto->fileId = (string) $entity->getFileId(); $dto->taskId = (string) $entity->getTaskId(); $dto->fileType = $entity->getFileType(); $dto->fileName = $entity->getFileName(); $dto->fileExtension = $entity->getFileExtension(); $dto->fileKey = $entity->getFileKey(); $dto->fileSize = $entity->getFileSize(); $dto->isHidden = $entity->getIsHidden(); $dto->updatedAt = $entity->getUpdatedAt(); $dto->topicId = (string) $entity->getTopicId(); $dto->relativeFilePath = WorkDirectoryUtil::getRelativeFilePath($entity->getFileKey(), $workDir); $dto->isDirectory = $entity->getIsDirectory(); $dto->metadata = FileMetadataUtil::getMetadataObject($entity->getMetadata()); // Add project_id Field $dto->projectId = (string) $entity->getProjectId(); // Set SortField $dto->sort = $entity->getSort(); $dto->fileUrl = ''; $dto->parentId = (string) $entity->getParentId(); $dto->source = $entity->getSource(); // Add file_url Field $fileKey = $entity->getFileKey(); // Determinefile keywhether DuplicateIfDuplicateSkip // IfDirectoryalso Skip if (in_array($fileKey, $fileKeys) || empty($entity->getParentId())) 
{
 continue; 
}
 $fileKeys[] = $fileKey; $list[] = $dto->toArray(); 
}
 return [ 'total' => $result['total'], 'list' => $list, ]; 
}
 
}
 

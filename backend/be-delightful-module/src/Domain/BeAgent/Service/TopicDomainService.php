<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Service;

use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\MagicSeqEntity;
use App\Domain\Chat\Entity\MagicTopicEntity;
use App\Domain\Chat\Entity\ValueObject\MagicMessageStatus;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Repository\Facade\MagicChatSeqRepositoryInterface;
use App\Domain\Chat\Repository\Facade\MagicChatTopicRepositoryInterface;
use App\Domain\Chat\Repository\Facade\MagicMessageRepositoryInterface;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\user Type;
use App\Domain\File\Repository\Persistence\Facade\CloudFileRepositoryInterface;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\CreationSource;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\query \Topicquery ;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskMessageRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TopicRepositoryInterface;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
use function Hyperf\Translation\trans;

class TopicDomainService 
{
 
    private LoggerInterface $logger; 
    public function __construct( 
    protected TopicRepositoryInterface $topicRepository, 
    protected TaskRepositoryInterface $taskRepository, 
    protected MagicMessageRepositoryInterface $magicMessageRepository, 
    protected MagicChatSeqRepositoryInterface $magicSeqRepository, 
    protected MagicChatTopicRepositoryInterface $magicChatTopicRepository, 
    protected TaskMessageRepositoryInterface $taskMessageRepository, 
    protected CloudFileRepositoryInterface $cloudFileRepository, LoggerFactory $loggerFactory, ) 
{
 $this->logger = $loggerFactory->get('topic'); 
}
 
    public function getTopicById(int $id): ?TopicEntity 
{
 return $this->topicRepository->getTopicById($id); 
}
 
    public function getTopicWithdelete d(int $id): ?TopicEntity 
{
 return $this->topicRepository->getTopicWithdelete d($id); 
}
 
    public function getTopicBySandboxId(string $sandboxId): ?TopicEntity 
{
 return $this->topicRepository->getTopicBySandboxId($sandboxId); 
}
 
    public function getSandboxIdByTopicId(int $topicId): ?string 
{
 $topic = $this->getTopicById($topicId); if (empty($topic)) 
{
 return null; 
}
 return $topic->getSandboxId(); 
}
 
    public function updateTopicStatus(int $id, int $taskId, TaskStatus $taskStatus): bool 
{
 return $this->topicRepository->updateTopicStatus($id, $taskId, $taskStatus); 
}
 
    public function updateTopicStatusAndSandboxId(int $id, int $taskId, TaskStatus $taskStatus, string $sandboxId): bool 
{
 return $this->topicRepository->updateTopicStatusAndSandboxId($id, $taskId, $taskStatus, $sandboxId); 
}
 /** * Get topic list whose update time exceeds specified time. * * @param string $timeThreshold Time threshold, if topic update time is earlier than this time, it will be included in the result * @param int $limit Maximum number of results returned * @return array<TopicEntity> Topic entity list */ 
    public function getTopicsExceedingUpdateTime(string $timeThreshold, int $limit = 100): array 
{
 return $this->topicRepository->getTopicsExceedingUpdateTime($timeThreshold, $limit); 
}
 /** * Get topic entity by ChatTopicId. */ 
    public function getTopicByChatTopicId(DataIsolation $dataIsolation, string $chatTopicId): ?TopicEntity 
{
 $conditions = [ 'user_id' => $dataIsolation->getcurrent user Id(), 'chat_topic_id' => $chatTopicId, ]; $result = $this->topicRepository->getTopicsByConditions($conditions, false); if (empty($result['list'])) 
{
 return null; 
}
 return $result['list'][0]; 
}
 
    public function getTopicMode(DataIsolation $dataIsolation, int $topicId): string 
{
 $conditions = [ 'id' => $topicId, 'user_id' => $dataIsolation->getcurrent user Id(), ]; $result = $this->topicRepository->getTopicsByConditions($conditions, false); if (empty($result['list'])) 
{
 return ''; 
}
 return $result['list'][0]->getTopicMode() ?? ''; 
}
 /** * @return array<TopicEntity> */ 
    public function getuser RunningTopics(DataIsolation $dataIsolation): array 
{
 $conditions = [ 'user_id' => $dataIsolation->getcurrent user Id(), 'current_task_status' => TaskStatus::RUNNING, ]; $result = $this->topicRepository->getTopicsByConditions($conditions, false); if (empty($result['list'])) 
{
 return []; 
}
 return $result['list']; 
}
 /** * Get topic entity by ChatTopicId. */ 
    public function getTopicOnlyByChatTopicId(string $chatTopicId): ?TopicEntity 
{
 $conditions = [ 'chat_topic_id' => $chatTopicId, ]; $result = $this->topicRepository->getTopicsByConditions($conditions, false); if (empty($result['list'])) 
{
 return null; 
}
 return $result['list'][0]; 
}
 
    public function updateTopicWhereUpdatedAt(TopicEntity $topicEntity, string $updatedAt): bool 
{
 return $this->topicRepository->updateTopicWithUpdatedAt($topicEntity, $updatedAt); 
}
 
    public function updateTopicStatusBySandboxIds(array $sandboxIds, TaskStatus $taskStatus): bool 
{
 return $this->topicRepository->updateTopicStatusBySandboxIds($sandboxIds, $taskStatus->value); 
}
 
    public function updateTopic(DataIsolation $dataIsolation, int $id, string $topicName): TopicEntity 
{
 // Findcurrent topic whether yes $topicEntity = $this->topicRepository->getTopicById($id); if (empty($topicEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 if ($topicEntity->getuser Id() !== $dataIsolation->getcurrent user Id()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.topic_access_denied'); 
}
 $topicEntity->setTopicName($topicName); $this->topicRepository->updateTopic($topicEntity); return $topicEntity; 
}
 /** * Create topic. * * @param DataIsolation $dataIsolation Data isolation object * @param int $workspaceId Workspace ID * @param int $projectId Project ID * @param string $chatConversationId Chat conversation ID * @param string $chatTopicId Chat topic ID * @param string $topicName Topic name * @param string $workDir Work directory * @return TopicEntity Created topic entity * @throws Exception If creation fails */ 
    public function createTopic( DataIsolation $dataIsolation, int $workspaceId, int $projectId, string $chatConversationId, string $chatTopicId, string $topicName = '', string $workDir = '', string $topicMode = '', int $source = CreationSource::USER_CREATED->value, string $sourceId = '' ): TopicEntity 
{
 // Get current user info $userId = $dataIsolation->getcurrent user Id(); $organizationCode = $dataIsolation->getcurrent OrganizationCode(); $currentTime = date('Y-m-d H:i:s'); // Validate required parameters if (empty($chatTopicId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic.id_required'); 
}
 // Create topic entity $topicEntity = new TopicEntity(); $topicEntity->setuser Id($userId); $topicEntity->setuser OrganizationCode($organizationCode); $topicEntity->setWorkspaceId($workspaceId); $topicEntity->setProjectId($projectId); $topicEntity->setChatTopicId($chatTopicId); $topicEntity->setChatConversationId($chatConversationId); $topicEntity->setTopicName($topicName); $topicEntity->setSandboxId(''); // Initially empty $topicEntity->setWorkDir($workDir); // Initially empty $topicEntity->setcurrent TaskId(0); $topicEntity->setcurrent TaskStatus(TaskStatus::WAITING); // Default status: waiting $topicEntity->setSource($source); $topicEntity->setSourceId($sourceId); // Set source ID $topicEntity->setCreatedUid($userId); // Set creator user ID $topicEntity->setUpdatedUid($userId); // Set updater user ID $topicEntity->setCreatedAt($currentTime); if (! empty($topicMode)) 
{
 $topicEntity->setTopicMode($topicMode); 
}
 return $this->topicRepository->createTopic($topicEntity); 
}
 
    public function deleteTopicsByWorkspaceId(DataIsolation $dataIsolation, int $workspaceId) 
{
 $conditions = [ 'workspace_id' => $workspaceId, ]; $data = [ 'deleted_at' => date('Y-m-d H:i:s'), 'updated_uid' => $dataIsolation->getcurrent user Id(), 'updated_at' => date('Y-m-d H:i:s'), ]; return $this->topicRepository->updateTopicByCondition($conditions, $data); 
}
 
    public function deleteTopicsByProjectId(DataIsolation $dataIsolation, int $projectId) 
{
 $conditions = [ 'project_id' => $projectId, ]; $data = [ 'deleted_at' => date('Y-m-d H:i:s'), 'updated_uid' => $dataIsolation->getcurrent user Id(), 'updated_at' => date('Y-m-d H:i:s'), ]; return $this->topicRepository->updateTopicByCondition($conditions, $data); 
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
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.topic_access_denied'); 
}
 // Set Deletion time $topicEntity->setdelete dAt(date('Y-m-d H:i:s')); // Set Updateuser ID $topicEntity->setUpdatedUid($userId); $topicEntity->setUpdatedAt(date('Y-m-d H:i:s')); // SaveUpdate return $this->topicRepository->updateTopic($topicEntity); 
}
 /** * Get project topics with pagination * GetItemunder topic list SupportPagingSort. */ 
    public function getProjectTopicsWithPagination( int $projectId, string $userId, int $page = 1, int $pageSize = 10 ): array 
{
 $conditions = [ 'project_id' => $projectId, 'user_id' => $userId, ]; return $this->topicRepository->getTopicsByConditions( $conditions, true, // needPagination $pageSize, $page, 'id', // Creation timeSort 'desc' // Descending ); 
}
 /** * BatchCalculate workspace Status. * * @param array $workspaceIds workspace IDArray * @param null|string $userId Optionaluser IDScheduledCalculate user topic Status * @return array ['workspace_id' => 'status'] KeyValuePair */ 
    public function calculateWorkspaceStatusBatch(array $workspaceIds, ?string $userId = null): array 
{
 if (empty($workspaceIds)) 
{
 return []; 
}
 // FromGetHaveRunningtopic workspace IDlist $runningWorkspaceIds = $this->topicRepository->getRunningWorkspaceIds($workspaceIds, $userId); // Calculate each workspace Status $result = []; foreach ($workspaceIds as $workspaceId) 
{
 $result[$workspaceId] = in_array($workspaceId, $runningWorkspaceIds, true) ? TaskStatus::RUNNING->value : TaskStatus::WAITING->value; 
}
 return $result; 
}
 /** * BatchCalculate ItemStatus. * * @param array $projectIds Project IDArray * @param null|string $userId Optionaluser IDScheduledquery user topic * @return array ['project_id' => 'status'] KeyValuePair */ 
    public function calculateProjectStatusBatch(array $projectIds, ?string $userId = null): array 
{
 if (empty($projectIds)) 
{
 return []; 
}
 // FromGetHaveRunningtopic Project IDlist $runningProjectIds = $this->topicRepository->getRunningProjectIds($projectIds, $userId); // Calculate each ItemStatus $result = []; foreach ($projectIds as $projectId) 
{
 $result[$projectId] = in_array($projectId, $runningProjectIds, true) ? TaskStatus::RUNNING->value : TaskStatus::WAITING->value; 
}
 return $result; 
}
 /** * Updatetopic Name. * * @param DataIsolation $dataIsolation DataObject * @param int $id topic primary key ID * @param string $topicName topic Name * @return bool whether UpdateSuccess * @throws Exception IfUpdate failed */ 
    public function updateTopicName(DataIsolation $dataIsolation, int $id, string $topicName): bool 
{
 // Getcurrent user ID $userId = $dataIsolation->getcurrent user Id(); // Throughprimary key IDGettopic $topicEntity = $this->topicRepository->getTopicById($id); if (! $topicEntity) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'topic.not_found'); 
}
 // check user permission check topic whether belongs to current user  if ($topicEntity->getuser Id() !== $userId) 
{
 ExceptionBuilder::throw(GenericErrorCode::AccessDenied, 'topic.access_denied'); 
}
 $conditions = [ 'id' => $id, ]; $data = [ 'topic_name' => $topicName, 'updated_uid' => $userId, 'updated_at' => date('Y-m-d H:i:s'), ]; // SaveUpdate return $this->topicRepository->updateTopicByCondition($conditions, $data); 
}
 
    public function updateTopicSandboxId(DataIsolation $dataIsolation, int $id, string $sandboxId): bool 
{
 $conditions = [ 'id' => $id, ]; $data = [ 'sandbox_id' => $sandboxId, 'updated_uid' => $dataIsolation->getcurrent user Id(), 'updated_at' => date('Y-m-d H:i:s'), ]; return $this->topicRepository->updateTopicByCondition($conditions, $data); 
}
 /** * Validate topic for message queue operations. * check s both ownership and running status. * * @param DataIsolation $dataIsolation Data isolation object * @param int $topicId Topic ID * @return TopicEntity Topic entity if validation passes * @throws Exception If validation fails */ 
    public function validateTopicForMessageQueue(DataIsolation $dataIsolation, int $topicId): TopicEntity 
{
 // Get topic by ID $topicEntity = $this->topicRepository->getTopicById($topicId); if (empty($topicEntity)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 // check ownership if ($topicEntity->getuser Id() !== $dataIsolation->getcurrent user Id()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_ACCESS_DENIED, 'topic.topic_access_denied'); 
}
 return $topicEntity; 
}
 /** * check if topic is running by user. * * @param DataIsolation $dataIsolation Data isolation object * @param int $topicId Topic ID * @return bool True if topic is running and belongs to user */ 
    public function isTopicRunningByuser (DataIsolation $dataIsolation, int $topicId): bool 
{
 try 
{
 $this->validateTopicForMessageQueue($dataIsolation, $topicId); return true; 
}
 catch (Exception $e) 
{
 return false; 
}
 
}
 // ======================= MessageRollbackrelated Method ======================= /** * execute MessageRollback. */ 
    public function rollbackMessages(string $targetSeqId): void 
{
 // According toseq_idGetmagic_message_id $magicMessageId = $this->topicRepository->getMagicMessageIdBySeqId($targetSeqId); if (empty($magicMessageId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.seq_id_not_found'); 
}
 // GetAllrelated seq_idAll $baseSeqIds = $this->topicRepository->getAllSeqIdsByMagicMessageId($magicMessageId); if (empty($baseSeqIds)) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.magic_message_id_not_found'); 
}
 // GetFromcurrent MessageStartAllseq_idscurrent MessageMessage $allSeqIds = $this->topicRepository->getAllSeqIdsFromcurrent ($baseSeqIds); if (empty($allSeqIds)) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.seq_id_not_found'); 
}
 // AtTransactionin execute delete Db::transaction(function () use ($allSeqIds, $targetSeqId) 
{
 // delete topic_messagesData $this->topicRepository->deleteTopicMessages($allSeqIds);
// delete messagessequencesData $this->topicRepository->deleteMessagesAndSequencesBySeqIds($allSeqIds); // delete magic_super_agent_messagetable Data $this->topicRepository->deleteSuperAgentMessagesFromSeqId((int) $targetSeqId); 
}
); 
}
 /** * execute MessageRollbackStartmark Statusdelete . */ 
    public function rollbackMessagesStart(string $targetSeqId): void 
{
 // According toseq_idGetmagic_message_id $magicMessageId = $this->topicRepository->getMagicMessageIdBySeqId($targetSeqId); if (empty($magicMessageId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.seq_id_not_found'); 
}
 // GetAllrelated seq_idAll $baseSeqIds = $this->topicRepository->getAllSeqIdsByMagicMessageId($magicMessageId); if (empty($baseSeqIds)) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.magic_message_id_not_found'); 
}
 // GetFromcurrent MessageStartAllseq_idscurrent MessageMessage $allSeqIdsFromcurrent = $this->topicRepository->getAllSeqIdsFromcurrent ($baseSeqIds); if (empty($allSeqIdsFromcurrent )) 
{
 ExceptionBuilder::throw(GenericErrorCode::IllegalOperation, 'chat.message.rollback.seq_id_not_found'); 
}
 // GetLess thancurrent MessageAllMessage $allSeqIdsBeforecurrent = $this->topicRepository->getAllSeqIdsBeforecurrent ($baseSeqIds); // AtTransactionin execute Statusupdate operation Db::transaction(function () use ($allSeqIdsFromcurrent , $allSeqIdsBeforecurrent ) 
{
 // 1. Less thantarget_message_idAllMessageSet as ViewStatusNormalStatus if (! empty($allSeqIdsBeforecurrent )) 
{
 $this->topicRepository->batchUpdateSeqStatus($allSeqIdsBeforecurrent , MagicMessageStatus::Read);

}
 // 2. mark messages with id greater than or equal to target_message_id as recalled status $this->topicRepository->batchUpdateSeqStatus($allSeqIdsFromcurrent , MagicMessageStatus::Revoked); 
}
); 
}
 /** * execute MessageRollbackSubmitdelete recalled status Message. */ 
    public function rollbackMessagesCommit(int $topicId, string $userId): void 
{
 // Gettopic in Allrecalled status Messageseq_ids $revokedSeqIds = $this->topicRepository->getRevokedSeqIdsByTopicId($topicId, $userId); if (empty($revokedSeqIds)) 
{
 // Don't haverecalled status Messagedirectly Return return; 
}
 // as ed UsingHavedelete need target_seq_idfor deleteSuperAgentMessagesFromSeqId // Minimumseq_idas targetEnsuredelete Allrelated super_agent_message $targetSeqId = min($revokedSeqIds); // AtTransactionin execute delete HaverollbackMessagesConsistent Db::transaction(function () use ($revokedSeqIds, $targetSeqId) 
{
 // delete topic_messagesData $this->topicRepository->deleteTopicMessages($revokedSeqIds);
// delete messagessequencesData $this->topicRepository->deleteMessagesAndSequencesBySeqIds($revokedSeqIds); // delete magic_super_agent_messagetable Data $this->topicRepository->deleteSuperAgentMessagesFromSeqId($targetSeqId); 
}
); 
}
 /** * execute MessageUndorecalled status MessageResumeas NormalStatus. * * @param int $topicId topic ID * @param string $userId user IDpermission Validate  */ 
    public function rollbackMessagesUndo(int $topicId, string $userId): void 
{
 $this->logger->info('[TopicDomain] Starting message rollback undo', [ 'topic_id' => $topicId, 'user_id' => $userId, ]); // Gettopic in Allrecalled status Messageseq_ids $revokedSeqIds = $this->topicRepository->getRevokedSeqIdsByTopicId($topicId, $userId); if (empty($revokedSeqIds)) 
{
 $this->logger->info('[TopicDomain] No revoked messages found for undo', [ 'topic_id' => $topicId, 'user_id' => $userId, ]); // Don't haverecalled status Messagedirectly Return return; 
}
 $this->logger->info('[TopicDomain] Found revoked messages for undo', [ 'topic_id' => $topicId, 'user_id' => $userId, 'revoked_seq_ids_count' => count($revokedSeqIds), ]); // AtTransactionin execute Statusupdate operation recalled status Resumeas ViewStatus Db::transaction(function () use ($revokedSeqIds) 
{
 // recalled status MessageResumeas ViewStatus $this->topicRepository->batchUpdateSeqStatus($revokedSeqIds, MagicMessageStatus::Read);

}
); $this->logger->info('[TopicDomain] Message rollback undo completed successfully', [ 'topic_id' => $topicId, 'user_id' => $userId, 'restored_seq_ids_count' => count($revokedSeqIds), ]); 
}
 /** * According totopic query ObjectGettopic list . * * @param Topicquery $query topic query Object * @return array
{
total: int, list: array<TopicEntity>
}
 topic list Total */ 
    public function getTopicsByquery (Topicquery $query): array 
{
 $conditions = $query->toConditions(); // query topic return $this->topicRepository->getTopicsByConditions( $conditions, true, $query->getPageSize(), $query->getPage(), $query->getOrderBy(), $query->getOrder() ); 
}
 /** * Gettopic StatusCount. * * @param DataIsolation $dataIsolation DataObject * @param string $organizationCode OptionalOrganizationCodeFilter * @return array topic StatusCountData */ 
    public function getTopicStatusMetrics(DataIsolation $dataIsolation, string $organizationCode = ''): array 
{
 // Buildquery Condition $conditions = []; // Ifed OrganizationCodeAddquery Condition if (! empty($organizationCode)) 
{
 $conditions['user_organization_code'] = $organizationCode; 
}
 // Usingquery CountData return $this->topicRepository->getTopicStatusMetrics($conditions); 
}
 /** * Batch get topic names by IDs. * * @param array $topicIds Topic ID array * @return array ['topic_id' => 'topic_name'] key-value pairs */ 
    public function getTopicNamesBatch(array $topicIds): array 
{
 if (empty($topicIds)) 
{
 return []; 
}
 return $this->topicRepository->getTopicNamesBatch($topicIds); 
}
 /** * Get chat history download URL for topic. * * @param int $topicId Topic ID * @return string Pre-signed download URL * @throws Exception If topic not found */ 
    public function getChatHistoryDownloadUrl(int $topicId): string 
{
 // Get topic entity $topicEntity = $this->topicRepository->getTopicById($topicId); if (! $topicEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found'); 
}
 // Build file path using WorkDirectoryUtil $filePath = WorkDirectoryUtil::getAgentChatHistoryFilePath( $topicEntity->getuser Id(), $topicEntity->getProjectId(), $topicId ); // Get organization code from topic entity (not current user) $organizationCode = $topicEntity->getuser OrganizationCode(); // Get full prefix and build complete object key $prefix = $this->cloudFileRepository->getFullPrefix($organizationCode); $objectKey = rtrim($prefix, '/') . '/' . ltrim($filePath, '/'); // Generate pre-signed URL for download $preSignedUrl = $this->cloudFileRepository->getPreSignedUrlByCredential( organizationCode: $organizationCode, objectKey: $objectKey, bucketType: StorageBucketType::SandBox, options: [ 'method' => 'GET', 'expires' => 3600, // 1 hour expiration 'filename' => sprintf('chat_history_%d.zip', $topicId), // Set download filename ] ); $this->logger->info('Generate d chat history download URL', [ 'topic_id' => $topicId, 'file_path' => $filePath, 'object_key' => $objectKey, 'organization_code' => $organizationCode, ]); return $preSignedUrl; 
}
 /** * Duplicate topic skeleton - create topic entity and IM conversation only. * This method only creates the topic entity and IM conversation, * without copying messages. Use copyTopicMessageFromOthers to copy messages. * * @param DataIsolation $dataIsolation Data isolation context * @param TopicEntity $sourceTopicEntity Source topic entity to duplicate from * @param string $newTopicName Name for the new topic * @return array Returns array containing topic_entity and im_conversation */ 
    public function duplicateTopicSkeleton( DataIsolation $dataIsolation, TopicEntity $sourceTopicEntity, string $newTopicName ): array 
{
 $this->logger->info('Creating topic skeleton for duplication', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'new_topic_name' => $newTopicName, ]); // Initialize IM conversation $imConversationResult = $this->initImConversationFromTopic($sourceTopicEntity, $newTopicName); // Create topic entity $targetTopicEntity = $this->copyTopicEntity( $dataIsolation, $sourceTopicEntity, $imConversationResult['user_conversation_id'], $imConversationResult['new_topic_id'], $newTopicName ); $this->logger->info('Topic skeleton created successfully', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'new_topic_id' => $targetTopicEntity->getId(), ]); return [ 'topic_entity' => $targetTopicEntity, 'im_conversation' => $imConversationResult, ]; 
}
 /** * Duplicate topic - complete duplication including skeleton and messages. * This is the main method for topic duplication (synchronous). * * @param DataIsolation $dataIsolation Data isolation context * @param TopicEntity $sourceTopicEntity Source topic entity to duplicate from * @param string $newTopicName Name for the new topic * @param int $targetMessageId Message ID to copy up to * @return TopicEntity The newly created topic entity * @throws Throwable */ 
    public function duplicateTopic( DataIsolation $dataIsolation, TopicEntity $sourceTopicEntity, string $newTopicName, int $targetMessageId ): TopicEntity 
{
 $this->logger->info('Starting complete topic duplication', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'new_topic_name' => $newTopicName, 'target_message_id' => $targetMessageId, ]); // Step 1: Create topic skeleton with IM conversation $duplicateResult = $this->duplicateTopicSkeleton( $dataIsolation, $sourceTopicEntity, $newTopicName ); $newTopicEntity = $duplicateResult['topic_entity']; $imConversationResult = $duplicateResult['im_conversation']; $this->logger->info('Topic skeleton created, starting message copy', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'new_topic_id' => $newTopicEntity->getId(), ]); // Step 2: Copy messages from source to target $this->copyTopicMessageFromOthers( $sourceTopicEntity, $newTopicEntity, $targetMessageId, $imConversationResult, null // No progress callback needed for synchronous operation ); $this->logger->info('complete topic duplication finished', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'new_topic_id' => $newTopicEntity->getId(), ]); return $newTopicEntity; 
}
 /** * Copy topic messages from source topic to target topic. * This method handles the copying of messages, IM messages, and chat history files. * * @param TopicEntity $sourceTopicEntity Source topic entity * @param TopicEntity $targetTopicEntity Target topic entity * @param int $messageId Message ID to copy up to * @param array $imConversationResult IM conversation result from duplicateTopicSkeleton * @param null|callable $progressCallback Optional progress callback function */ 
    public function copyTopicMessageFromOthers( TopicEntity $sourceTopicEntity, TopicEntity $targetTopicEntity, int $messageId, array $imConversationResult, ?callable $progressCallback = null ): void 
{
 $this->logger->info('Starting to copy topic messages', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'target_topic_id' => $targetTopicEntity->getId(), 'message_id' => $messageId, ]); // Copy messages $progressCallback && $progressCallback('running', 20, 'Copying topic messages'); $messageIdMapping = $this->copyTopicShareMessages($messageId, $sourceTopicEntity, $targetTopicEntity); // Get agent's seq id $progressCallback && $progressCallback('running', 40, 'Getting sequence IDs'); $seqlist = $this->getSeqIdByMessageId((string) $messageId); $userSeqId = (int) $seqlist ['user_seq_id']; $aiSeqId = (int) $seqlist ['ai_seq_id']; // Copy IM messages $progressCallback && $progressCallback('running', 60, 'Copying IM messages'); $this->copyImMessages($imConversationResult, $messageIdMapping, $userSeqId, $aiSeqId, (string) $targetTopicEntity->getId()); // Copy sandbox chat history $progressCallback && $progressCallback('running', 80, 'Copying chat history files'); $this->copyAiChatHistoryFile($sourceTopicEntity, $targetTopicEntity); $progressCallback && $progressCallback('running', 100, 'Topic message copy completed'); $this->logger->info('Topic messages copied successfully', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'target_topic_id' => $targetTopicEntity->getId(), ]); 
}
 
    private function copyTopicEntity( DataIsolation $dataIsolation, TopicEntity $sourceTopicEntity, string $chatConversationId, string $chatTopicId, string $newTopicName ): TopicEntity 
{
 $currentTime = date('Y-m-d H:i:s'); $topicEntity = new TopicEntity(); $topicEntity->setuser Id($sourceTopicEntity->getuser Id()); $topicEntity->setuser OrganizationCode($sourceTopicEntity->getuser OrganizationCode()); $topicEntity->setWorkspaceId($sourceTopicEntity->getWorkspaceId()); $topicEntity->setProjectId($sourceTopicEntity->getProjectId()); $topicEntity->setChatTopicId($chatTopicId); $topicEntity->setChatConversationId($chatConversationId); $topicEntity->setTopicName($newTopicName); $topicEntity->setTopicMode($sourceTopicEntity->getTopicMode()); $topicEntity->setSandboxId(''); $topicEntity->setSourceId((string) $sourceTopicEntity->getId()); // Initially empty $topicEntity->setSource(CreationSource::COPY->value); $topicEntity->setWorkDir($sourceTopicEntity->getWorkDir()); $topicEntity->setcurrent TaskId(0); $topicEntity->setcurrent TaskStatus(TaskStatus::WAITING); // Default status: waiting $topicEntity->setCreatedUid($dataIsolation->getcurrent user Id()); // Set creator user ID $topicEntity->setUpdatedUid($dataIsolation->getcurrent user Id()); // Set updater user ID $topicEntity->setCreatedAt($currentTime); $topicEntity->setFromTopicId($sourceTopicEntity->getId()); return $this->topicRepository->createTopic($topicEntity); 
}
 
    private function copyTopicShareMessages(int $messageId, TopicEntity $sourceTopicEntity, TopicEntity $targetTopicEntity): array 
{
 $this->logger->info('Starting to copy topic share messages', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'target_topic_id' => $targetTopicEntity->getId(), 'message_id' => $messageId, ]); // query need Data $messagesToCopy = $this->taskMessageRepository->findMessagesToCopyByTopicIdAndMessageId( $sourceTopicEntity->getId(), $messageId ); if (empty($messagesToCopy)) 
{
 $this->logger->info('No messages found to copy', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'message_id' => $messageId, ]); return []; 
}
 $this->logger->info('Found messages to copy', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'target_topic_id' => $targetTopicEntity->getId(), 'message_count' => count($messagesToCopy), ]); // Atprocess Messagebefore need EnsureMessageHaveTaskRowAssociationneed Task $taskIds = []; foreach ($messagesToCopy as $messageToCopy) 
{
 if (! in_array($messageToCopy->getTaskId(), $taskIds)) 
{
 $taskIds[] = $messageToCopy->getTaskId(); 
}
 
}
 // $taskIdMapping = $this->copyTopicTaskEntity($sourceTopicEntity->getId(), $targetTopicEntity->getId(), $taskIds); $newMessageEntities = []; $messageIdMapping = []; // Map relationship of old message ID to new message ID foreach ($messagesToCopy as $messageToCopy) 
{
 $newMessageEntity = new TaskMessageEntity(); // CopyMessagePropertyUpdateas Newtopic ID $newMessageEntity->setSenderType($messageToCopy->getSenderType()); $newMessageEntity->setTopicId($targetTopicEntity->getId()); // Set as new topic ID $newMessageEntity->setSenderUid($messageToCopy->getSenderUid()); $newMessageEntity->setReceiverUid($messageToCopy->getReceiverUid()); $newMessageEntity->setMessageId($messageToCopy->getMessageId()); $newMessageEntity->setType($messageToCopy->getType()); $newMessageEntity->setTaskId(''); $newMessageEntity->setEvent($messageToCopy->getEvent()); $newMessageEntity->setStatus($messageToCopy->getStatus()); $newMessageEntity->setSteps($messageToCopy->getSteps()); $newMessageEntity->settool ($messageToCopy->gettool ()); $newMessageEntity->setAttachments($messageToCopy->getAttachments()); $newMessageEntity->setMentions($messageToCopy->getMentions()); $newMessageEntity->setRawData(''); $newMessageEntity->setContent($messageToCopy->getContent()); $newMessageEntity->setSeqId($messageToCopy->getSeqId()); $newMessageEntity->setprocess ingStatus($messageToCopy->getprocess ingStatus()); $newMessageEntity->setErrorMessage($messageToCopy->getErrorMessage()); $newMessageEntity->setRetryCount($messageToCopy->getRetryCount()); $newMessageEntity->setprocess edAt($messageToCopy->getprocess edAt()); $newMessageEntity->setShowInUi($messageToCopy->getShowInUi()); $newMessageEntity->setRawContent($messageToCopy->getRawContent()); $newMessageEntities[] = $newMessageEntity; // MapRelationshipOldMessageID => NewMessageID $messageIdMapping[$messageToCopy->getId()] = (string) $newMessageEntity->getId(); 
}
 // BatchInsertNewtopic in $createdMessageEntities = $this->taskMessageRepository->batchCreateMessages($newMessageEntities); $this->logger->debug('Successfully copied topic share messages', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'target_topic_id' => $targetTopicEntity->getId(), 'copied_count' => count($createdMessageEntities), 'message_id_mapping' => $messageIdMapping, ]); return $messageIdMapping; 
}
 
    private function initImConversationFromTopic(TopicEntity $sourceTopicEntity, string $topicName = ''): array 
{
 $this->logger->info('StartFromtopic InitializeIMSession', [ 'source_topic_id' => $sourceTopicEntity->getId(), 'chat_topic_id' => $sourceTopicEntity->getChatTopicId(), 'chat_conversation_id' => $sourceTopicEntity->getChatConversationId(), ]); // 1. query magic_chat_topics table by chat_topic_id to get all related records $existingTopics = $this->magicChatTopicRepository->getTopicsByTopicId($sourceTopicEntity->getChatTopicId()); if (count($existingTopics) !== 2) 
{
 ExceptionBuilder::throw( SuperAgentErrorCode::TOPIC_NOT_FOUND, trans('super_agent.topic.im_topic_not_found') ); 
}
 // 2. Generate Newtopic ID $newTopicId = (string) IdGenerator::getSnowId(); $aiConversationId = ''; $userConversationId = ''; // 3. Atloop in CertainRoledirectly Createrecord foreach ($existingTopics as $topic) 
{
 $newTopicEntity = new MagicTopicEntity(); $newTopicEntity->setTopicId($newTopicId); $newTopicEntity->setConversationId($topic->getConversationId()); $newTopicEntity->setName(! empty($topicName) ? $topicName : $sourceTopicEntity->getTopicName()); $newTopicEntity->setDescription($topic->getDescription()); $newTopicEntity->setOrganizationCode($topic->getOrganizationCode()); // SaveNewtopic record $this->magicChatTopicRepository->createTopic($newTopicEntity); // CertainAIuser SessionID if ($topic->getConversationId() === $sourceTopicEntity->getChatConversationId()) 
{
 $userConversationId = $topic->getConversationId(); 
}
 else 
{
 $aiConversationId = $topic->getConversationId(); 
}
 
}
 // Validate SessionID if (empty($aiConversationId) || empty($userConversationId)) 
{
 ExceptionBuilder::throw( SuperAgentErrorCode::TOPIC_NOT_FOUND, trans('super_agent.topic.conversation_mismatch') ); 
}
 $result = [ 'ai_conversation_id' => $aiConversationId, 'user_conversation_id' => $userConversationId, 'old_topic_id' => $sourceTopicEntity->getChatTopicId(), 'new_topic_id' => $newTopicId, ]; $this->logger->info('IMSessionInitializecomplete ', $result); return $result; 
}
 
    private function copyImMessages(array $imConversationResult, array $messageIdMapping, int $userSeqId, int $aiSeqId, string $newTopicId): array 
{
 $this->logger->info('StartCopyIMMessage', [ 'user_seq_id' => $userSeqId, 'ai_seq_id' => $aiSeqId, 'im_conversation_result' => $imConversationResult, 'new_topic_id' => $newTopicId, ]); // process magic_chat_topic_messages table // 1. query user topic messages $userTopicMessages = $this->magicChatTopicRepository->getTopicMessagesBySeqId( $imConversationResult['user_conversation_id'], $imConversationResult['old_topic_id'], $userSeqId ); // 2. query AItopic messages $aiTopicMessages = $this->magicChatTopicRepository->getTopicMessagesBySeqId( $imConversationResult['ai_conversation_id'], $imConversationResult['old_topic_id'], $aiSeqId ); $this->logger->info('query IMMessage', [ 'user_messages_count' => count($userTopicMessages), 'ai_messages_count' => count($aiTopicMessages), ]); // 3. BatchInsertData $batchInsertData = []; $userSeqIds = []; $aiSeqIds = []; $seqIdsMap = []; $currentTime = date('Y-m-d H:i:s'); // process user Message foreach ($userTopicMessages as $userMessage) 
{
 $newSeqId = (string) IdGenerator::getSnowId(); $seqIdsMap[$userMessage->getSeqId()] = $newSeqId; $userSeqIds[] = $userMessage->getSeqId(); $batchInsertData[] = [ 'seq_id' => $newSeqId, 'conversation_id' => $imConversationResult['user_conversation_id'], 'topic_id' => $imConversationResult['new_topic_id'], 'organization_code' => $userMessage->getOrganizationCode(), 'created_at' => $currentTime, 'updated_at' => $currentTime, ]; 
}
 // process AIMessage foreach ($aiTopicMessages as $aiMessage) 
{
 $newSeqId = (string) IdGenerator::getSnowId(); $seqIdsMap[$aiMessage->getSeqId()] = $newSeqId; $aiSeqIds[] = $aiMessage->getSeqId(); $batchInsertData[] = [ 'seq_id' => $newSeqId, 'conversation_id' => $imConversationResult['ai_conversation_id'], 'topic_id' => $imConversationResult['new_topic_id'], 'organization_code' => $aiMessage->getOrganizationCode(), 'created_at' => $currentTime, 'updated_at' => $currentTime, ]; 
}
 // 4. BatchInsertMessage $insertResult = false; if (! empty($batchInsertData)) 
{
 $insertResult = $this->magicChatTopicRepository->createTopicMessages($batchInsertData); 
}
 // 5. process magic_chat_sequences table $magicMessageIdMapping = []; $batchSeqInsertData = []; // 5.1 query user sequences $userSequences = $this->magicSeqRepository->getSequencesByConversationIdAndSeqIds( $imConversationResult['user_conversation_id'], $userSeqIds ); // 5.2 query AI sequences $aiSequences = $this->magicSeqRepository->getSequencesByConversationIdAndSeqIds( $imConversationResult['ai_conversation_id'], $aiSeqIds ); $this->logger->info('query SeqMessage', [ 'user_sequences_count' => count($userSequences), 'ai_sequences_count' => count($aiSequences), ]); // 5.3 process user sequences foreach ($userSequences as $userSeq) 
{
 $originalSeqId = $userSeq->getId(); $newSeqId = $seqIdsMap[$originalSeqId] ?? null; if (! $newSeqId) 
{
 continue; 
}
 // Generate or Get magic_message_id Map $originalMagicMessageId = $userSeq->getMagicMessageId(); if (! isset($magicMessageIdMapping[$originalMagicMessageId])) 
{
 $magicMessageIdMapping[$originalMagicMessageId] = IdGenerator::getUniqueId32(); 
}
 $newMagicMessageId = $magicMessageIdMapping[$originalMagicMessageId]; // process extra in topic_id Replace $extra = $userSeq->getExtra(); if ($extra && $extra->getTopicId()) 
{
 $extraData = $extra->toArray(); $extraData['topic_id'] = $imConversationResult['new_topic_id']; $newExtra = new SeqExtra($extraData); 
}
 else 
{
 $newExtra = $extra; 
}
 // Get sender_message_id $senderMessageId = $seqIdsMap[$userSeq->getSenderMessageId()] ?? ''; // process app_message_id - Using messageIdMapping Map $originalAppMessageId = $userSeq->getAppMessageId(); $appMessageId = ! empty($messageIdMapping[$originalAppMessageId]) ? $messageIdMapping[$originalAppMessageId] : (string) IdGenerator::getSnowId(); // create new sequence $newuser Seq = new MagicSeqEntity(); $newuser Seq->setId($newSeqId); $newuser Seq->setOrganizationCode($userSeq->getOrganizationCode()); $newuser Seq->setObjectType($userSeq->getObjectType()); $newuser Seq->setObjectId($userSeq->getObjectId()); $newuser Seq->setSeqId($newSeqId); $newuser Seq->setSeqType($userSeq->getSeqType()); $newuser Seq->setContent($userSeq->getContent()); $newuser Seq->setMagicMessageId($newMagicMessageId); $newuser Seq->setMessageId($newSeqId); $newuser Seq->setReferMessageId($userSeq->getMessageId()); $newuser Seq->setSenderMessageId($senderMessageId); $newuser Seq->setConversationId($imConversationResult['user_conversation_id']); $newuser Seq->setStatus($userSeq->getStatus()); $newuser Seq->setReceivelist ($userSeq->getReceivelist ()); $newuser Seq->setExtra($newExtra); $newuser Seq->setAppMessageId($appMessageId); $newuser Seq->setCreatedAt($currentTime); $newuser Seq->setUpdatedAt($currentTime); $batchSeqInsertData[] = $newuser Seq; 
}
 // 5.4 process AI sequences foreach ($aiSequences as $aiSeq) 
{
 $originalSeqId = $aiSeq->getId(); $newSeqId = $seqIdsMap[$originalSeqId] ?? null; if (! $newSeqId) 
{
 continue; 
}
 // Generate or Get magic_message_id Map $originalMagicMessageId = $aiSeq->getMagicMessageId(); if (! isset($magicMessageIdMapping[$originalMagicMessageId])) 
{
 $magicMessageIdMapping[$originalMagicMessageId] = IdGenerator::getUniqueId32(); 
}
 $newMagicMessageId = $magicMessageIdMapping[$originalMagicMessageId]; // process extra in topic_id Replace $extra = $aiSeq->getExtra(); if ($extra && $extra->getTopicId()) 
{
 $extraData = $extra->toArray(); $extraData['topic_id'] = $imConversationResult['new_topic_id']; $newExtra = new SeqExtra($extraData); 
}
 else 
{
 $newExtra = $extra; 
}
 // Get sender_message_id $senderMessageId = $seqIdsMap[$aiSeq->getSenderMessageId()] ?? ''; // process app_message_id - Using messageIdMapping Map $originalAppMessageId = $aiSeq->getAppMessageId(); $appMessageId = $messageIdMapping[$originalAppMessageId] ?? ''; // create new sequence $newAiSeq = new MagicSeqEntity(); $newAiSeq->setId($newSeqId); $newAiSeq->setOrganizationCode($aiSeq->getOrganizationCode()); $newAiSeq->setObjectType($aiSeq->getObjectType()); $newAiSeq->setObjectId($aiSeq->getObjectId()); $newAiSeq->setSeqId($newSeqId); $newAiSeq->setSeqType($aiSeq->getSeqType()); $newAiSeq->setContent($aiSeq->getContent()); $newAiSeq->setMagicMessageId($newMagicMessageId); $newAiSeq->setMessageId($newSeqId); $newAiSeq->setReferMessageId(''); $newAiSeq->setSenderMessageId($senderMessageId); $newAiSeq->setConversationId($imConversationResult['ai_conversation_id']); $newAiSeq->setStatus($aiSeq->getStatus()); $newAiSeq->setReceivelist ($aiSeq->getReceivelist ()); $newAiSeq->setExtra($newExtra); $newAiSeq->setAppMessageId($appMessageId); $newAiSeq->setCreatedAt($currentTime); $newAiSeq->setUpdatedAt($currentTime); $batchSeqInsertData[] = $newAiSeq; 
}
 // 5.5 BatchInsert sequences $seqInsertResult = []; if (! empty($batchSeqInsertData)) 
{
 $seqInsertResult = $this->magicSeqRepository->batchCreateSeq($batchSeqInsertData); 
}
 // 6. process magic_chat_messages table // 6.1 $magicMessageIdMapping key Array $originalMagicMessageIds $originalMagicMessageIds = array_keys($magicMessageIdMapping); // 6.2 query magic_chat_messages record $originalMessages = $this->magicMessageRepository->getMessagesByMagicMessageIds($originalMagicMessageIds); $this->logger->info('query original Messages', [ 'original_message_ids_count' => count($originalMagicMessageIds), 'found_messages_count' => count($originalMessages), ]); // 6.3 Generate New magic_chat_messages record // directly Using messageIdMappingStructure: [old_app_message_id] = new_message_id $batchMessageInsertData = []; foreach ($originalMessages as $originalMessage) 
{
 $originalMagicMessageId = $originalMessage->getMagicMessageId(); $newMagicMessageId = $magicMessageIdMapping[$originalMagicMessageId] ?? null; if (! $newMagicMessageId) 
{
 continue; 
}
 // Get original content array $contentArray = $originalMessage->getContent()->toArray(); // For SuperAgentCard type, directly replace fields with fixed structure if ($originalMessage->getMessageType() === ChatMessageType::SuperAgentCard) 
{
 $contentArray['message_id'] = $messageIdMapping[$contentArray['message_id']] ?? ''; $contentArray['topic_id'] = $newTopicId; $contentArray['task_id'] = ''; 
}
 $batchMessageInsertData[] = [ 'sender_id' => $originalMessage->getSenderId(), 'sender_type' => $originalMessage->getSenderType(), 'sender_organization_code' => $originalMessage->getSenderOrganizationCode(), 'receive_id' => $originalMessage->getReceiveId(), 'receive_type' => $originalMessage->getReceiveType(), 'receive_organization_code' => $originalMessage->getReceiveOrganizationCode(), 'message_type' => $originalMessage->getMessageType(), 'content' => json_encode($contentArray, JSON_UNESCAPED_UNICODE), 'language' => $originalMessage->getLanguage(), 'app_message_id' => $messageIdMapping[$originalMessage->getAppMessageId()] ?? '', 'magic_message_id' => $newMagicMessageId, 'send_time' => $originalMessage->getSendTime(), 'current_version_id' => null, 'created_at' => $currentTime, 'updated_at' => $currentTime, ]; 
}
 // 6.5 BatchInsert magic_chat_messages $messageInsertResult = false; if (! empty($batchMessageInsertData)) 
{
 $messageInsertResult = $this->magicMessageRepository->batchCreateMessages($batchMessageInsertData); 
}
 $result = [ 'user_messages_count' => count($userTopicMessages), 'ai_messages_count' => count($aiTopicMessages), 'total_topic_messages_copied' => count($batchInsertData), 'topic_messages_insert_success' => $insertResult, 'user_sequences_count' => count($userSequences), 'ai_sequences_count' => count($aiSequences), 'total_sequences_copied' => count($batchSeqInsertData), 'sequences_insert_success' => ! empty($seqInsertResult), 'magic_message_id_mappings' => count($magicMessageIdMapping), 'original_messages_found' => count($originalMessages), 'total_messages_copied' => count($batchMessageInsertData), 'messages_insert_success' => $messageInsertResult, 'app_message_id_mappings' => count($messageIdMapping), ]; $this->logger->info('IMMessageCopycomplete ', $result); return $result; 
}
 
    private function getSeqIdByMessageId(string $messageId): array 
{
 // Through app_message_id message_type query magic_chat_messages Get magic_message_id $magicMessageId = $this->magicMessageRepository->getMagicMessageIdByAppMessageId($messageId); // Through magic_message_id query magic_chat_sequences Get seq_id $seqlist = $this->magicSeqRepository->getBothSeqlist ByMagicMessageId($magicMessageId); $result = []; foreach ($seqlist as $seq) 
{
 if ($seq['object_type'] === user Type::Ai->value) 
{
 $result['ai_seq_id'] = $seq['seq_id']; 
}
 elseif ($seq['object_type'] === user Type::Human->value) 
{
 $result['user_seq_id'] = $seq['seq_id']; 
}
 
}
 return $result; 
}
 
    private function copyAiChatHistoryFile(TopicEntity $sourceTopicEntity, TopicEntity $targetTopicEntity) 
{
 $sourcePath = WorkDirectoryUtil::getAgentChatHistoryFilePath($sourceTopicEntity->getuser Id(), $sourceTopicEntity->getProjectId(), $sourceTopicEntity->getId()); $targetPath = WorkDirectoryUtil::getAgentChatHistoryFilePath($targetTopicEntity->getuser Id(), $targetTopicEntity->getProjectId(), $targetTopicEntity->getId()); $prefix = $this->cloudFileRepository->getFullPrefix($sourceTopicEntity->getuser OrganizationCode()); try 
{
 $sourceKey = rtrim($prefix, '/') . '/' . ltrim($sourcePath, '/'); $destinationKey = rtrim($prefix, '/') . '/' . ltrim($targetPath, '/'); $this->cloudFileRepository->copyObjectByCredential( prefix: '/', organizationCode: $sourceTopicEntity->getuser OrganizationCode(), sourceKey: $sourceKey, destinationKey: $destinationKey, bucketType: StorageBucketType::SandBox ); 
}
 catch (Throwable $e) 
{
 $this->logger->error('CopyIMMessageFileFailed', [ 'error_message' => $e->getMessage(), 'source_path' => $sourcePath, 'target_path' => $targetPath, ]); 
}
 
}
 
}
 

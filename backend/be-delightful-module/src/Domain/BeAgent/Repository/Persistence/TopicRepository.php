<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence;

use App\Domain\Chat\Entity\ValueObject\MagicMessageStatus;
use App\Domain\Chat\Repository\Persistence\Model\MagicChatSequenceModel;
use App\Domain\Chat\Repository\Persistence\Model\MagicChatTopicMessageModel;
use App\Domain\Chat\Repository\Persistence\Model\MagicMessageModel;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TopicRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TaskMessageModel;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TopicModel;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\WorkspaceModel;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class TopicRepository implements TopicRepositoryInterface 
{
 
    private LoggerInterface $logger; 
    public function __construct( 
    protected TopicModel $model, 
    protected MagicChatSequenceModel $magicChatSequenceModel, 
    protected MagicChatTopicMessageModel $magicChatTopicMessageModel, 
    protected MagicMessageModel $magicMessageModel, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(static::class); 
}
 
    public function getTopicById(int $id): ?TopicEntity 
{
 // id query $model = $this->model::query()->whereNull('deleted_at') ->where('id', $id) ->first(); if ($model) 
{
 $data = $this->convertModelToEntityData($model->toArray()); return new TopicEntity($data); 
}
 // If id  chat_topic_id query $model = $this->model::query()->whereNull('deleted_at') ->where('chat_topic_id', $id) ->first(); if ($model) 
{
 // chat_topic_id Datarecord ErrorLog trace $this->logger->error('TopicRepository getTopicById queried data by chat_topic_id, possible data inconsistency issue', [ 'search_id' => $id, 'found_topic_id' => $model->id, 'found_chat_topic_id' => $model->chat_topic_id, 'trace' => (new Exception())->getTraceAsString(), ]); $data = $this->convertModelToEntityData($model->toArray()); return new TopicEntity($data); 
}
 return null; 
}
 
    public function getTopicsByIds(array $ids): array 
{
 if (empty($ids)) 
{
 return []; 
}
 $models = $this->model::query()->whereNull('deleted_at')->whereIn('id', $ids)->get(); $entities = []; foreach ($models as $model) 
{
 $data = $this->convertModelToEntityData($model->toArray()); $entities[] = new TopicEntity($data); 
}
 return $entities; 
}
 
    public function getTopicWithdelete d(int $id): ?TopicEntity 
{
 $model = $this->model::query()->withTrashed()->find($id); if (! $model) 
{
 return null; 
}
 $data = $this->convertModelToEntityData($model->toArray()); return new TopicEntity($data); 
}
 
    public function getTopicBySandboxId(string $sandboxId): ?TopicEntity 
{
 $model = $this->model::query()->whereNull('deleted_at')->where('sandbox_id', $sandboxId)->first(); if (! $model) 
{
 return null; 
}
 $data = $this->convertModelToEntityData($model->toArray()); return new TopicEntity($data); 
}
 /** * According toConditionGettopic list . * SupportFilterPagingSort. * * @param array $conditions query Condition ['workspace_id' => 1, 'user_id' => 'xxx'] * @param bool $needPagination whether need Paging * @param int $pageSize PagingSize * @param int $page Page number * @param string $orderBy SortField * @param string $orderDirection Sortasc or desc * @return array
{
list: TopicEntity[], total: int
}
 topic list Total */ 
    public function getTopicsByConditions( array $conditions = [], bool $needPagination = true, int $pageSize = 10, int $page = 1, string $orderBy = 'id', string $orderDirection = 'desc' ): array 
{
 // BuildBasequery $query = $this->model::query(); // DefaultFilterdelete dData $query->whereNull('deleted_at'); // ApplyConditionFilter foreach ($conditions as $field => $value) 
{
 if (is_array($value)) 
{
 $query->whereIn($field, $value); 
}
 elseif ($field === 'topic_name') 
{
 // topic_name FieldUsing like RowVagueMatch $query->where($field, 'like', '%' . $value . '%'); 
}
 else 
{
 $query->where($field, $value); 
}
 
}
 // GetTotal $total = $query->count(); // ApplySort $query->orderBy($orderBy, $orderDirection); // ApplyPaging if ($needPagination) 
{
 $offset = ($page - 1) * $pageSize; $query->skip($offset)->take($pageSize); 
}
 // GetData $topics = Db::select($query->toSql(), $query->getBindings()); // Convert toObject $list = []; foreach ($topics as $topic) 
{
 $list[] = new TopicEntity($topic); 
}
 return [ 'list' => $list, 'total' => $total, ]; 
}
 
    public function createTopic(TopicEntity $topicEntity): TopicEntity 
{
 $date = date('Y-m-d H:i:s'); $topicEntity->setId(IdGenerator::getSnowId()); $topicEntity->setCreatedAt($date); $topicEntity->setUpdatedAt($date); $entityArray = $topicEntity->toArray(); $model = $this->model::query()->create($entityArray); /* @var TopicModel $model */ $topicEntity->setId($model->id); return $topicEntity; 
}
 
    public function updateTopic(TopicEntity $topicEntity): bool 
{
 $topicEntity->setUpdatedAt(date('Y-m-d H:i:s')); $entityArray = $topicEntity->toArray(); return $this->model::query() ->where('id', $topicEntity->getId()) ->update($entityArray) > 0; 
}
 // Usingupdated_at as Lock 
    public function updateTopicWithUpdatedAt(TopicEntity $topicEntity, string $updatedAt): bool 
{
 $topicEntity->setUpdatedAt(date('Y-m-d H:i:s')); $entityArray = $topicEntity->toArray(); return $this->model::query() ->where('id', $topicEntity->getId()) ->where('updated_at', $updatedAt) ->update($entityArray) > 0; 
}
 
    public function updateTopicByCondition(array $condition, array $data): bool 
{
 return $this->model::query() ->where($condition) ->update($data) > 0; 
}
 
    public function deleteTopic(int $id): bool 
{
 return $this->model::query() ->where('id', $id) ->update([ 'deleted_at' => date('Y-m-d H:i:s'), ]) > 0; 
}
 /** * Throughtopic IDCollectionGetworkspace info . * * @param array $topicIds topic IDCollection * @return array topic IDas Keyworkspace info as ValueAssociationArrayFormat['topic ID' => ['workspace_id' => 'workspace ID', 'workspace_name' => 'workspace Name']] */ 
    public function getWorkspaceinfo ByTopicIds(array $topicIds): array 
{
 if (empty($topicIds)) 
{
 return []; 
}
 // ConvertAllIDas Integer $topicIds = array_map('intval', $topicIds); // UsingSQLtable query  $sql = 'SELECT t.id as topic_id, w.id as workspace_id, w.name as workspace_name FROM ' . $this->model->gettable () . ' t JOIN ' . (new WorkspaceModel())->gettable () . ' w ON t.workspace_id = w.id WHERE t.id IN (' . implode(',', $topicIds) . ') AND t.deleted_at IS NULL AND w.deleted_at IS NULL'; $results = Db::select($sql); // Resultas topic IDas KeyAssociationArray $workspaceinfo = []; foreach ($results as $row) 
{
 $workspaceinfo [$row['topic_id']] = [ 'workspace_id' => (string) $row['workspace_id'], 'workspace_name' => $row['workspace_name'], ]; 
}
 return $workspaceinfo ; 
}
 /** * Gettopic StatusCountData. * * @param array $conditions CountCondition ['user_id' => '123', 'organization_code' => 'abc'] * @return array including StatusQuantityArray */ 
    public function getTopicStatusMetrics(array $conditions = []): array 
{
 // UsingSQLquery StatusGroupGetCount $basequery = $this->model::query(); // process FilterCondition foreach ($conditions as $field => $value) 
{
 if (is_array($value)) 
{
 $basequery ->whereIn($field, $value); 
}
 else 
{
 $basequery ->where($field, $value); 
}
 
}
 // DefaultFilterdelete dData $basequery ->whereNull('deleted_at'); // Countuser $userCount = $basequery ->distinct()->count('user_id'); // Counttopic Total $topicCount = $basequery ->count(); // CountStatustopic Quantity $statusCounts = $basequery ->selectRaw('current_task_status, COUNT(*) as count') ->groupBy('current_task_status') ->get() ->keyBy('current_task_status') ->map(function ($item) 
{
 return (int) $item->count; 
}
) ->toArray(); // Return Result return [ 'status_metrics' => [ 'error_count' => $statusCounts['error'] ?? 0, 'completed_count' => $statusCounts['finished'] ?? 0, 'running_count' => $statusCounts['running'] ?? 0, 'waiting_count' => $statusCounts['waiting'] ?? 0, 'paused_count' => ($statusCounts['suspended'] ?? 0) + ($statusCounts['stopped'] ?? 0), ], 'total_metrics' => [ 'user_count' => $userCount, 'topic_count' => $topicCount, ], ]; 
}
 
    public function updateTopicStatus(int $id, $taskId, TaskStatus $status): bool 
{
 return $this->model::query() ->where('id', $id) ->update([ 'current_task_id' => $taskId, 'current_task_status' => $status->value, 'updated_at' => date('Y-m-d H:i:s'), ]) > 0; 
}
 
    public function updateTopicStatusAndSandboxId(int $id, $taskId, TaskStatus $status, string $sandboxId): bool 
{
 return $this->model::query() ->where('id', $id) ->update([ 'current_task_id' => $taskId, 'current_task_status' => $status->value, 'sandbox_id' => $sandboxId, 'updated_at' => date('Y-m-d H:i:s'), ]) > 0; 
}
 /** * Getmost recently Update timespecified Timetopic list . * * @param string $timeThreshold TimeThresholdIftopic Update timeTimeincluding AtResultin * @param int $limit Return ResultMaximumQuantity * @return array<TopicEntity> topic list */ 
    public function getTopicsExceedingUpdateTime(string $timeThreshold, int $limit = 100): array 
{
 $models = $this->model::query() ->where('updated_at', '<', $timeThreshold) ->where('current_task_status', TaskStatus::RUNNING->value) ->whereNull('deleted_at') ->orderBy('id', 'asc') ->limit($limit) ->get(); $result = []; foreach ($models as $model) 
{
 $data = $this->convertModelToEntityData($model->toArray()); $result[] = new TopicEntity($data); 
}
 return $result; 
}
 
    public function updateTopicStatusBySandboxIds(array $sandboxIds, string $status): bool 
{
 return $this->model::query() ->whereIn('sandbox_id', $sandboxIds) ->update([ 'current_task_status' => $status, 'updated_at' => date('Y-m-d H:i:s'), ]) > 0; 
}
 /** * According toProject IDGettopic list . */ 
    public function getTopicsByProjectId(int $projectId, string $userId): array 
{
 $models = $this->model::query() ->where('project_id', $projectId) ->where('user_id', $userId) ->whereNull('deleted_at') ->orderBy('updated_at', 'desc') ->get(); $result = []; foreach ($models as $model) 
{
 $data = $this->convertModelToEntityData($model->toArray()); $result[] = new TopicEntity($data); 
}
 return $result; 
}
 /** * CountItemunder topic Quantity. */ 
    public function countTopicsByProjectId(int $projectId): int 
{
 return $this->model::query() ->where('project_id', $projectId) ->whereNull('deleted_at') ->count(); 
}
 /** * BatchGetHaveRunningtopic workspace IDlist . * * @param array $workspaceIds workspace IDArray * @param null|string $userId Optionaluser IDScheduledquery user topic * @return array HaveRunningtopic workspace IDArray */ 
    public function getRunningWorkspaceIds(array $workspaceIds, ?string $userId = null): array 
{
 if (empty($workspaceIds)) 
{
 return []; 
}
 $query = $this->model::query() ->whereIn('workspace_id', $workspaceIds) ->where('current_task_status', TaskStatus::RUNNING->value) ->whereNull('deleted_at'); if ($userId !== null) 
{
 $query->where('user_id', $userId); 
}
 return $query ->distinct() ->pluck('workspace_id') ->toArray(); 
}
 /** * BatchGetHaveRunningtopic Project IDlist . * * @param array $projectIds Project IDArray * @param null|string $userId Optionaluser IDScheduledquery user topic * @return array HaveRunningtopic Project IDArray */ 
    public function getRunningProjectIds(array $projectIds, ?string $userId = null): array 
{
 if (empty($projectIds)) 
{
 return []; 
}
 $query = $this->model::query() ->whereIn('project_id', $projectIds) ->where('current_task_status', TaskStatus::RUNNING->value) ->whereNull('deleted_at'); if ($userId !== null) 
{
 $query->where('user_id', $userId); 
}
 return $query ->distinct() ->pluck('project_id') ->toArray(); 
}
 // ======================= MessageRollbackrelated MethodImplementation ======================= /** * According toColumnIDGetmagic_message_id. */ 
    public function getMagicMessageIdBySeqId(string $seqId): ?string 
{
 $result = $this->magicChatSequenceModel::query() ->where('id', $seqId) ->value('magic_message_id'); return $result ?: null; 
}
 /** * According tomagic_message_idGetAllrelated seq_idAll. */ 
    public function getAllSeqIdsByMagicMessageId(string $magicMessageId): array 
{
 // Return Allrelated seq_id return $this->magicChatSequenceModel::query() ->where('magic_message_id', $magicMessageId) ->pluck('id') ->toArray(); 
}
 /** * According toBaseseq_idsGetcurrent topic current MessageMessageAllMessage. */ 
    public function getAllSeqIdsFromcurrent (array $baseSeqIds): array 
{
 if (empty($baseSeqIds)) 
{
 return []; 
}
 // Batchquery AllbaseSeqIdscorresponding conversation_idtopic_id $topicinfo s = $this->magicChatTopicMessageModel::query() ->select(['seq_id', 'conversation_id', 'topic_id']) ->whereIn('seq_id', $baseSeqIds) ->get() ->keyBy('seq_id'); if ($topicinfo s->isEmpty()) 
{
 return []; 
}
 $allSeqIds = []; // query topicinfo Geteach topic greater than or equal to seq_idAllMessage foreach ($topicinfo s as $topicinfo ) 
{
 // query topic greater than or equal to seq_idAllMessageincluding current MessageMessage $seqIds = $this->magicChatTopicMessageModel::query() ->where('conversation_id', $topicinfo ->conversation_id) ->where('topic_id', $topicinfo ->topic_id) ->where('seq_id', '>=', $topicinfo ->seq_id) ->pluck('seq_id') ->toArray(); $allSeqIds[] = $seqIds; 
}
 ! empty($allSeqIds) && $allSeqIds = array_merge(...$allSeqIds); return array_values(array_unique($allSeqIds)); 
}
 /** * delete topic_messagesData. */ 
    public function deleteTopicMessages(array $seqIds): int 
{
 if (empty($seqIds)) 
{
 return 0; 
}
 return $this->magicChatTopicMessageModel::query() ->whereIn('seq_id', $seqIds) ->delete(); 
}
 /** * According toseq_idsdelete messagessequencesData. */ 
    public function deleteMessagesAndSequencesBySeqIds(array $seqIds): bool 
{
 if (empty($seqIds)) 
{
 return true; 
}
 // GetAllrelated magic_message_ids $magicMessageIds = $this->magicChatSequenceModel::query() ->whereIn('id', $seqIds) ->distinct() ->pluck('magic_message_id') ->toArray(); // delete magic_chat_messages if (! empty($magicMessageIds)) 
{
 $this->magicMessageModel::query() ->whereIn('magic_message_id', $magicMessageIds) ->delete(); 
}
 // delete magic_chat_sequences $this->magicChatSequenceModel::query() ->whereIn('id', $seqIds) ->delete(); return true; 
}
 /** * According toim_seq_iddelete magic_super_agent_messagetable in Pairtopic Message. */ 
    public function deleteSuperAgentMessagesFromSeqId(int $seqId): int 
{
 // 1. query corresponding message record by seq_id $targetMessage = TaskMessageModel::query() ->where('im_seq_id', $seqId) ->first(['id', 'topic_id']); if (! $targetMessage) 
{
 return 0; 
}
 $messageId = (int) $targetMessage->id; $topicId = (int) $targetMessage->topic_id; // 2. delete current topic in id >= messageId all data return TaskMessageModel::query() ->where('topic_id', $topicId) ->where('id', '>=', $messageId) ->delete(); 
}
 /** * BatchUpdatemagic_chat_sequencestable statusField. */ 
    public function batchUpdateSeqStatus(array $seqIds, MagicMessageStatus $status): bool 
{
 if (empty($seqIds)) 
{
 return true; 
}
 return (bool) $this->magicChatSequenceModel::query() ->whereIn('id', $seqIds) ->update(['status' => $status->value]); 
}
 /** * According toBaseseq_idsGetcurrent topic in Less thanspecified seq_idAllMessage. */ 
    public function getAllSeqIdsBeforecurrent (array $baseSeqIds): array 
{
 if (empty($baseSeqIds)) 
{
 return []; 
}
 // Batchquery AllbaseSeqIdscorresponding conversation_idtopic_id $topicinfo s = $this->magicChatTopicMessageModel::query() ->select(['seq_id', 'conversation_id', 'topic_id']) ->whereIn('seq_id', $baseSeqIds) ->get() ->keyBy('seq_id'); if ($topicinfo s->isEmpty()) 
{
 return []; 
}
 $allSeqIds = []; // query topicinfo Geteach topic Less thanseq_idAllMessage foreach ($topicinfo s as $topicinfo ) 
{
 // query topic Less thanseq_idAllMessage $seqIds = $this->magicChatTopicMessageModel::query() ->where('conversation_id', $topicinfo ->conversation_id) ->where('topic_id', $topicinfo ->topic_id) ->where('seq_id', '<', $topicinfo ->seq_id) ->pluck('seq_id') ->toArray(); $allSeqIds[] = $seqIds; 
}
 ! empty($allSeqIds) && $allSeqIds = array_merge(...$allSeqIds); return array_values(array_unique($allSeqIds)); 
}
 /** * According totopic IDGetAllrecalled status Messageseq_ids. */ 
    public function getRevokedSeqIdsByTopicId(int $topicId, string $userId): array 
{
 // GetSuperAgenttopic $topic = $this->getTopicById($topicId); if (! $topic) 
{
 return []; 
}
 // Getcorresponding topic ID $chatTopicId = $topic->getChatTopicId(); if (empty($chatTopicId)) 
{
 return []; 
}
 // Usingtopic IDquery topic Allrecalled status Message return $this->magicChatTopicMessageModel::query() ->join('magic_chat_sequences', 'magic_chat_topic_messages.seq_id', '=', 'magic_chat_sequences.id') ->where('magic_chat_topic_messages.topic_id', $chatTopicId) ->where('magic_chat_sequences.status', MagicMessageStatus::Revoked->value) ->pluck('magic_chat_topic_messages.seq_id') ->toArray(); 
}
 /** * Batch get topic names by IDs. */ 
    public function getTopicNamesBatch(array $topicIds): array 
{
 if (empty($topicIds)) 
{
 return []; 
}
 $results = $this->model::query() ->whereIn('id', $topicIds) ->whereNull('deleted_at') ->select(['id', 'topic_name']) ->get(); $topicNames = []; foreach ($results as $result) 
{
 $topicNames[(string) $result->id] = $result->topic_name; 
}
 return $topicNames; 
}
 /** * DatabaseModelDataConvert toData. * @param array $modelData ModelData * @return array Data */ 
    private function convertModelToEntityData(array $modelData): array 
{
 // UnderlineConvert to $entityData = []; foreach ($modelData as $key => $value) 
{
 $camelKey = $this->snakeToCamel($key); $entityData[$camelKey] = $value; 
}
 return $entityData; 
}
 /** * UnderlineConvert to. * user_id => userId, topic_name => topicName. * * @param string $snake UnderlineString * @return string String */ 
    private function snakeToCamel(string $snake): string 
{
 // process CharacterUnderline return lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $snake)))); 
}
 
}
 

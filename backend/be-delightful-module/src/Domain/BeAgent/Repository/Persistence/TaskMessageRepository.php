<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Persistence;

use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Carbon\Carbon;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskMessageRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TaskMessageModel;
use Hyperf\DbConnection\Db;
use InvalidArgumentException;
use RuntimeException;

class TaskMessageRepository implements TaskMessageRepositoryInterface 
{
 
    public function __construct(
    protected TaskMessageModel $model) 
{
 
}
 
    public function getById(int $id): ?TaskMessageEntity 
{
 $record = $this->model::query()->find($id); if (! $record) 
{
 return null; 
}
 return new TaskMessageEntity($record->toArray()); 
}
 
    public function save(TaskMessageEntity $message): void 
{
 $this->model::query()->create($message->toArray()); 
}
 
    public function batchSave(array $messages): void 
{
 $data = array_map(function (TaskMessageEntity $message) 
{
 return $message->toArray(); 
}
, $messages); $this->model::query()->insert($data); 
}
 
    public function findByTaskId(string $taskId): array 
{
 $query = $this->model::query() ->where('task_id', $taskId) ->orderBy('send_timestamp', 'asc'); $result = Db::select($query->toSql(), $query->getBindings()); return array_map(function ($record) 
{
 return new TaskMessageEntity((array) $record); 
}
, $result); 
}
 /** * According totopic IDTaskIDGetuser Messagelist optimize Index+Filteruser Message. * @return TaskMessageEntity[] */ 
    public function finduser MessagesByTopicIdAndTaskId(int $topicId, string $taskId): array 
{
 $query = $this->model::query() ->where('topic_id', $topicId) ->where('task_id', $taskId) ->where('sender_type', 'user') ->orderBy('id'); $result = Db::select($query->toSql(), $query->getBindings()); return array_map(function ($record) 
{
 return new TaskMessageEntity((array) $record); 
}
, $result); 
}
 /** * According totopic IDGetMessagelist SupportPaging. * * @param int $topicId topic ID * @param int $page Page number * @param int $pageSize Per pageSize * @param bool $shouldPage whether need Paging * @param string $sortDirection SortSupportascdesc * @param bool $showInUi whether DisplayUIVisibleMessage * @return array Return including Messagelist TotalArray ['list' => TaskMessageEntity[], 'total' => int] */ 
    public function findByTopicId(int $topicId, int $page = 1, int $pageSize = 20, bool $shouldPage = true, string $sortDirection = 'asc', bool $showInUi = true): array 
{
 // EnsureSortyes Valid $sortDirection = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc'; // BuildBasequery - Using leftJoin casts Convert $query = $this->model::query() ->where('topic_id', $topicId); // If $showInUi as trueAddConditionFilter if ($showInUi) 
{
 $query->where('show_in_ui', true); 
}
 $query->orderBy('id', $sortDirection); // Getrecord $total = $query->count(); // Ifneed PagingAddPagingCondition if ($shouldPage) 
{
 $offset = ($page - 1) * $pageSize; $query->offset($offset)->limit($pageSize); 
}
 // execute query $records = $query->get(); // query ResultConvert toObject $messages = []; $imSeqIds = []; foreach ($records as $record) 
{
 // toArray() automatic Apply casts Convert $entity = new TaskMessageEntity($record->toArray()); $messages[] = $entity; // im_seq_id for Batchquery im_status if ($entity->getImSeqId() !== null) 
{
 $imSeqIds[$entity->getImSeqId()] = $entity->getImSeqId(); 
}
 
}
 // Batchquery im_status if (! empty($imSeqIds)) 
{
 $imStatusMap = Db::table('magic_chat_sequences') ->whereIn('id', array_values($imSeqIds)) ->pluck('status', 'id') ->toArray(); // im_status Set corresponding in foreach ($messages as $message) 
{
 if ($message->getImSeqId() !== null && isset($imStatusMap[$message->getImSeqId()])) 
{
 $message->setImStatus((int) $imStatusMap[$message->getImSeqId()]); 
}
 
}
 
}
 // Return StructureResult return [ 'list' => $messages, 'total' => $total, ]; 
}
 
    public function getuser FirstMessageByTopicId(int $topicId, string $userId): ?TaskMessageEntity 
{
 // BuildBasequery $query = $this->model::query() ->where('topic_id', $topicId) ->where('sender_type', 'user') ->where('sender_uid', $userId) ->orderBy('id', 'asc'); $record = $query->first(); if (! $record) 
{
 return null; 
}
 return new TaskMessageEntity($record->toArray()); 
}
 
    public function findPendingMessagesByTopicId(int $topicId, string $processingStatus, string $senderType = 'assistant', int $limit = 50): array 
{
 $query = $this->model::query() ->where('topic_id', $topicId) ->where('processing_status', $processingStatus) ->where('sender_type', $senderType) ->orderBy('seq_id', 'asc') ->limit($limit); $result = Db::select($query->toSql(), $query->getBindings()); return array_map(function ($record) 
{
 return new TaskMessageEntity((array) $record); 
}
, $result); 
}
 
    public function updateprocess ingStatus(int $id, string $processingStatus, ?string $errorMessage = null, int $retryCount = 0): void 
{
 $updateData = [ 'processing_status' => $processingStatus, 'retry_count' => $retryCount, 'updated_at' => Carbon::now(), ]; if ($errorMessage !== null) 
{
 $updateData['error_message'] = $errorMessage; 
}
 if ($processingStatus === TaskMessageModel::PROCESSING_STATUS_COMPLETED) 
{
 $updateData['processed_at'] = Carbon::now(); 
}
 $this->model::query()->where('id', $id)->update($updateData); 
}
 
    public function batchUpdateprocess ingStatus(array $ids, string $processingStatus): void 
{
 $updateData = [ 'processing_status' => $processingStatus, 'updated_at' => Carbon::now(), ]; if ($processingStatus === TaskMessageModel::PROCESSING_STATUS_COMPLETED) 
{
 $updateData['processed_at'] = Carbon::now(); 
}
 $this->model::query()->whereIn('id', $ids)->update($updateData); 
}
 
    public function getNextSeqId(int $topicId, int $taskId): int 
{
 // DescendingIndexdirectly GetMaximum seq_id ORDER BY seq_id DESC $maxSeqId = $this->model::query() ->where('topic_id', $topicId) ->where('task_id', $taskId) ->orderByDesc('seq_id') ->value('seq_id'); // IfDon't haverecord Return 1OtherwiseReturn MaximumValue+1 return ($maxSeqId ?? 0) + 1; 
}
 
    public function saveWithRawData(array $rawData, TaskMessageEntity $message, string $processStatus = TaskMessageModel::PROCESSING_STATUS_PENDING): void 
{
 $messageArray = $message->toArray(); // seq_idalready AtServicein Set ed if (empty($messageArray['seq_id'])) 
{
 throw new InvalidArgumentException('seq_id must be set before saving'); 
}
 // SaveOriginal data $messageArray['raw_data'] = json_encode($rawData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); // Set process Status $messageArray['processing_status'] = $processStatus; $messageArray['retry_count'] = 0; $this->model::query()->create($messageArray); 
}
 
    public function findBySeqIdAndTopicId(int $seqId, int $taskId, int $topicId): ?TaskMessageEntity 
{
 $query = $this->model::query() ->where('seq_id', $seqId) ->where('topic_id', $topicId) ->where('task_id', $taskId) ->first(); if (! $query) 
{
 return null; 
}
 return new TaskMessageEntity($query->toArray()); 
}
 
    public function findByTopicIdAndMessageId(int $topicId, string $messageId): ?TaskMessageEntity 
{
 $query = $this->model::query() ->where('topic_id', $topicId) ->where('message_id', $messageId) ->first(); if (! $query) 
{
 return null; 
}
 return new TaskMessageEntity($query->toArray()); 
}
 
    public function updateExistingMessage(TaskMessageEntity $message): void 
{
 // Use Eloquent model instance to leverage casts automatic conversion $model = $this->model::query()->find($message->getId()); if (! $model) 
{
 throw new RuntimeException('Task message not found for ID: ' . $message->getId()); 
}
 $entityArray = $message->toArray(); // Fill model attributes - casts will automatically handle array to JSON conversion $model->fill($entityArray); // Save using Eloquent - this will apply casts and handle timestamps automatically $model->save(); 
}
 
    public function findprocess ableMessages( int $topicId, int $taskId, string $senderType = 'assistant', int $timeoutMinutes = 30, int $maxRetries = 3, int $limit = 50 ): array 
{
 // SQLquery topic_id + sender_type + Statusquery // AtCodein process ComplexComplexSQLAttable performance issues $query = $this->model::query() ->select([ 'id', 'seq_id', 'processing_status', 'updated_at', 'retry_count', 'raw_data', 'message_id', 'task_id', ]) ->where('topic_id', $topicId) ->where('sender_type', $senderType) ->whereIn('processing_status', [ TaskMessageModel::PROCESSING_STATUS_PENDING, TaskMessageModel::PROCESSING_STATUS_PROCESSING, TaskMessageModel::PROCESSING_STATUS_FAILED, ]); if ($taskId > 0) 
{
 $query = $query->where('task_id', $taskId); 
}
 $query->orderBy('seq_id', 'asc')->limit($limit * 2); // Appropriately enlarge limitBecause need to filter in code $records = $query->get();
$timeoutTime = Carbon::now()->subMinutes($timeoutMinutes); $processableMessages = []; foreach ($records as $record) 
{
 $shouldprocess = false; switch ($record->processing_status) 
{
 case TaskMessageModel::PROCESSING_STATUS_PENDING: // pendingStatusAllprocess $shouldprocess = true; break; case TaskMessageModel::PROCESSING_STATUS_PROCESSING: // processingStatusspecified Timeas yes Timeout $updatedAt = Carbon::parse($record->updated_at); $shouldprocess = $updatedAt->lt($timeoutTime); break; case TaskMessageModel::PROCESSING_STATUS_FAILED: // failedStatusRetryMaximumValue $shouldprocess = $record->retry_count <= $maxRetries; break; 
}
 if ($shouldprocess ) 
{
 $processableMessages[] = new TaskMessageEntity($record->toArray()); // ReachTargetQuantityStop if (count($processableMessages) >= $limit) 
{
 break; 
}
 
}
 
}
 return $processableMessages; 
}
 /** * @return TaskMessageEntity[] */ 
    public function findMessagesToCopyByTopicIdAndMessageId(int $topicId, int $messageId): array 
{
 $query = $this->model::query() ->where('topic_id', $topicId) ->where('show_in_ui', true) ->where('id', '<=', $messageId) ->orderBy('id', 'asc'); $records = $query->get(); foreach ($records as $record) 
{
 $messages[] = new TaskMessageEntity($record->toArray()); 
}
 return $messages ?? []; 
}
 
    public function batchCreateMessages(array $messageEntities): array 
{
 if (empty($messageEntities)) 
{
 return []; 
}
 $insertData = []; foreach ($messageEntities as $messageEntity) 
{
 // IfIDSet automatic Generate if (empty($messageEntity->getId())) 
{
 $messageEntity->setId(IdGenerator::getSnowId()); 
}
 $insertData[] = $messageEntity->toArrayWithoutOtherField(); 
}
 // BatchInsert $this->model::query()->insert($insertData); return $messageEntities; // directly Return entitiesas already including ed CorrectID 
}
 
    public function updateMessageSeqId(int $id, ?int $imSeqId): void 
{
 // If im_seq_id Emptyexecute Update if ($imSeqId === null) 
{
 return; 
}
 // Update im_seq_id Field $this->model::query() ->where('id', $id) ->update(['im_seq_id' => $imSeqId]); 
}
 
}
 

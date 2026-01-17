<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Persistence;

use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Carbon\Carbon;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskMessageRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskMessageModel;
use Hyperf\DbConnection\Db;
use InvalidArgumentException;
use RuntimeException;

class TaskMessageRepository implements TaskMessageRepositoryInterface
{
    public function __construct(protected TaskMessageModel $model)
    {
    }

    public function getById(int $id): ?TaskMessageEntity
    {
        $record = $this->model::query()->find($id);
        if (! $record) {
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
        $data = array_map(function (TaskMessageEntity $message) {
            return $message->toArray();
        }, $messages);

        $this->model::query()->insert($data);
    }

    public function findByTaskId(string $taskId): array
    {
        $query = $this->model::query()
            ->where('task_id', $taskId)
            ->orderBy('send_timestamp', 'asc');

        $result = Db::select($query->toSql(), $query->getBindings());

        return array_map(function ($record) {
            return new TaskMessageEntity((array) $record);
        }, $result);
    }

    /**
     * Get user message list by topic ID and task ID (optimized index + filter user messages).
     * @return TaskMessageEntity[]
     */
    public function findUserMessagesByTopicIdAndTaskId(int $topicId, string $taskId): array
    {
        $query = $this->model::query()
            ->where('topic_id', $topicId)
            ->where('task_id', $taskId)
            ->where('sender_type', 'user')
            ->orderBy('id');

        $result = Db::select($query->toSql(), $query->getBindings());

        return array_map(function ($record) {
            return new TaskMessageEntity((array) $record);
        }, $result);
    }

    /**
     * Get message list by topic ID, with pagination support.
     *
     * @param int $topicId Topic ID
     * @param int $page Page number
     * @param int $pageSize Page size
     * @param bool $shouldPage Whether pagination is needed
     * @param string $sortDirection Sort direction, supports asc and desc
     * @param bool $showInUi Whether to show only UI-visible messages
     * @return array Returns array containing message list and total count ['list' => TaskMessageEntity[], 'total' => int]
     */
    public function findByTopicId(int $topicId, int $page = 1, int $pageSize = 20, bool $shouldPage = true, string $sortDirection = 'asc', bool $showInUi = true): array
    {
        // Ensure sort direction is valid
        $sortDirection = strtolower($sortDirection) === 'desc' ? 'desc' : 'asc';

        // Build base query - don't use leftJoin to avoid affecting casts conversion
        $query = $this->model::query()
            ->where('topic_id', $topicId);

        // If $showInUi is true, add filter condition
        if ($showInUi) {
            $query->where('show_in_ui', true);
        }

        $query->orderBy('id', $sortDirection);

        // Get total record count
        $total = $query->count();

        // If pagination is needed, add pagination conditions
        if ($shouldPage) {
            $offset = ($page - 1) * $pageSize;
            $query->offset($offset)->limit($pageSize);
        }

        // Execute query
        $records = $query->get();

        // Convert query results to entity objects
        $messages = [];
        $imSeqIds = [];
        foreach ($records as $record) {
            // toArray() will automatically apply casts conversion
            $entity = new TaskMessageEntity($record->toArray());
            $messages[] = $entity;

            // Collect im_seq_id for batch querying im_status
            if ($entity->getImSeqId() !== null) {
                $imSeqIds[$entity->getImSeqId()] = $entity->getImSeqId();
            }
        }

        // Batch query im_status
        if (! empty($imSeqIds)) {
            $imStatusMap = Db::table('delightful_chat_sequences')
                ->whereIn('id', array_values($imSeqIds))
                ->pluck('status', 'id')
                ->toArray();

            // Set im_status to corresponding entity
            foreach ($messages as $message) {
                if ($message->getImSeqId() !== null && isset($imStatusMap[$message->getImSeqId()])) {
                    $message->setImStatus((int) $imStatusMap[$message->getImSeqId()]);
                }
            }
        }

        // Return structured result
        return [
            'list' => $messages,
            'total' => $total,
        ];
    }

    public function getUserFirstMessageByTopicId(int $topicId, string $userId): ?TaskMessageEntity
    {
        // Build base query
        $query = $this->model::query()
            ->where('topic_id', $topicId)
            ->where('sender_type', 'user')
            ->where('sender_uid', $userId)
            ->orderBy('id', 'asc');
        $record = $query->first();

        if (! $record) {
            return null;
        }
        return new TaskMessageEntity($record->toArray());
    }

    public function findPendingMessagesByTopicId(int $topicId, string $processingStatus, string $senderType = 'assistant', int $limit = 50): array
    {
        $query = $this->model::query()
            ->where('topic_id', $topicId)
            ->where('processing_status', $processingStatus)
            ->where('sender_type', $senderType)
            ->orderBy('seq_id', 'asc')
            ->limit($limit);

        $result = Db::select($query->toSql(), $query->getBindings());

        return array_map(function ($record) {
            return new TaskMessageEntity((array) $record);
        }, $result);
    }

    public function updateProcessingStatus(int $id, string $processingStatus, ?string $errorMessage = null, int $retryCount = 0): void
    {
        $updateData = [
            'processing_status' => $processingStatus,
            'retry_count' => $retryCount,
            'updated_at' => Carbon::now(),
        ];

        if ($errorMessage !== null) {
            $updateData['error_message'] = $errorMessage;
        }

        if ($processingStatus === TaskMessageModel::PROCESSING_STATUS_COMPLETED) {
            $updateData['processed_at'] = Carbon::now();
        }

        $this->model::query()->where('id', $id)->update($updateData);
    }

    public function batchUpdateProcessingStatus(array $ids, string $processingStatus): void
    {
        $updateData = [
            'processing_status' => $processingStatus,
            'updated_at' => Carbon::now(),
        ];

        if ($processingStatus === TaskMessageModel::PROCESSING_STATUS_COMPLETED) {
            $updateData['processed_at'] = Carbon::now();
        }

        $this->model::query()->whereIn('id', $ids)->update($updateData);
    }

    public function getNextSeqId(int $topicId, int $taskId): int
    {
        // Use descending index to directly get max seq_id, with ORDER BY seq_id DESC
        $maxSeqId = $this->model::query()
            ->where('topic_id', $topicId)
            ->where('task_id', $taskId)
            ->orderByDesc('seq_id')
            ->value('seq_id');

        // If no record, return 1; otherwise return max value + 1
        return ($maxSeqId ?? 0) + 1;
    }

    public function saveWithRawData(array $rawData, TaskMessageEntity $message, string $processStatus = TaskMessageModel::PROCESSING_STATUS_PENDING): void
    {
        $messageArray = $message->toArray();

        // seq_id should already be set in domain service
        if (empty($messageArray['seq_id'])) {
            throw new InvalidArgumentException('seq_id must be set before saving');
        }

        // Save raw data
        $messageArray['raw_data'] = json_encode($rawData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Set initial processing status
        $messageArray['processing_status'] = $processStatus;
        $messageArray['retry_count'] = 0;

        $this->model::query()->create($messageArray);
    }

    public function findBySeqIdAndTopicId(int $seqId, int $taskId, int $topicId): ?TaskMessageEntity
    {
        $query = $this->model::query()
            ->where('seq_id', $seqId)
            ->where('topic_id', $topicId)
            ->where('task_id', $taskId)
            ->first();

        if (! $query) {
            return null;
        }

        return new TaskMessageEntity($query->toArray());
    }

    public function findByTopicIdAndMessageId(int $topicId, string $messageId): ?TaskMessageEntity
    {
        $query = $this->model::query()
            ->where('topic_id', $topicId)
            ->where('message_id', $messageId)
            ->first();

        if (! $query) {
            return null;
        }

        return new TaskMessageEntity($query->toArray());
    }

    public function updateExistingMessage(TaskMessageEntity $message): void
    {
        // Use Eloquent model instance to leverage casts automatic conversion
        $model = $this->model::query()->find($message->getId());

        if (! $model) {
            throw new RuntimeException('Task message not found for ID: ' . $message->getId());
        }

        $entityArray = $message->toArray();

        // Fill model attributes - casts will automatically handle array to JSON conversion
        $model->fill($entityArray);

        // Save using Eloquent - this will apply casts and handle timestamps automatically
        $model->save();
    }

    public function findProcessableMessages(
        int $topicId,
        int $taskId,
        string $senderType = 'assistant',
        int $timeoutMinutes = 30,
        int $maxRetries = 3,
        int $limit = 50
    ): array {
        // Simplified SQL query: query only by topic_id + sender_type + three statuses
        // Handle complex logic in code to avoid performance issues with complex SQL on large tables
        $query = $this->model::query()
            ->select([
                'id',
                'seq_id',
                'processing_status',
                'updated_at',
                'retry_count',
                'raw_data',
                'message_id',
                'task_id',
            ])
            ->where('topic_id', $topicId)
            ->where('sender_type', $senderType)
            ->whereIn('processing_status', [
                TaskMessageModel::PROCESSING_STATUS_PENDING,
                TaskMessageModel::PROCESSING_STATUS_PROCESSING,
                TaskMessageModel::PROCESSING_STATUS_FAILED,
            ]);

        if ($taskId > 0) {
            $query = $query->where('task_id', $taskId);
        }

        $query->orderBy('seq_id', 'asc')->limit($limit * 2); // Appropriately expand limit since filtering is done in code

        $records = $query->get();
        $timeoutTime = Carbon::now()->subMinutes($timeoutMinutes);
        $processableMessages = [];

        foreach ($records as $record) {
            $shouldProcess = false;

            switch ($record->processing_status) {
                case TaskMessageModel::PROCESSING_STATUS_PENDING:
                    // Process all pending status
                    $shouldProcess = true;
                    break;
                case TaskMessageModel::PROCESSING_STATUS_PROCESSING:
                    // Processing status but exceeded specified time (considered timeout)
                    $updatedAt = Carbon::parse($record->updated_at);
                    $shouldProcess = $updatedAt->lt($timeoutTime);
                    break;
                case TaskMessageModel::PROCESSING_STATUS_FAILED:
                    // Failed status but retry count does not exceed maximum
                    $shouldProcess = $record->retry_count <= $maxRetries;
                    break;
            }

            if ($shouldProcess) {
                $processableMessages[] = new TaskMessageEntity($record->toArray());

                // Stop when target count is reached
                if (count($processableMessages) >= $limit) {
                    break;
                }
            }
        }

        return $processableMessages;
    }

    /**
     * @return TaskMessageEntity[]
     */
    public function findMessagesToCopyByTopicIdAndMessageId(int $topicId, int $messageId): array
    {
        $query = $this->model::query()
            ->where('topic_id', $topicId)
            ->where('show_in_ui', true)
            ->where('id', '<=', $messageId)
            ->orderBy('id', 'asc');

        $records = $query->get();

        foreach ($records as $record) {
            $messages[] = new TaskMessageEntity($record->toArray());
        }

        return $messages ?? [];
    }

    public function batchCreateMessages(array $messageEntities): array
    {
        if (empty($messageEntities)) {
            return [];
        }

        $insertData = [];

        foreach ($messageEntities as $messageEntity) {
            // If ID is not set, auto-generate
            if (empty($messageEntity->getId())) {
                $messageEntity->setId(IdGenerator::getSnowId());
            }

            $insertData[] = $messageEntity->toArrayWithoutOtherField();
        }

        // Batch insert
        $this->model::query()->insert($insertData);

        return $messageEntities; // Directly return passed entities since they already contain correct IDs
    }

    public function updateMessageSeqId(int $id, ?int $imSeqId): void
    {
        // If im_seq_id is null, skip update
        if ($imSeqId === null) {
            return;
        }

        // Only update im_seq_id field
        $this->model::query()
            ->where('id', $id)
            ->update(['im_seq_id' => $imSeqId]);
    }
}

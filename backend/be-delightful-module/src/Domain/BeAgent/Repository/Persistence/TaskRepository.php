<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Persistence;

use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskModel;
use Hyperf\DbConnection\Db;

class TaskRepository implements TaskRepositoryInterface
{
    public function __construct(protected TaskModel $model)
    {
    }

    /**
     * Get task model.
     *
     * @return TaskModel Task model
     */
    public function getModel(): TaskModel
    {
        return $this->model;
    }

    public function getTaskById(int $id): ?TaskEntity
    {
        $model = $this->model::query()->find($id);
        if (! $model) {
            return null;
        }
        return new TaskEntity($model->toArray());
    }

    /**
     * Get task by task ID (taskId returned by sandbox service)
     */
    public function getTaskByTaskId(string $taskId): ?TaskEntity
    {
        $model = $this->model::query()->where('task_id', $taskId)->first();
        if (! $model) {
            return null;
        }
        return new TaskEntity($model->toArray());
    }

    /**
     * Get task list by topic ID.
     * @return array{list: TaskEntity[], total: int}
     */
    public function getTasksByTopicId(int $topicId, int $page, int $pageSize, array $conditions = []): array
    {
        $offset = ($page - 1) * $pageSize;
        $query = $this->model::query()->where('topic_id', $topicId);
        // Build conditions
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        // Get total first
        $total = $query->count();

        // Get paginated data
        $query = $query->skip($offset)
            ->take($pageSize)
            ->orderBy('id', 'desc');

        $result = Db::select($query->toSql(), $query->getBindings());

        $list = [];
        foreach ($result as $item) {
            $list[] = new TaskEntity((array) $item);
        }

        return [
            'list' => $list,
            'total' => $total,
        ];
    }

    public function createTask(TaskEntity $taskEntity): TaskEntity
    {
        $date = date('Y-m-d H:i:s');

        // If ID is not set, auto-generate
        if (empty($taskEntity->getId())) {
            $taskEntity->setId(IdGenerator::getSnowId());
        }

        $taskEntity->setCreatedAt($date);
        $taskEntity->setUpdatedAt($date);

        $entityArray = $taskEntity->toArray();
        $model = $this->model::query()->create($entityArray);
        $taskEntity->setId($model->id);

        return $taskEntity;
    }

    public function updateTask(TaskEntity $taskEntity): bool
    {
        $taskEntity->setUpdatedAt(date('Y-m-d H:i:s'));
        $entityArray = $taskEntity->toArray();

        return $this->model::query()
            ->where('id', $taskEntity->getId())
            ->update($entityArray) > 0;
    }

    public function updateTaskStatus(int $id, TaskStatus $status): bool
    {
        return $this->model::query()
            ->where('id', $id)
            ->update([
                'task_status' => $status->value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Update task status by sandbox task ID
     */
    public function updateTaskStatusByTaskId(int $id, TaskStatus $status): bool
    {
        return $this->model::query()
            ->where('id', $id)
            ->update([
                'task_status' => $status->value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    public function deleteTask(int $id): bool
    {
        return $this->model::query()
            ->where('id', $id)
            ->update([
                'deleted_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Get task by user ID and task ID (taskId returned by sandbox service)
     */
    public function getTaskByUserIdAndTaskId(string $userId, string $taskId): ?TaskEntity
    {
        $model = $this->model::query()
            ->where('user_id', $userId)
            ->where('id', $taskId)
            ->first();

        if (empty($model->toArray())) {
            return null;
        }

        return new TaskEntity($model->toArray());
    }

    /**
     * Get task by sandbox ID
     */
    public function getTaskBySandboxId(string $sandboxId): ?TaskEntity
    {
        $model = $this->model::query()->where('sandbox_id', $sandboxId)->first();
        if (! $model) {
            return null;
        }
        return new TaskEntity($model->toArray());
    }

    /**
     * Get task list by user ID.
     *
     * @param string $userId User ID
     * @param array $conditions Conditions array, e.g., ['task_status' => 'running']
     * @return array Task list
     */
    public function getTasksByUserId(string $userId, array $conditions = []): array
    {
        $query = $this->model::query()
            ->where('user_id', $userId);

        // Add other filter conditions
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }

        $tasks = Db::select($query->toSql(), $query->getBindings());

        $result = [];
        foreach ($tasks as $task) {
            $result[] = new TaskEntity((array) $task);
        }

        return $result;
    }

    /**
     * Update long-running tasks to error status
     *
     * @param string $timeThreshold Time threshold, running tasks earlier than this will be marked as error
     * @return int Number of updated tasks
     */
    public function updateStaleRunningTasks(string $timeThreshold): int
    {
        return $this->model::query()
            ->where('task_status', TaskStatus::RUNNING->value)
            ->where('updated_at', '<', $timeThreshold)
            ->whereNull('deleted_at')
            ->update([
                'task_status' => TaskStatus::ERROR->value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Get task list by status.
     *
     * @param TaskStatus $status Task status
     * @return array<TaskEntity> Task entity list
     */
    public function getTasksByStatus(TaskStatus $status): array
    {
        $models = $this->model::query()
            ->where('task_status', $status->value)
            ->whereNull('deleted_at')
            ->get();

        $result = [];
        foreach ($models as $model) {
            $result[] = new TaskEntity($model->toArray());
        }

        return $result;
    }

    /**
     * Batch delete all tasks under specified topic (soft delete).
     *
     * @param int $topicId Topic ID
     * @return int Number of deleted tasks
     */
    public function deleteTasksByTopicId(int $topicId): int
    {
        // Use batch update operation to set deleted_at field to current time for all non-deleted tasks under specified topic
        $now = date('Y-m-d H:i:s');
        return $this->model::query()
            ->where('topic_id', $topicId)
            ->whereNull('deleted_at')  // Ensure only non-deleted tasks are updated
            ->update(['deleted_at' => $now]);
    }

    /**
     * Update task status and error message.
     */
    public function updateTaskStatusAndErrMsg(int $id, TaskStatus $status, ?string $errMsg = null): bool
    {
        $updateData = [
            'task_status' => $status->value,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($errMsg !== null) {
            $updateData['err_msg'] = $errMsg;
        }

        return $this->model::query()
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Update task status and error message by sandbox task ID.
     */
    public function updateTaskStatusAndErrMsgByTaskId(int $id, TaskStatus $status, ?string $errMsg = null): bool
    {
        $updateData = [
            'task_status' => $status->value,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($errMsg !== null) {
            $updateData['err_msg'] = $errMsg;
        }

        return $this->model::query()
            ->where('id', $id)
            ->update($updateData) > 0;
    }

    /**
     * Get task list where last update time exceeds specified time.
     *
     * @param string $timeThreshold Time threshold, tasks updated earlier than this will be included in result
     * @param int $limit Maximum number of results to return
     * @return array<TaskEntity> Task entity list
     */
    public function getTasksExceedingUpdateTime(string $timeThreshold, int $limit = 100): array
    {
        $models = $this->model::query()
            ->where('updated_at', '<', $timeThreshold)
            ->where('task_status', TaskStatus::RUNNING->value)
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->limit($limit)
            ->get();

        $result = [];
        foreach ($models as $model) {
            $result[] = new TaskEntity($model->toArray());
        }

        return $result;
    }

    /**
     * Get task count by topic ID.
     */
    public function getTaskCountByTopicId(int $topicId): int
    {
        return $this->model::query()
            ->where('topic_id', $topicId)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Get task list by project ID.
     */
    public function getTasksByProjectId(int $projectId, string $userId): array
    {
        $models = $this->model::query()
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        $result = [];
        foreach ($models as $model) {
            $result[] = new TaskEntity($model->toArray());
        }

        return $result;
    }

    public function updateTaskStatusBySandboxIds(array $sandboxIds, string $status, string $errMsg = ''): int
    {
        return $this->model::query()
            ->whereIn('sandbox_id', $sandboxIds)
            ->update([
                'task_status' => $status,
                'err_msg' => $errMsg,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Count tasks by project ID.
     */
    public function countTasksByProjectId(int $projectId): int
    {
        return $this->model::query()
            ->where('project_id', $projectId)
            ->whereNull('deleted_at')
            ->count();
    }

    public function updateTaskByCondition(array $condition, array $data): bool
    {
        return $this->model::query()
            ->where($condition)
            ->update($data) > 0;
    }

    public function getTasksByTopicIdAndTaskIds(int $topicId, array $taskIds): array
    {
        if (empty($taskIds)) {
            return [];
        }

        $models = $this->model::query()
            ->where('topic_id', $topicId)
            ->whereIn('id', $taskIds)
            ->whereNull('deleted_at')
            ->orderBy('id', 'asc')
            ->get();

        $result = [];
        foreach ($models as $model) {
            $result[] = new TaskEntity($model->toArray());
        }

        return $result;
    }

    public function batchCreateTasks(array $taskEntities): array
    {
        if (empty($taskEntities)) {
            return [];
        }

        $date = date('Y-m-d H:i:s');
        $insertData = [];

        foreach ($taskEntities as $taskEntity) {
            // If ID is not set, auto-generate (backward compatibility)
            if (empty($taskEntity->getId())) {
                $taskEntity->setId(IdGenerator::getSnowId());
            }

            // Ensure timestamps are set correctly
            if (empty($taskEntity->getCreatedAt())) {
                $taskEntity->setCreatedAt($date);
            }
            if (empty($taskEntity->getUpdatedAt())) {
                $taskEntity->setUpdatedAt($date);
            }

            $insertData[] = $taskEntity->toArray();
        }

        // Batch insert
        $this->model::query()->insert($insertData);

        return $taskEntities; // Directly return passed entities since they already contain correct IDs
    }
}

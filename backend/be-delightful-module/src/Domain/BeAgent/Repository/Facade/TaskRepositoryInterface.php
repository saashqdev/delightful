<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskModel;

interface TaskRepositoryInterface
{
    /**
     * Get task model instance.
     *
     * @return TaskModel Task model instance
     */
    public function getModel(): TaskModel;

    /**
     * Get task by ID
     */
    public function getTaskById(int $id): ?TaskEntity;

    /**
     * Get task by task ID (taskId returned by sandbox service)
     */
    public function getTaskByTaskId(string $taskId): ?TaskEntity;

    /**
     * Get task list by topic ID.
     * @return array{list: TaskEntity[], total: int}
     */
    public function getTasksByTopicId(int $topicId, int $page, int $pageSize, array $conditions = []): array;

    /**
     * Create task
     */
    public function createTask(TaskEntity $taskEntity): TaskEntity;

    /**
     * Update task
     */
    public function updateTask(TaskEntity $taskEntity): bool;

    /**
     * Update task status
     */
    public function updateTaskStatus(int $id, TaskStatus $status): bool;

    /**
     * Update task status and error message.
     */
    public function updateTaskStatusAndErrMsg(int $id, TaskStatus $status, ?string $errMsg = null): bool;

    /**
     * Update task status by sandbox task ID
     */
    public function updateTaskStatusByTaskId(int $id, TaskStatus $status): bool;

    /**
     * Update task status and error message by sandbox task ID.
     */
    public function updateTaskStatusAndErrMsgByTaskId(int $id, TaskStatus $status, ?string $errMsg = null): bool;

    /**
     * Delete task
     */
    public function deleteTask(int $id): bool;

    /**
     * Batch delete all tasks under specified topic (soft delete).
     *
     * @param int $topicId Topic ID
     * @return int Number of deleted tasks
     */
    public function deleteTasksByTopicId(int $topicId): int;

    /**
     * Get task by user ID and task ID (taskId returned by sandbox service)
     */
    public function getTaskByUserIdAndTaskId(string $userId, string $taskId): ?TaskEntity;

    /**
     * Get task by sandbox ID
     */
    public function getTaskBySandboxId(string $sandboxId): ?TaskEntity;

    /**
     * Get task list by user ID.
     *
     * @param string $userId User ID
     * @param array $conditions Condition array, e.g. ['task_status' => 'running']
     * @return array Task list
     */
    public function getTasksByUserId(string $userId, array $conditions = []): array;

    /**
     * Update tasks in running state for a long time to error state
     *
     * @param string $timeThreshold Time threshold, running tasks earlier than this time will be marked as error
     * @return int Number of updated tasks
     */
    public function updateStaleRunningTasks(string $timeThreshold): int;

    /**
     * Get task list by specified status.
     *
     * @param TaskStatus $status Task status
     * @return array<TaskEntity> Task entity list
     */
    public function getTasksByStatus(TaskStatus $status): array;

    /**
     * Get task list with last update time exceeding specified time.
     *
     * @param string $timeThreshold Time threshold, tasks with update time earlier than this will be included in results
     * @param int $limit Maximum number of results to return
     * @return array<TaskEntity> Task entity list
     */
    public function getTasksExceedingUpdateTime(string $timeThreshold, int $limit = 100): array;

    /**
     * Get task count under specified topic.
     */
    public function getTaskCountByTopicId(int $topicId): int;

    /**
     * Get task list by project ID.
     */
    public function getTasksByProjectId(int $projectId, string $userId): array;

    public function updateTaskStatusBySandboxIds(array $sandboxIds, string $status, string $errMsg = ''): int;

    /**
     * Count tasks under project.
     */
    public function countTasksByProjectId(int $projectId): int;

    public function updateTaskByCondition(array $condition, array $data): bool;

    /**
     * Get task entity list by topic ID and task ID list.
     *
     * @param int $topicId Topic ID
     * @param array $taskIds Task ID list
     * @return TaskEntity[] Task entity array
     */
    public function getTasksByTopicIdAndTaskIds(int $topicId, array $taskIds): array;

    /**
     * Batch create tasks
     *
     * @param TaskEntity[] $taskEntities Task entity array
     * @return TaskEntity[] Successfully created task entity array (with generated IDs)
     */
    public function batchCreateTasks(array $taskEntities): array;
}

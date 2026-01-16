<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TaskModel;

interface TaskRepositoryInterface 
{
 /** * GetTaskModelInstance. * * @return TaskModel TaskModelInstance */ 
    public function getModel(): TaskModel; /** * ThroughIDGetTask */ 
    public function getTaskById(int $id): ?TaskEntity; /** * ThroughTaskID(sandbox ServiceReturn taskId)GetTask */ 
    public function getTaskByTaskId(string $taskId): ?TaskEntity; /** * Throughtopic IDGetTasklist . * @return array
{
list: TaskEntity[], total: int
}
 */ 
    public function getTasksByTopicId(int $topicId, int $page, int $pageSize, array $conditions = []): array; /** * CreateTask */ 
    public function createTask(TaskEntity $taskEntity): TaskEntity; /** * UpdateTask */ 
    public function updateTask(TaskEntity $taskEntity): bool; /** * UpdateTaskStatus */ 
    public function updateTaskStatus(int $id, TaskStatus $status): bool; /** * UpdateTaskStatusError message. */ 
    public function updateTaskStatusAndErrMsg(int $id, TaskStatus $status, ?string $errMsg = null): bool; /** * According tosandbox TaskIDUpdateTaskStatus */ 
    public function updateTaskStatusByTaskId(int $id, TaskStatus $status): bool; /** * According tosandbox TaskIDUpdateTaskStatusError message. */ 
    public function updateTaskStatusAndErrMsgByTaskId(int $id, TaskStatus $status, ?string $errMsg = null): bool; /** * delete Task */ 
    public function deleteTask(int $id): bool; /** * Batchdelete specified topic under AllTaskdelete . * * @param int $topicId topic ID * @return int delete TaskQuantity */ 
    public function deleteTasksByTopicId(int $topicId): int; /** * Throughuser IDTaskID(sandbox ServiceReturn taskId)GetTask */ 
    public function getTaskByuser IdAndTaskId(string $userId, string $taskId): ?TaskEntity; /** * ThroughSandbox IDGetTask */ 
    public function getTaskBySandboxId(string $sandboxId): ?TaskEntity; /** * According touser IDGetTasklist . * * @param string $userId user ID * @param array $conditions ConditionArray ['task_status' => 'running'] * @return array Tasklist */ 
    public function getTasksByuser Id(string $userId, array $conditions = []): array; /** * UpdateTimeRowStatusTaskas ErrorStatus * * @param string $timeThreshold TimeThresholdTimeRunningTaskmark as Error * @return int UpdateTaskQuantity */ 
    public function updateStaleRunningTasks(string $timeThreshold): int; /** * Getspecified StatusTasklist . * * @param TaskStatus $status TaskStatus * @return array<TaskEntity> Tasklist */ 
    public function getTasksByStatus(TaskStatus $status): array; /** * Getmost recently Update timespecified TimeTasklist . * * @param string $timeThreshold TimeThresholdIfTaskUpdate timeTimeincluding AtResultin * @param int $limit Return ResultMaximumQuantity * @return array<TaskEntity> Tasklist */ 
    public function getTasksExceedingUpdateTime(string $timeThreshold, int $limit = 100): array; /** * Getspecified topic under TaskQuantity. */ 
    public function getTaskCountByTopicId(int $topicId): int; /** * According toProject IDGetTasklist . */ 
    public function getTasksByProjectId(int $projectId, string $userId): array; 
    public function updateTaskStatusBySandboxIds(array $sandboxIds, string $status, string $errMsg = ''): int; /** * CountItemunder TaskQuantity. */ 
    public function countTasksByProjectId(int $projectId): int; 
    public function updateTaskByCondition(array $condition, array $data): bool; /** * According totopic IDTaskIDlist GetTasklist . * * @param int $topicId topic ID * @param array $taskIds TaskIDlist * @return TaskEntity[] TaskArray */ 
    public function getTasksByTopicIdAndTaskIds(int $topicId, array $taskIds): array; /** * BatchCreateTask * * @param TaskEntity[] $taskEntities TaskArray * @return TaskEntity[] CreateSuccessTaskArrayincluding Generate ID */ 
    public function batchCreateTasks(array $taskEntities): array; 
}
 

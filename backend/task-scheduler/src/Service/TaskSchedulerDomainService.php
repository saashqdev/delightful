<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\TaskScheduler\Service;

use DateInterval;
use DateTime;
use Dtyq\TaskScheduler\Entity\Query\Page;
use Dtyq\TaskScheduler\Entity\Query\TaskSchedulerCrontabQuery;
use Dtyq\TaskScheduler\Entity\Query\TaskSchedulerQuery;
use Dtyq\TaskScheduler\Entity\TaskScheduler;
use Dtyq\TaskScheduler\Entity\TaskSchedulerCrontab;
use Dtyq\TaskScheduler\Entity\ValueObject\TaskSchedulerExecuteResult;
use Dtyq\TaskScheduler\Entity\ValueObject\TaskSchedulerStatus;
use Dtyq\TaskScheduler\Exception\TaskSchedulerParamsSchedulerException;
use Dtyq\TaskScheduler\Factory\TaskSchedulerCrontabFactory;
use Dtyq\TaskScheduler\Factory\TaskSchedulerFactory;
use Dtyq\TaskScheduler\Factory\TaskSchedulerLogFactory;
use Dtyq\TaskScheduler\Repository\Persistence\TaskSchedulerCrontabRepository;
use Dtyq\TaskScheduler\Repository\Persistence\TaskSchedulerLogRepository;
use Dtyq\TaskScheduler\Repository\Persistence\TaskSchedulerRepository;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class TaskSchedulerDomainService
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly TaskSchedulerRepository $scheduleTaskRepository,
        private readonly TaskSchedulerLogRepository $scheduleTaskLogRepository,
        private readonly TaskSchedulerCrontabRepository $scheduleTaskCrontabRepository,
        private readonly LoggerFactory $loggerFactory
    ) {
        $this->logger = $this->loggerFactory->get('task_scheduler');
    }

    public function getById(int $id): ?TaskScheduler
    {
        return $this->scheduleTaskRepository->getById($id);
    }

    public function getByCrontabId(int $crontabId): ?TaskSchedulerCrontab
    {
        $crontab = $this->scheduleTaskCrontabRepository->getByCrontabId($crontabId);
        if (! $crontab) {
            return null;
        }
          // Convert to TaskSchedulerCrontab entity
        return TaskSchedulerCrontabFactory::modelToEntity($crontab);
    }

    /**
      * Create a specified schedule.
     */
    public function create(TaskScheduler $scheduleTask): void
    {
        $scheduleTask->prepareForCreation();
        $this->scheduleTaskRepository->save($scheduleTask);
    }

    /**
     * Create a cron-based schedule.
     */
    public function createCrontab(TaskSchedulerCrontab $scheduleTaskCrontab): TaskSchedulerCrontab
    {
        $scheduleTaskCrontab->prepareForCreate();
        return $this->scheduleTaskCrontabRepository->create($scheduleTaskCrontab);
    }

    /**
     * Batch create cron schedules.
     */
    public function batchCreate(array $scheduleTasks): void
    {
        $newScheduleTasks = [];
        foreach ($scheduleTasks as $scheduleTask) {
            /*
             *  TaskScheduler $scheduleTask
             */
            $scheduleTask->prepareForCreation();
            $newScheduleTasks[] = $scheduleTask;
        }
        $this->scheduleTaskRepository->batchCreate($newScheduleTasks);
    }

    /**
     * Update a cron-based schedule.
     */
    public function saveCrontab(TaskSchedulerCrontab $scheduleTaskCrontab): void
    {
        $scheduleTaskCrontab->prepareForUpdate();
        $this->scheduleTaskCrontabRepository->save($scheduleTaskCrontab);
    }

    // Clear scheduler tasks and crontab rules
    #[Transactional]
    public function clearByExternalId(string $externalId): void
    {
        // Clear scheduler tasks
        $this->scheduleTaskRepository->clearByExternalId($externalId);
        // Clear crontab rules
        $this->scheduleTaskCrontabRepository->clearByExternalId($externalId);
    }

    // Clear scheduler tasks
    #[Transactional]
    public function clearTaskByExternalId(string $externalId): void
    {
        // Clear scheduler tasks
        $this->scheduleTaskRepository->clearByExternalId($externalId);
    }

    /**
     * Create scheduled tasks based on a cron rule.
     */
    #[Transactional]
    public function createByCrontab(TaskSchedulerCrontab $scheduleTaskCrontab, int $days = 3): void
    {
        // if ($days < 1 || $days > 3) {
        //     throw new TaskSchedulerParamsSchedulerException('Only 1-3 day windows are supported for pre-generation');
        // }
        $scheduleTaskCrontab->prepareForCreateScheduleTask();

        // Use the earlier of the upcoming days window and the deadline
        $endTime = (new DateTime())->add(new DateInterval('P' . $days . 'D'));
        if ($scheduleTaskCrontab->getDeadline()) {
            $endTime = min($endTime, $scheduleTaskCrontab->getDeadline());
        }

        $dateList = $scheduleTaskCrontab->listCycleDate($endTime, 100);
        foreach ($dateList as $date) {
            $task = TaskSchedulerFactory::createByCrontab($scheduleTaskCrontab, $date);
            $this->create($task);
            // If the deadline has been reached, disable this crontab
            if ($scheduleTaskCrontab->getDeadline() && $scheduleTaskCrontab->getDeadline() <= $date) {
                $scheduleTaskCrontab->setEnabled(false);
            }
        }
        $this->scheduleTaskCrontabRepository->save($scheduleTaskCrontab);
    }

    /**
     * Query scheduled tasks.
     * @return array{total: int, list: array<TaskScheduler>}
     */
    public function queries(TaskSchedulerQuery $query, Page $page): array
    {
        return $this->scheduleTaskRepository->queries($query, $page);
    }

    /**
     * Query cron schedule tasks.
     * @return array{total: int, list: array<TaskSchedulerCrontab>}
     */
    public function queriesCrontab(TaskSchedulerCrontabQuery $query, Page $page): array
    {
        return $this->scheduleTaskCrontabRepository->queries($query, $page);
    }

    /**
     * Execute a scheduled task.
     */
    public function execute(TaskScheduler $scheduleTask): TaskSchedulerExecuteResult
    {
        $this->logger->info('execute_task_scheduler_start', ['id' => $scheduleTask->getId()]);
        $scheduleTask->prepareForExecution();

        try {
            $this->scheduleTaskRepository->changeStatus($scheduleTask->getId(), TaskSchedulerStatus::Running);
            $result = $scheduleTask->execute();
            if ($result->isSuccess()) {
                $scheduleTask->setStatus(TaskSchedulerStatus::Success);
            } else {
                $retryTimes = max(0, $scheduleTask->getRetryTimes() - 1);
                $scheduleTask->setRetryTimes($retryTimes);
                if ($retryTimes > 0) {
                    $scheduleTask->setStatus(TaskSchedulerStatus::Retry);
                } else {
                    // Retries exhausted, mark as failed
                    $scheduleTask->setStatus(TaskSchedulerStatus::Failed);
                }
            }
            $this->scheduleTaskRepository->save($scheduleTask);
            $this->createLog($scheduleTask, $result);
            $this->logger->info('execute_task_scheduler_success', ['id' => $scheduleTask->getId(), 'result' => $result->toArray()]);
        } catch (Throwable $throwable) {
            $this->scheduleTaskRepository->changeStatus($scheduleTask->getId(), TaskSchedulerStatus::Failed);
            $this->logger->error('execute_task_scheduler_fail', ['id' => $scheduleTask->getId(), 'exception' => $throwable->getMessage()]);
            throw $throwable;
        }
        return $result;
    }

    /**
     * Cancel scheduled tasks.
     */
    #[Transactional]
    public function cancel(TaskSchedulerQuery $query): void
    {
        if (empty($query->getIds()) || empty($query->getExternalIds())) {
            return;
        }
        if (count($query->getIds()) > 100 || count($query->getExternalIds()) > 100) {
            throw new TaskSchedulerParamsSchedulerException('Too many ids to cancel');
        }
        $data = $this->queries($query, new Page(1, 500));
        $cancelIds = [];
        foreach ($data['list'] as $task) {
            $task->prepareForCancel();
            $cancelIds[] = $task->getId();
        }

        $this->scheduleTaskRepository->cancelByIds($cancelIds);
        foreach ($data['list'] as $task) {
            $this->createLog($task);
        }
        $this->logger->info('cancel_task_scheduler', ['ids' => $cancelIds]);
    }

    public function deleteByIds(array $clearIds): void
    {
        $this->scheduleTaskRepository->deleteByIds($clearIds);
    }

    public function existsByExternalId(string $externalId): bool
    {
        return $this->scheduleTaskCrontabRepository->existsByExternalId($externalId);
    }

    /**
     * Archive.
     */
    private function createLog(TaskScheduler $scheduleTask, ?TaskSchedulerExecuteResult $result = null): void
    {
        $log = TaskSchedulerLogFactory::createByScheduleTask($scheduleTask);
        $log->setResult($result);
        $log->prepareForCreation();
        $this->scheduleTaskLogRepository->create($log);
    }
}

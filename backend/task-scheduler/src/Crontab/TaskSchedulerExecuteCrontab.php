<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\TaskScheduler\Crontab;

use DateTime;
use Dtyq\TaskScheduler\Entity\Query\Page;
use Dtyq\TaskScheduler\Entity\Query\TaskSchedulerQuery;
use Dtyq\TaskScheduler\Entity\TaskScheduler;
use Dtyq\TaskScheduler\Entity\ValueObject\TaskSchedulerStatus;
use Dtyq\TaskScheduler\Service\TaskSchedulerDomainService;
use Dtyq\TaskScheduler\Util\Locker;
use Hyperf\Coroutine\Concurrent;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Config\config;

#[Crontab(rule: '* * * * *', name: 'TaskSchedulerExecuteCrontab', singleton: true, mutexExpires: 90, onOneServer: true, callback: 'execute', memo: 'Execute schedules')]
class TaskSchedulerExecuteCrontab
{
    protected Concurrent $concurrent;

    private LoggerInterface $logger;

    private array $config;

    public function __construct(
        private readonly TaskSchedulerDomainService $scheduleTaskDomainService,
        private readonly LoggerFactory $loggerFactory,
        private readonly Locker $locker
    ) {
        $this->config = config('task_scheduler');
        $this->logger = $this->loggerFactory->get('task_scheduler');
        $limit = (int) ($this->config['concurrent_limit'] ?? 500);
        $this->concurrent = new Concurrent(max($limit, 1));
    }

    public function execute(): void
    {
        // Fetch tasks whose scheduled time has passed and have not started yet
        $query = new TaskSchedulerQuery();
        $query->setExpectTimeLt(new DateTime());
        $query->setStatus(TaskSchedulerStatus::Pending);
        $page = new Page(1, 200);
        $limitPage = 1000;
        while (true) {
            $data = $this->scheduleTaskDomainService->queries($query, $page);
            $list = $data['list'] ?? [];
            if (empty($list)) {
                break;
            }
            foreach ($list as $scheduleTask) {
                $this->concurrent->create(function () use ($scheduleTask) {
                    $this->run($scheduleTask);
                });
            }
            if ($data['total'] <= $page->getPageNum() * $page->getPage()) {
                break;
            }
            $page->setNextPage();
            if ($page->getPage() > $limitPage) {
                break;
            }
        }
    }

    private function run(TaskScheduler $taskScheduler): void
    {
        $expire = (int) ($this->config['lock_timeout'] ?? 60 * 10);
        $lockKey = "TaskSchedulerExecuteCrontab-{$taskScheduler->getId()}";
        $lockOwner = 'TaskSchedulerExecuteCrontab';

        try {
            if (! $this->locker->mutexLock($lockKey, $lockOwner, max($expire, 1))) {
                return;
            }

            // Fetch the latest data in real time
            $taskScheduler = $this->scheduleTaskDomainService->getById($taskScheduler->getId());
            if (! $taskScheduler) {
                return;
            }
            $this->scheduleTaskDomainService->execute($taskScheduler);
        } catch (Throwable $throwable) {
            $this->logger->notice('Failed to execute schedule', [
                'task_scheduler_id' => $taskScheduler->getId(),
                'exception' => $throwable->getMessage(),
            ]);
        } finally {
            $this->locker->release($lockKey, $lockOwner);
        }
    }
}

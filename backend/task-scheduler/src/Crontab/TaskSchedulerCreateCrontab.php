<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\TaskScheduler\Crontab;

use DateTime;
use Dtyq\TaskScheduler\Entity\Query\Page;
use Dtyq\TaskScheduler\Entity\Query\TaskSchedulerCrontabQuery;
use Dtyq\TaskScheduler\Service\TaskSchedulerDomainService;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Config\config;

#[Crontab(rule: '* * * * *', name: 'TaskSchedulerCreateCrontab', singleton: true, mutexExpires: 90, onOneServer: true, callback: 'execute', memo: 'Create scheduled data for the next n days')]
class TaskSchedulerCreateCrontab
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly TaskSchedulerDomainService $scheduleTaskDomainService,
        private readonly LoggerFactory $loggerFactory
    ) {
        $this->logger = $this->loggerFactory->get('task_scheduler');
    }

    public function execute(): void
    {
        $days = config('task_scheduler.crontab_days', 3);
        $lastTime = new DateTime();
        // Retrieve records newer than lastTime
        $query = new TaskSchedulerCrontabQuery();
        $query->setLastGenTimeGt($lastTime);
        $query->setEnable(true);
        $page = new Page(1, 100);
        $limitPage = 100;
        while (true) {
            $data = $this->scheduleTaskDomainService->queriesCrontab($query, $page);
            $list = $data['list'] ?? [];
            if (empty($list)) {
                break;
            }
            foreach ($list as $scheduleTask) {
                try {
                    $this->scheduleTaskDomainService->createByCrontab($scheduleTask, $days);
                } catch (Throwable $throwable) {
                    $this->logger->notice('Failed to create schedule', [
                        'task_scheduler_id' => $scheduleTask->getId(),
                        'exception' => $throwable->getMessage(),
                    ]);
                }
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
}

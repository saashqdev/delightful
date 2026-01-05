<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\TaskScheduler\Crontab;

use DateInterval;
use DateTime;
use Delightful\TaskScheduler\Entity\Query\Page;
use Delightful\TaskScheduler\Entity\Query\TaskSchedulerQuery;
use Delightful\TaskScheduler\Entity\TaskScheduler;
use Delightful\TaskScheduler\Service\TaskSchedulerDomainService;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

use function Hyperf\Config\config;

#[Crontab(rule: '0 2 * * *', name: 'TaskSchedulerClearCrontab', singleton: true, mutexExpires: 600, onOneServer: true, callback: 'execute', memo: 'Clear scheduling data older than n days')]
class TaskSchedulerClearCrontab
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
        // Clear scheduled data older than n days
        $clearDays = config('task_scheduler.clear_days', 3);
        $clearTime = (new DateTime())->sub(new DateInterval("P{$clearDays}D"));

        $query = new TaskSchedulerQuery();
        $query->setOrder(['id' => 'asc']);
        // Delete one page at a time
        $page = new Page(1, 1000);
        $limitPage = 100;
        while (true) {
            $data = $this->scheduleTaskDomainService->queries($query, $page);
            $list = $data['list'] ?? [];
            if (empty($list)) {
                break;
            }
            $clearIds = [];
            foreach ($list as $scheduleTask) {
                if ($scheduleTask->getExpectTime() < $clearTime) {
                    $clearIds[] = $scheduleTask->getId();
                }
            }
            if (! empty($clearIds)) {
                $this->scheduleTaskDomainService->deleteByIds($clearIds);
                $this->logger->info('Cleared scheduled data', [
                    'clear_ids' => $clearIds,
                ]);
            }
            /** @var TaskScheduler $end */
            $end = end($list);
            if ($end->getExpectTime() >= $clearTime) {
                break;
            }
            $page->setNextPage();
            if ($page->getPage() > $limitPage) {
                break;
            }
        }
    }
}

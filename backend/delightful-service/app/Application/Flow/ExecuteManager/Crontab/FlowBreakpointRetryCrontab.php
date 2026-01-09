<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Crontab;

use App\Application\Flow\ExecuteManager\Archive\FlowExecutorArchiveCloud;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\DelightfulFlowExecutor;
use App\Domain\Flow\Entity\DelightfulFlowExecuteLogEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Service\DelightfulFlowExecuteLogDomainService;
use App\Infrastructure\Core\ValueObject\Page;
use App\Infrastructure\Util\Locker\LockerInterface;
use Hyperf\Coroutine\Parallel;
use Hyperf\Crontab\Annotation\Crontab;
use Psr\Container\ContainerInterface;

#[Crontab(rule: '* * * * *', name: 'FlowBreakpointRetryCrontab', singleton: true, mutexExpires: 60 * 5, onOneServer: true, callback: 'execute', memo: 'process断点retryscheduletask', enable: true)]
class FlowBreakpointRetryCrontab
{
    private DelightfulFlowExecuteLogDomainService $delightfulFlowExecuteLogDomainService;

    private LockerInterface $locker;

    public function __construct(ContainerInterface $container)
    {
        $this->delightfulFlowExecuteLogDomainService = $container->get(DelightfulFlowExecuteLogDomainService::class);
        $this->locker = $container->get(LockerInterface::class);
    }

    public function execute(): void
    {
        $flowDataIsolation = FlowDataIsolation::create()->disabled();

        $page = new Page(1, 200);
        $maxPage = 1000;
        $parallel = new Parallel(50);
        while (true) {
            $parallel->clear();
            // get所有 10 分钟还在进行中的process
            $list = $this->delightfulFlowExecuteLogDomainService->getRunningTimeoutList($flowDataIsolation, 60 * 10, $page);
            if (empty($list)) {
                break;
            }
            foreach ($list as $delightfulFlowExecuteLogEntity) {
                $parallel->add(function () use ($delightfulFlowExecuteLogEntity) {
                    $this->retry($delightfulFlowExecuteLogEntity);
                });
            }
            $parallel->wait();
            $page->setNextPage();
            if ($page->getPage() > $maxPage) {
                break;
            }
        }
    }

    private function retry(DelightfulFlowExecuteLogEntity $delightfulFlowExecuteLogEntity): void
    {
        $lockKey = "FlowBreakpointRetryCrontab-{$delightfulFlowExecuteLogEntity->getExecuteDataId()}";
        $lockOwner = 'FlowBreakpointRetryCrontab';
        if (! $this->locker->mutexLock($lockKey, $lockOwner, 60 * 10)) {
            return;
        }

        try {
            $flowDataIsolation = FlowDataIsolation::create()->disabled();

            // 实时query最新
            $delightfulFlowExecuteLogEntity = $this->delightfulFlowExecuteLogDomainService->getByExecuteId($flowDataIsolation, $delightfulFlowExecuteLogEntity->getExecuteDataId());
            if ($delightfulFlowExecuteLogEntity->getRetryCount() >= 1) {
                return;
            }

            // retrycount +1
            $this->delightfulFlowExecuteLogDomainService->incrementRetryCount($flowDataIsolation, $delightfulFlowExecuteLogEntity);

            $extParams = $delightfulFlowExecuteLogEntity->getExtParams();
            $archive = FlowExecutorArchiveCloud::get($extParams['organization_code'], (string) $delightfulFlowExecuteLogEntity->getExecuteDataId());
            $flowEntity = $archive['delightful_flow'];
            /** @var ExecutionData $executionData */
            $executionData = $archive['execution_data'];
            // reset一些record
            $executionData->rewind();

            $executor = new DelightfulFlowExecutor($flowEntity, $executionData, lastDelightfulFlowExecuteLogEntity: $delightfulFlowExecuteLogEntity);
            $executor->execute();
        } finally {
            $this->locker->release($lockKey, $lockOwner);
        }
    }
}

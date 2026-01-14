<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\AsyncEvent\Kernel\Crontab;

use BeDelightful\AsyncEvent\Kernel\AsyncEventRetry;
use BeDelightful\AsyncEvent\Kernel\Service\AsyncEventService;
use BeDelightful\AsyncEvent\Kernel\Utils\Locker;
use Hyperf\Coroutine\Parallel;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class RetryCrontab
{
    private AsyncEventService $asyncEventService;

    private LoggerInterface $logger;

    private Locker $locker;

    public function __construct(AsyncEventService $asyncEventService, LoggerFactory $loggerFactory, Locker $locker)
    {
        $this->asyncEventService = $asyncEventService;
        $this->logger = $loggerFactory->get('RetryCrontab');
        $this->locker = $locker;
    }

    public function execute(): void
    {
        try {
            // Query records stuck in pending or executing state
            $datetime = date('Y-m-d H:i:s', time() - (int) \Hyperf\Config\config('async_event.retry.interval', 600));
            $recordIds = $this->asyncEventService->getTimeoutRecordIds($datetime);
            $parallel = new Parallel(30);
            foreach ($recordIds as $recordId) {
                $parallel->add(function () use ($recordId) {
                    $this->locker->get(function () use ($recordId) {
                        $this->logger->info("Retry async event [{$recordId}].");
                        AsyncEventRetry::retry($recordId);
                    }, "async_event_retry_{$recordId}");
                });
            }
            $parallel->wait(false);
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getTraceAsString());
        }
    }

    public function isEnable(): bool
    {
        return true;
    }
}

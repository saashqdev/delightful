<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\AsyncEvent\Kernel\Crontab;

use Delightful\AsyncEvent\Kernel\Service\AsyncEventService;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class ClearHistoryCrontab
{
    private AsyncEventService $asyncEventService;

    private LoggerInterface $logger;

    public function __construct(AsyncEventService $asyncEventService, LoggerFactory $loggerFactory)
    {
        $this->asyncEventService = $asyncEventService;
        $this->logger = $loggerFactory->get('ClearHistoryCrontab');
    }

    public function execute(): void
    {
        try {
            $this->asyncEventService->clearHistory();
        } catch (Throwable $throwable) {
            $this->logger->error($throwable->getTraceAsString());
        }
    }

    public function isEnable(): bool
    {
        return (bool) \Hyperf\Config\config('async_event.clear_history', true);
    }
}

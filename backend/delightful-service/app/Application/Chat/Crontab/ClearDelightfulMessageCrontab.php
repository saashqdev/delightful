<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Crontab;

use Carbon\Carbon;
use Hyperf\Crontab\Annotation\Crontab;
use Psr\Log\LoggerInterface;

// #[Crontab(rule: '*/1 * * * *', name: 'ClearDelightfulMessageCrontab', singleton: true, mutexExpires: 600, onOneServer: true, callback: 'execute', memo: 'cleanupdelightfulMessage')]
readonly class ClearDelightfulMessageCrontab
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function execute(): void
    {
        $this->logger->info('ClearDelightfulMessageCrontab start');
        $time = Carbon::now()->subMinutes(30)->toDateTimeString();
        $this->clearDelightfulMessage($time);
        $this->logger->info('ClearDelightfulMessageCrontab success');
    }

    public function clearDelightfulMessage(string $time): void
    {
        // recordingfeaturealready移except,thismethod保留fornullimplement,canaccording toneedaddothercleanuplogic
        $this->logger->info(sprintf('ClearDelightfulMessageCrontab time: %s - recording functionality removed', $time));
    }
}

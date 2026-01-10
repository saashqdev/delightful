<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Kernel\Event\Subscribe;

use Hyperf\Context\ApplicationContext;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swow\Coroutine;
use Swow\Watchdog;
use WeakMap;

readonly class DelightfulWatchDogSubscriber implements ListenerInterface
{
    public function __construct()
    {
    }

    public function listen(): array
    {
        return [
            MainCoroutineServerStart::class,
        ];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(object $event): void
    {
        if ((bool) env('ENABLE_DELIGHTFUL_WATCHDOG', true) !== true) {
            return;
        }
        $quantum = 10 * 1000 * 1000; // unit:millisecondssecond
        $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)?->get('DelightfulWatchDogSubscriber');
        // watchdogfindsameblockingplace
        $logger->info('Delightfulwatchdog,start!');
        $alertCountMap = new WeakMap();
        Watchdog::run($quantum * 5, 0, static function () use (&$alertCountMap, $logger) {
            $coroutine = Coroutine::getCurrent();
            $alertCount = ($alertCountMap[$coroutine] ??= 0) + 1;
            $alertCountMap[$coroutine] = $alertCount;
            // whensinglecoroutinerunlineexceedspass $millSeconds o clock,willtouchhairwatchdog,printcoroutinecallstack
            if ($alertCount > 1) {
                $trace = str_replace(["\n", "\r"], ' | ', $coroutine->getTraceAsString());
                $logger->error(sprintf(
                    'Delightfulwatchdog hairshowblocking coroutine id:%s,samecoroutineblockingcount:%s trace :%s ',
                    $coroutine->getId(),
                    $alertCount,
                    $trace
                ));
            }
            // letouttimeslice,letothercoroutinehavemachinewillexecute
            $millSeconds = 10 * 1000; // 10 millisecondssecond
            usleep($millSeconds * $alertCount);
        });
    }
}

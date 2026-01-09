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
        $quantum = 10 * 1000 * 1000; // unit:毫second
        $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)?->get('DelightfulWatchDogSubscriber');
        // watchdog找同blockingplace
        $logger->info('Magicwatchdog,start!');
        $alertCountMap = new WeakMap();
        Watchdog::run($quantum * 5, 0, static function () use (&$alertCountMap, $logger) {
            $coroutine = Coroutine::getCurrent();
            $alertCount = ($alertCountMap[$coroutine] ??= 0) + 1;
            $alertCountMap[$coroutine] = $alertCount;
            // whensinglecoroutine运line超pass $millSeconds o clock,will触hairwatchdog,printcoroutinecallstack
            if ($alertCount > 1) {
                $trace = str_replace(["\n", "\r"], ' | ', $coroutine->getTraceAsString());
                $logger->error(sprintf(
                    'Magicwatchdog hair现blocking coroutine id:%s,同coroutineblockingcount:%s trace :%s ',
                    $coroutine->getId(),
                    $alertCount,
                    $trace
                ));
            }
            // letouttimeslice,letothercoroutinehave机willexecute
            $millSeconds = 10 * 1000; // 10 毫second
            usleep($millSeconds * $alertCount);
        });
    }
}
